<?php

use WebmanTech\AmisAdmin\Amis\Component;
use WebmanTech\AmisAdmin\Amis\DetailAttribute;
use WebmanTech\AmisAdmin\Amis\FormField;
use WebmanTech\AmisAdmin\Amis\GridColumn;
use WebmanTech\AmisAdmin\Helper\DTO\PresetItem;
use WebmanTech\AmisAdmin\Helper\PresetsHelper;
use WebmanTech\AmisAdmin\Repository\RepositoryInterface;

function components_to_array(array $items): array
{
    return array_map(fn(Component $item) => $item->toArray(), $items);
}

beforeEach(function () {
    $this->presetsHelper = new PresetsHelper();
});

test('support withPresets', function () {
    $presetsHelper = $this->presetsHelper;
    expect(array_keys($presetsHelper->pickLabel()))->toBe([]);

    $presetsHelper->withPresets([
        'id' => new PresetItem(
            label: 'ID',
        ),
    ]);
    expect($presetsHelper->pickLabel())->toBe(['id' => 'ID']);
});

test('support withDefaultSceneKeys', function () {
    $presetsHelper = $this->presetsHelper
        ->withPresets([
            'id' => new PresetItem(label: 'ID'),
            'code' => new PresetItem(label: 'Code'),
        ]);
    expect($presetsHelper->pickLabel())->toBe(['id' => 'ID', 'code' => 'Code']);
    $presetsHelper->withDefaultSceneKeys(['id']);
    expect($presetsHelper->pickLabel())->toBe(['id' => 'ID']);
});

test('support withCrudSceneKeys', function () {
    $presetsHelper = $this->presetsHelper
        ->withPresets([
            'id' => new PresetItem(label: 'ID'),
            'code' => new PresetItem(label: 'Code'),
        ]);
    expect($presetsHelper->pickLabel())->toBe(['id' => 'ID', 'code' => 'Code']);
    $presetsHelper->withCrudSceneKeys(['id']);
    expect($presetsHelper->pickLabel())->toBe(['id' => 'ID', 'code' => 'Code'])
        ->and($presetsHelper->withScene(RepositoryInterface::SCENE_CREATE)->pickLabel())->toBe(['id' => 'ID'])
        ->and($presetsHelper->withScene(RepositoryInterface::SCENE_CREATE)->pickLabel())->toBe(['id' => 'ID'])
        ->and($presetsHelper->withScene(RepositoryInterface::SCENE_UPDATE)->pickLabel())->toBe(['id' => 'ID'])
        ->and($presetsHelper->withScene(RepositoryInterface::SCENE_DETAIL)->pickLabel())->toBe(['id' => 'ID']);
});

test('support withSceneKeys', function () {
    $presetsHelper = $this->presetsHelper
        ->withPresets([
            'id' => new PresetItem(label: 'ID'),
            'code' => new PresetItem(label: 'Code'),
        ]);
    expect($presetsHelper->pickLabel())->toBe(['id' => 'ID', 'code' => 'Code']);
    $presetsHelper->withSceneKeys([
        'scene_abc' => ['id'],
        'scene_xyz' => ['code'],
    ]);
    expect($presetsHelper->pickLabel())->toBe(['id' => 'ID', 'code' => 'Code'])
        ->and($presetsHelper->withScene('scene_abc')->pickLabel())->toBe(['id' => 'ID'])
        ->and($presetsHelper->withScene('scene_xyz')->pickLabel())->toBe(['code' => 'Code']);
});

test('support withScene', function () {
    $presetsHelper = $this->presetsHelper
        ->withPresets([
            'id' => new PresetItem(label: 'ID'),
            'code' => new PresetItem(label: 'Code'),
        ])
        ->withSceneKeys([
            'scene_abc' => ['id']
        ]);
    expect($presetsHelper->pickLabel())->toBe(['id' => 'ID', 'code' => 'Code'])
        ->and($presetsHelper->withScene('scene_abc')->pickLabel())->toBe(['id' => 'ID'])
        ->and($presetsHelper->pickLabel())->toBe(['id' => 'ID']) // withScene 具有副作用，会更改全局当前的 scene
        ->and($presetsHelper->withScene()->pickLabel())->toBe(['id' => 'ID', 'code' => 'Code']); // 使用 withScene(null) 重置 scene
});

test('support label', function () {
    $presetsHelper = $this->presetsHelper
        ->withPresets([
            'id' => new PresetItem(label: 'ID'),
        ]);
    expect($presetsHelper->pickLabel())->toBe(['id' => 'ID'])
        ->and($presetsHelper->pickGrid(['id'])[0]->get('label'))->toBe('ID')
        ->and($presetsHelper->pickForm(['id'])[0]->get('label'))->toBe('ID')
        ->and($presetsHelper->pickDetail(['id'])[0]->get('label'))->toBe('ID');
});

test('support labelRemark', function () {
    $presetsHelper = $this->presetsHelper
        ->withPresets([
            'id' => new PresetItem(labelRemark: 'ID'),
        ]);
    expect($presetsHelper->pickLabelRemark())->toBe(['id' => 'ID'])
        ->and($presetsHelper->pickGrid(['id'])[0]->get('labelRemark'))->toBeNull()
        ->and($presetsHelper->pickForm(['id'])[0]->get('labelRemark'))->toBe('ID')
        ->and($presetsHelper->pickDetail(['id'])[0]->get('labelRemark'))->toBeNull();
});

test('support description', function () {
    $presetsHelper = $this->presetsHelper
        ->withPresets([
            'id' => new PresetItem(description: 'ID'),
        ]);
    expect($presetsHelper->pickDescription())->toBe(['id' => 'ID'])
        ->and($presetsHelper->pickGrid(['id'])[0]->get('description'))->toBeNull()
        ->and($presetsHelper->pickForm(['id'])[0]->get('description'))->toBe('ID')
        ->and($presetsHelper->pickDetail(['id'])[0]->get('description'))->toBeNull();
});

test('support filter', function () {
    $presetsHelper = $this->presetsHelper
        ->withPresets([
            'key0' => new PresetItem(),
            'key1' => new PresetItem(filter: true),
            'key2' => new PresetItem(filter: '='),
            'key3' => new PresetItem(filter: 'like'),
            'key4' => new PresetItem(filter: null),
            'key5' => new PresetItem(filter: false),
        ]);
    $filters = $presetsHelper->pickFilter();
    expect($filters['key0'])->toBeInstanceOf(Closure::class)
        ->and($filters['key1'])->toBeInstanceOf(Closure::class)
        ->and($filters['key2'])->toBeInstanceOf(Closure::class)
        ->and($filters['key3'])->toBeInstanceOf(Closure::class)
        ->and(array_key_exists('key4', $filters))->toBeFalse()
        ->and(array_key_exists('key5', $filters))->toBeFalse();
});

test('support grid', function () {
    $presetsHelper = $this->presetsHelper
        ->withPresets([
            'default' => new PresetItem(),
            'change_grid' => new PresetItem(
                grid: fn(string $key) => GridColumn::make()->name($key),
            ),
            'ext_grid' => new PresetItem(
                gridExt: fn(GridColumn $column) => $column->sortable(),
            ),
            'no_filter' => new PresetItem(
                filter: null,
            ),
            'hidden' => new PresetItem(
                grid: null,
            ),
            'hidden2' => new PresetItem(
                grid: false,
            ),
        ]);
    expect(components_to_array($presetsHelper->pickGrid()))->toBe(components_to_array([
        GridColumn::make()->name('default')->searchable(),
        GridColumn::make()->name('change_grid'),
        GridColumn::make()->name('ext_grid')->searchable()->sortable(),
        GridColumn::make()->name('no_filter'),
    ]));
});

test('support form', function () {
    $presetsHelper = $this->presetsHelper
        ->withPresets([
            'default' => new PresetItem(),
            'change_form' => new PresetItem(
                form: fn(string $key) => FormField::make()->name($key),
            ),
            'ext_form' => new PresetItem(
                formExt: fn(FormField $field) => $field->hidden(),
            ),
            'auto_required' => new PresetItem(
                rule: 'required',
            ),
            'hidden' => new PresetItem(
                form: null,
            ),
            'hidden2' => new PresetItem(
                form: false,
            ),
        ]);
    expect(components_to_array($presetsHelper->pickForm()))->toBe(components_to_array([
        FormField::make()->name('default'),
        FormField::make()->name('change_form'),
        FormField::make()->name('ext_form')->hidden(),
        FormField::make()->name('auto_required')->required(),
    ]));
});

test('support detail', function () {
    $presetsHelper = $this->presetsHelper
        ->withPresets([
            'default' => new PresetItem(),
            'change_detail' => new PresetItem(
                detail: fn(string $key) => DetailAttribute::make()->name($key),
            ),
            'ext_detail' => new PresetItem(
                detailExt: fn(DetailAttribute $attribute) => $attribute->typeImage(),
            ),
            'hidden' => new PresetItem(
                detail: null,
            ),
            'hidden2' => new PresetItem(
                detail: false,
            ),
        ]);
    expect(components_to_array($presetsHelper->pickDetail()))->toBe(components_to_array([
        DetailAttribute::make()->name('default'),
        DetailAttribute::make()->name('change_detail'),
        DetailAttribute::make()->name('ext_detail')->typeImage(),
    ]));
});

test('support rule', function () {
    $presetsHelper = $this->presetsHelper
        ->withPresets([
            'default' => new PresetItem(),
            'change_rule' => new PresetItem(
                rule: 'required',
            ),
            'change_rule2' => new PresetItem(
                rule: 'required|string',
            ),
            'change_rule3' => new PresetItem(
                rule: ['required', 'string'],
            ),
            'callback_rule' => new PresetItem(
                rule: fn() => 'required',
            ),
            'hidden' => new PresetItem(
                rule: null,
            ),
        ]);
    expect($presetsHelper->pickRules())->toBe([
        'default' => ['nullable'],
        'change_rule' => ['required'],
        'change_rule2' => ['required', 'string'],
        'change_rule3' => ['required', 'string'],
        'callback_rule' => ['required'],
    ]);
});

test('support rule Scene add sometimes rule', function () {
    $presetsHelper = $this->presetsHelper
        ->withPresets([
            'default' => new PresetItem(),
            'change_rule' => new PresetItem(
                rule: 'required',
            ),
            'change_rule2' => new PresetItem(
                rule: 'sometimes|required',
            ),
        ]);
    expect($presetsHelper->withScene(RepositoryInterface::SCENE_UPDATE)->pickRules())->toBe([
        'default' => ['sometimes', 'nullable'],
        'change_rule' => ['sometimes', 'required'],
        'change_rule2' => ['sometimes', 'required'],
    ]);
});

test('support ruleMessages', function () {
    $presetsHelper = $this->presetsHelper
        ->withPresets([
            'default_hidden' => new PresetItem(),
            'change_ruleMessages' => new PresetItem(
                ruleMessages: ['required' => 'abc'],
            ),
            'callback_ruleMessages' => new PresetItem(
                ruleMessages: fn() => ['required' => 'abc'],
            ),
        ]);
    expect($presetsHelper->pickRuleMessages())->toBe([
        'change_ruleMessages' => ['required' => 'abc'],
        'callback_ruleMessages' => ['required' => 'abc'],
    ]);
});

test('support ruleCustomAttribute', function () {
    $presetsHelper = $this->presetsHelper
        ->withPresets([
            'default_hidden' => new PresetItem(),
            'change_ruleCustomAttribute' => new PresetItem(
                ruleCustomAttribute: 'abc',
            ),
            'callback_ruleCustomAttribute' => new PresetItem(
                ruleCustomAttribute: fn() => 'abc',
            ),
        ]);
    expect($presetsHelper->pickRuleCustomAttributes())->toBe([
        'change_ruleCustomAttribute' => 'abc',
        'callback_ruleCustomAttribute' => 'abc',
    ]);
});

test('support selectOptions', function () {
    $presetsHelper = $this->presetsHelper->withPresets([
        'id' => new PresetItem(
            selectOptions: ['a' => 'A', 'b' => 'B'],
        )
    ]);
    expect(components_to_array($presetsHelper->pickGrid()))
        ->toBe(components_to_array([
            GridColumn::make()->name('id')->typeMapping(['map' => [
                ['label' => 'A', 'value' => 'a'],
                ['label' => 'B', 'value' => 'b'],
            ]])->searchable()
        ]))
        ->and(components_to_array($presetsHelper->pickForm()))
        ->toBe(components_to_array([
            FormField::make()->name('id')->typeSelect(['options' => [
                ['value' => 'a', 'label' => 'A'],
                ['value' => 'b', 'label' => 'B'],
            ]]),
        ]))
        ->and(components_to_array($presetsHelper->pickDetail()))
        ->toBe(components_to_array([
            DetailAttribute::make()->name('id')->typeMapping(['map' => [
                ['label' => 'A', 'value' => 'a'],
                ['label' => 'B', 'value' => 'b'],
            ]]),
        ]));
});

test('support formDefaultValue', function () {
    $presetsHelper = $this->presetsHelper->withPresets([
        'id' => new PresetItem(
            formDefaultValue: 1,
        )
    ]);

    expect(components_to_array($presetsHelper->pickForm()))->toBe(components_to_array([
        FormField::make()->name('id')->value('1'),
    ]));
});

test('support pick special keys', function () {
    $presetsHelper = $this->presetsHelper
        ->withPresets([
            'id' => new PresetItem(label: 'ID'),
            'code' => new PresetItem(label: 'Code'),
        ]);
    expect($presetsHelper->pickLabel())->toBe(['id' => 'ID', 'code' => 'Code'])
        ->and($presetsHelper->pickLabel(['id']))->toBe(['id' => 'ID']);
});

test('support pickForm multi field', function () {
    $presetsHelper = $this->presetsHelper->withPresets([
        'id' => new PresetItem(
            form: fn(string $column) => FormField::make()->name($column),
        ),
        'code' => new PresetItem(
            form: fn(string $column) => [
                FormField::make()->name($column . '1'),
                FormField::make()->name($column . '2'),
            ],
        ),
    ]);
    expect(components_to_array($presetsHelper->pickForm()))->toBe(components_to_array([
        FormField::make()->name('id'),
        FormField::make()->name('code1'),
        FormField::make()->name('code2'),
    ]));
});

test('support extDynamic', function () {
    $presetsHelper = $this->presetsHelper->withPresets([
        'form_useSceneDynamic' => new PresetItem(
            formExtDynamic: fn(FormField $field, string $scene) => $field->required($scene === 'create'),
        ),
        'rule_useSceneDynamic' => new PresetItem(
            ruleExtDynamic: fn(array $rule, string $scene) => array_values(array_filter([
                $scene === 'create' ? 'required' : null,
                'string',
            ])),
        )
    ]);

    expect($presetsHelper->withScene()->pickForm()[0]->get('required'))->toBeFalse()
        ->and($presetsHelper->withScene('create')->pickForm()[0]->get('required'))->toBeTrue()
        ->and($presetsHelper->withScene()->pickRules()['rule_useSceneDynamic'])->toBe(['string'])
        ->and($presetsHelper->withScene('create')->pickRules()['rule_useSceneDynamic'])->toBe(['required', 'string'])
        ->and($presetsHelper->withScene()->pickForm()[1]->get('required'))->toBeFalse()
        ->and($presetsHelper->withScene('create')->pickForm()[1]->get('required'))->toBeTrue();
});

test('ext and extDynamic compare', function () {
    $globalValue = new stdClass();
    $globalValue->value = '123';
    $presetsHelper = $this->presetsHelper->withPresets([
        'key_noDynamic' => new PresetItem(
            gridExt: fn(GridColumn $column) => $column->width($globalValue->value),
            formExt: fn(FormField $field) => $field->value($globalValue->value),
            detailExt: fn(DetailAttribute $attribute) => $attribute->value($globalValue->value),
        ),
        'key_useDynamic' => new PresetItem(
            gridExtDynamic: fn(GridColumn $column, string $scene) => $column->width($globalValue->value),
            formExtDynamic: fn(FormField $field, string $scene) => $field->value($globalValue->value),
            detailExtDynamic: fn(DetailAttribute $attribute, string $scene) => $attribute->value($globalValue->value),
        ),
    ]);
    $grids = $presetsHelper->pickGrid();
    $forms = $presetsHelper->pickForm();
    $details = $presetsHelper->pickDetail();
    expect($grids[0]->get('width'))->toBe('123')
        ->and($forms[0]->get('value'))->toBe('123')
        ->and($details[0]->get('value'))->toBe('123')
        ->and($grids[1]->get('width'))->toBe('123')
        ->and($forms[1]->get('value'))->toBe('123')
        ->and($details[1]->get('value'))->toBe('123');

    $globalValue->value = '456'; // 修改值，仅 ExtDynamic 的才会变
    $grids = $presetsHelper->pickGrid();
    $forms = $presetsHelper->pickForm();
    $details = $presetsHelper->pickDetail();
    expect($grids[0]->get('width'))->toBe('123')
        ->and($forms[0]->get('value'))->toBe('123')
        ->and($details[0]->get('value'))->toBe('123')
        ->and($grids[1]->get('width'))->toBe('456')
        ->and($forms[1]->get('value'))->toBe('456')
        ->and($details[1]->get('value'))->toBe('456');
});
