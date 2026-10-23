<?php

use WebmanTech\DTO\Attributes\DataDecrypt;
use WebmanTech\DTO\Attributes\FromDataConfig;
use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\BaseDTO;
use WebmanTech\DTO\BaseRequestDTO;
use WebmanTech\DTO\DataDecrypterInterface;
use WebmanTech\DTO\Exceptions\DTOProcessorException;
use WebmanTech\DTO\Helper\ConfigHelper;
use WebmanTech\DTO\Processor\DecryptFields;
use WebmanTech\DTO\Processor\FromDataContext;
use WebmanTech\DTO\Processor\FromDataPipeline;
use WebmanTech\DTO\Processor\ProcessorInterface;
use WebmanTech\DTO\Processor\SanitizeData;
use WebmanTech\DTO\Processor\ValidateData;

afterEach(function () {
    ConfigHelper::setForTest();
});

test('pipeline: 默认处理顺序为字段解密、清洗、验证', function () {
    $classes = array_map(
        static fn(ProcessorInterface $processor): string => $processor::class,
        FromDataPipeline::create()->processors(),
    );

    expect($classes)->toBe([
        DecryptFields::class,
        SanitizeData::class,
        ValidateData::class,
    ]);
});

test('DataDecrypt: 只解密带注解的字段及其数组元素', function () {
    final class SelectiveReverseDecrypter implements DataDecrypterInterface
    {
        public function decrypt(string $ciphertext, string $fieldName): string
        {
            return "{$fieldName}:" . strrev($ciphertext);
        }
    }

    ConfigHelper::setForTest('dto.data_decrypter', new SelectiveReverseDecrypter());

    final class SelectiveDecryptDTOTest extends BaseRequestDTO
    {
        public string $plain;

        #[DataDecrypt]
        public string $phone;

        /** @var string[] */
        #[DataDecrypt]
        public array $tags;
    }

    $dto = SelectiveDecryptDTOTest::fromData([
        'plain' => 'hello',
        'phone' => '123',
        'tags' => ['abc', 'def'],
    ]);
    $repeatedDto = SelectiveDecryptDTOTest::fromData([
        'plain' => 'world',
        'phone' => '456',
        'tags' => ['ghi'],
    ]);

    expect($dto->plain)->toBe('hello')
        ->and($dto->phone)->toBe('phone:321')
        ->and($dto->tags)->toBe(['tags:cba', 'tags:fed'])
        ->and($repeatedDto->plain)->toBe('world')
        ->and($repeatedDto->phone)->toBe('phone:654')
        ->and($repeatedDto->tags)->toBe(['tags:ihg']);
});

test('DataDecrypt: 解密后再清洗和验证', function () {
    final class EmailReverseDecrypter implements DataDecrypterInterface
    {
        public function decrypt(string $ciphertext, string $fieldName): string
        {
            return strrev($ciphertext);
        }
    }

    ConfigHelper::setForTest('dto.data_decrypter', EmailReverseDecrypter::class);

    #[FromDataConfig(trim: true)]
    final class DecryptThenValidateDTOTest extends BaseRequestDTO
    {
        #[DataDecrypt]
        #[ValidationRules(rules: 'email')]
        public string $email;
    }

    $dto = DecryptThenValidateDTOTest::fromData([
        'email' => '  moc.elpmaxe@resu  ',
    ]);

    expect($dto->email)->toBe('user@example.com');
});

test('DataDecrypt: 未配置解密器时给出字段上下文', function () {
    final class MissingDecrypterDTOTest extends BaseRequestDTO
    {
        #[DataDecrypt]
        public string $secret;
    }

    try {
        MissingDecrypterDTOTest::fromData(['secret' => 'ciphertext']);
        throw new LogicException('should not reach here');
    } catch (DTOProcessorException $e) {
        expect($e->processor)->toBe(DecryptFields::class)
            ->and($e->fieldName)->toBe('secret')
            ->and($e->getPrevious())->toBeInstanceOf(InvalidArgumentException::class)
            ->and($e->getPrevious()->getMessage())->toBe('dto.data_decrypter is not configured');
    }
});

test('DataDecrypt: 嵌套 DTO 由自己的处理器解密', function () {
    final class NestedReverseDecrypter implements DataDecrypterInterface
    {
        public function decrypt(string $ciphertext, string $fieldName): string
        {
            return strrev($ciphertext);
        }
    }

    ConfigHelper::setForTest('dto.data_decrypter', new NestedReverseDecrypter());

    final class NestedDecryptItemDTOTest extends BaseDTO
    {
        #[DataDecrypt]
        public string $secret;
    }

    final class NestedDecryptParentDTOTest extends BaseRequestDTO
    {
        public NestedDecryptItemDTOTest $item;
    }

    $dto = NestedDecryptParentDTOTest::fromData([
        'item' => ['secret' => 'abcdef'],
    ]);

    expect($dto->item->secret)->toBe('fedcba');
});

test('pipeline: 自定义 processor 可插到字段解密前处理整体数据', function () {
    final class UnwrapPayloadProcessor implements ProcessorInterface
    {
        public function __invoke(FromDataContext $context): void
        {
            expect($context->dtoClass)->toBe(EncryptedPayloadDTOTest::class);
            $context->data = ['email' => $context->data['payload']];
        }
    }

    final class PayloadReverseDecrypter implements DataDecrypterInterface
    {
        public function decrypt(string $ciphertext, string $fieldName): string
        {
            return strrev($ciphertext);
        }
    }

    ConfigHelper::setForTest('dto.data_decrypter', new PayloadReverseDecrypter());

    #[FromDataConfig(trim: true)]
    final class EncryptedPayloadDTOTest extends BaseRequestDTO
    {
        #[DataDecrypt]
        #[ValidationRules(rules: 'email')]
        public string $email;

        protected static function configureFromDataPipeline(FromDataPipeline $pipeline): void
        {
            $pipeline->insert(new UnwrapPayloadProcessor(), before: DecryptFields::class);
        }
    }

    $dto = EncryptedPayloadDTOTest::fromData([
        'payload' => '  moc.elpmaxe@resu  ',
    ]);

    expect($dto->email)->toBe('user@example.com');
});

test('pipeline: validate=false 只跳过验证阶段', function () {
    final class NoValidateReverseDecrypter implements DataDecrypterInterface
    {
        public function decrypt(string $ciphertext, string $fieldName): string
        {
            return strrev($ciphertext);
        }
    }

    ConfigHelper::setForTest('dto.data_decrypter', new NoValidateReverseDecrypter());

    #[FromDataConfig(trim: true)]
    final class PipelineWithoutValidationDTOTest extends BaseRequestDTO
    {
        #[DataDecrypt]
        public string $name;
    }

    $dto = PipelineWithoutValidationDTOTest::fromData(['name' => '  ecila  '], validate: false);

    expect($dto->name)->toBe('alice');
});

test('pipeline: 验证阶段保留 BaseDTO 的 protected 扩展点', function () {
    final class CustomValidationHookDTOTest extends BaseDTO
    {
        public string $name;

        protected static function validateData(array $data, array $rules): array
        {
            $data['name'] = 'validated-by-hook';

            return $data;
        }
    }

    $dto = CustomValidationHookDTOTest::fromData(['name' => 'input']);

    expect($dto->name)->toBe('validated-by-hook');
});

test('pipeline: 处理器异常统一包装', function () {
    final class FailingPipelineProcessor implements ProcessorInterface
    {
        public function __invoke(FromDataContext $context): void
        {
            throw new RuntimeException('decrypt failed');
        }
    }

    final class FailingPipelineDTOTest extends BaseRequestDTO
    {
        public string $name;

        protected static function configureFromDataPipeline(FromDataPipeline $pipeline): void
        {
            $pipeline->insert(new FailingPipelineProcessor(), before: ValidateData::class);
        }
    }

    try {
        FailingPipelineDTOTest::fromData(['name' => 'alice']);
        throw new LogicException('should not reach here');
    } catch (DTOProcessorException $e) {
        expect($e->processor)->toBe(FailingPipelineProcessor::class)
            ->and($e->fieldName)->toBeNull()
            ->and($e->getPrevious())->toBeInstanceOf(RuntimeException::class)
            ->and($e->getPrevious()->getMessage())->toBe('decrypt failed');
    }
});

test('pipeline: add insert remove 可组合调整处理顺序', function () {
    final class InjectNameProcessor implements ProcessorInterface
    {
        public function __invoke(FromDataContext $context): void
        {
            $context->data['name'] = '  injected  ';
        }
    }

    final class AppendNameProcessor implements ProcessorInterface
    {
        public function __invoke(FromDataContext $context): void
        {
            $context->data['name'] .= '-after';
        }
    }

    #[FromDataConfig(trim: true)]
    final class MutablePipelineDTOTest extends BaseRequestDTO
    {
        public string $name;

        protected static function configureFromDataPipeline(FromDataPipeline $pipeline): void
        {
            $pipeline
                ->remove(DecryptFields::class)
                ->insert(new InjectNameProcessor(), before: SanitizeData::class)
                ->add(new AppendNameProcessor());
        }
    }

    $dto = MutablePipelineDTOTest::fromData(['name' => 'unused']);

    expect($dto->name)->toBe('injected-after');
});

test('pipeline: 自定义处理器可读取 FromDataConfig custom', function () {
    final class FromDataCustomConfigProcessor implements ProcessorInterface
    {
        public function __invoke(FromDataContext $context): void
        {
            $context->data['name'] = ($context->config?->custom['prefix'] ?? '') . $context->data['name'];
        }
    }

    #[FromDataConfig(custom: ['prefix' => 'custom-'])]
    final class FromDataCustomConfigDTOTest extends BaseDTO
    {
        public string $name;

        protected static function configureFromDataPipeline(FromDataPipeline $pipeline): void
        {
            $pipeline->insert(new FromDataCustomConfigProcessor(), before: ValidateData::class);
        }
    }

    expect(FromDataCustomConfigDTOTest::fromData(['name' => 'value'])->name)->toBe('custom-value');
});

test('FromDataConfig: 全局配置支持 custom', function () {
    ConfigHelper::setForTest('dto.from_data_config.request', [
        'custom' => ['prefix' => 'global-'],
    ]);
    ConfigHelper::setForTest('dto.from_data_config.base', [
        'custom' => ['prefix' => 'base-'],
    ]);

    expect(FromDataConfig::createForRequestDTO()->custom)->toBe([
        'prefix' => 'global-',
    ])->and(FromDataConfig::createForBaseDTO()->custom)->toBe([
        'prefix' => 'base-',
    ]);
});

test('pipeline: 配置可完整替换默认处理链', function () {
    final class ConfiguredOnlyProcessor implements ProcessorInterface
    {
        public function __invoke(FromDataContext $context): void
        {
            $context->data = ['name' => 'from-config'];
        }
    }

    ConfigHelper::setForTest('dto.from_data_processors', [ConfiguredOnlyProcessor::class]);

    final class ConfiguredPipelineDTOTest extends BaseRequestDTO
    {
        public string $name;
    }

    expect(ConfiguredPipelineDTOTest::fromData(['unused' => true])->name)->toBe('from-config');
});
