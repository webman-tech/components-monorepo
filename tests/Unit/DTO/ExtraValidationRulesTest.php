<?php

use WebmanTech\DTO\BaseDTO;
use WebmanTech\DTO\Exceptions\DTOValidateException;

test('getExtraValidationRules top level dynamic per call', function () {
    class DTOExtraTopLevelDynamicTest extends BaseDTO
    {
        public static string $min = '1';

        public string $note;

        protected static function getExtraValidationRules(): array
        {
            return [
                'note' => 'string|min:' . self::$min,
            ];
        }
    }

    // 首次调用
    $rules = DTOExtraTopLevelDynamicTest::getValidationRules();
    expect($rules['note'])->toBe(['required', 'string', 'min:1']);

    // 修改静态状态后再次调用，规则实时变化（顶层调用每请求动态，不缓存）
    DTOExtraTopLevelDynamicTest::$min = '2';
    $rules = DTOExtraTopLevelDynamicTest::getValidationRules();
    expect($rules['note'])->toBe(['required', 'string', 'min:2']);
});

test('getExtraValidationRules solidified when nested expanded', function () {
    class DTOExtraNestedSolidifiedChildTest extends BaseDTO
    {
        public static string $min = '1';
        public static int $extraCallCount = 0;

        public string $note;

        protected static function getExtraValidationRules(): array
        {
            self::$extraCallCount++;

            return [
                'note' => 'string|min:' . self::$min,
            ];
        }
    }

    class DTOExtraNestedSolidifiedParentTest extends BaseDTO
    {
        public DTOExtraNestedSolidifiedChildTest $child;
    }

    // 首次调用父级：子 DTO 的 extra 执行一次，随规则树固化
    $rules1 = DTOExtraNestedSolidifiedParentTest::getValidationRules();
    expect($rules1)->toBe([
        'child' => ['required', 'array'],
        'child.note' => ['required_with:child', 'string', 'min:1'],
    ])
        ->and(DTOExtraNestedSolidifiedChildTest::$extraCallCount)->toBe(1);

    // 修改子 DTO 的动态状态后再次调用父级：子 extra 不再执行，规则保持首次结果
    DTOExtraNestedSolidifiedChildTest::$min = '100';
    $rules2 = DTOExtraNestedSolidifiedParentTest::getValidationRules();
    expect($rules2)->toBe($rules1)
        ->and(DTOExtraNestedSolidifiedChildTest::$extraCallCount)->toBe(1);

    // 子 DTO 顶层直调仍是动态的（每次实时执行，固化只发生在父级规则树内）
    $childRules = DTOExtraNestedSolidifiedChildTest::getValidationRules();
    expect($childRules['note'])->toBe(['required', 'string', 'min:100'])
        ->and(DTOExtraNestedSolidifiedChildTest::$extraCallCount)->toBe(2);

    // 端到端：验证走的是固化后的规则（min:1），而非当前动态值（min:100）
    $dto = DTOExtraNestedSolidifiedParentTest::fromData([
        'child' => ['note' => 'a'],
    ]);
    expect($dto->child->note)->toBe('a');
});

test('getExtraValidationRules dot path penetrate nested DTO', function () {
    class DTOExtraDotPathChildTest extends BaseDTO
    {
        public string $note;
    }

    class DTOExtraDotPathParentTest extends BaseDTO
    {
        public DTOExtraDotPathChildTest $child;

        protected static function getExtraValidationRules(): array
        {
            // 嵌套字段需要动态规则时，在顶层以 dot-path 返回穿透嵌套
            return [
                'child.note' => 'string|min:3',
            ];
        }
    }

    // dot-path 规则合并进嵌套字段的规则中
    $rules = DTOExtraDotPathParentTest::getValidationRules();
    expect($rules)->toBe([
        'child' => ['required', 'array'],
        'child.note' => ['required_with:child', 'string', 'min:3'],
    ]);

    // 端到端：dot-path 规则对嵌套字段生效
    try {
        DTOExtraDotPathParentTest::fromData([
            'child' => ['note' => 'ab'],
        ]);
        throw new InvalidArgumentException('not reachable');
    } catch (DTOValidateException $e) {
        expect(array_keys($e->getErrors()))->toBe(['child.note']);
    }

    $dto = DTOExtraDotPathParentTest::fromData([
        'child' => ['note' => 'abc'],
    ]);
    expect($dto->child->note)->toBe('abc');
});
