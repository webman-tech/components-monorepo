<?php

use WebmanTech\DTO\Attributes\DataDecrypt;
use WebmanTech\DTO\Attributes\DataEncrypt;
use WebmanTech\DTO\Attributes\ToArrayConfig;
use WebmanTech\DTO\BaseDTO;
use WebmanTech\DTO\DataDecrypterInterface;
use WebmanTech\DTO\DataEncrypterInterface;
use WebmanTech\DTO\Exceptions\DTOProcessorException;
use WebmanTech\DTO\Helper\ConfigHelper;
use WebmanTech\DTO\Processor\ConvertEmptyArraysToObjects;
use WebmanTech\DTO\Processor\EncryptFields;
use WebmanTech\DTO\Processor\ExtractSingleKey;
use WebmanTech\DTO\Processor\FilterNullValues;
use WebmanTech\DTO\Processor\SelectProperties;
use WebmanTech\DTO\Processor\SerializeProperties;
use WebmanTech\DTO\Processor\ToArrayContext;
use WebmanTech\DTO\Processor\ToArrayPipeline;
use WebmanTech\DTO\Processor\ToArrayProcessorInterface;

afterEach(function () {
    ConfigHelper::setForTest();
});

test('toArray pipeline: 默认处理序列化和字段加密', function () {
    $classes = array_map(
        static fn(ToArrayProcessorInterface $processor): string => $processor::class,
        ToArrayPipeline::create()->processors(),
    );

    expect($classes)->toBe([
        SelectProperties::class,
        SerializeProperties::class,
        FilterNullValues::class,
        ConvertEmptyArraysToObjects::class,
        EncryptFields::class,
        ExtractSingleKey::class,
    ]);
});

test('DataEncrypt: 只加密带注解的字段及其数组元素', function () {
    final class SelectiveReverseEncrypter implements DataEncrypterInterface
    {
        public function encrypt(string $plaintext, string $fieldName): string
        {
            return "{$fieldName}:" . strrev($plaintext);
        }
    }

    ConfigHelper::setForTest('dto.data_encrypter', new SelectiveReverseEncrypter());

    final class SelectiveEncryptDTOTest extends BaseDTO
    {
        public string $plain = 'hello';

        #[DataEncrypt]
        public string $phone = '123';

        /** @var string[] */
        #[DataEncrypt]
        public array $tags = ['abc', 'def'];
    }

    $dto = new SelectiveEncryptDTOTest();

    expect($dto->toArray())->toBe([
        'plain' => 'hello',
        'phone' => 'phone:321',
        'tags' => ['tags:cba', 'tags:fed'],
    ]);
});

test('DataEncrypt: 与 DataDecrypt 支持字段往返', function () {
    final class ReverseDataCryptor implements DataEncrypterInterface, DataDecrypterInterface
    {
        public function encrypt(string $plaintext, string $fieldName): string
        {
            return strrev($plaintext);
        }

        public function decrypt(string $ciphertext, string $fieldName): string
        {
            return strrev($ciphertext);
        }
    }

    $cryptor = new ReverseDataCryptor();
    ConfigHelper::setForTest('dto.data_encrypter', $cryptor);
    ConfigHelper::setForTest('dto.data_decrypter', $cryptor);

    final class RoundTripEncryptDTOTest extends BaseDTO
    {
        #[DataDecrypt]
        #[DataEncrypt]
        public string $secret;
    }

    $dto = RoundTripEncryptDTOTest::fromData(['secret' => 'terces']);

    expect($dto->secret)->toBe('secret')
        ->and($dto->toArray())->toBe(['secret' => 'terces']);
});

test('DataEncrypt: 嵌套 DTO 使用自己的管线且只加密一次', function () {
    final class NestedReverseEncrypter implements DataEncrypterInterface
    {
        public function encrypt(string $plaintext, string $fieldName): string
        {
            return "{$fieldName}:" . strrev($plaintext);
        }
    }

    ConfigHelper::setForTest('dto.data_encrypter', NestedReverseEncrypter::class);

    final class NestedEncryptItemDTOTest extends BaseDTO
    {
        #[DataEncrypt]
        public string $secret = 'abc';
    }

    final class NestedEncryptParentDTOTest extends BaseDTO
    {
        public NestedEncryptItemDTOTest $item;

        /** @var NestedEncryptItemDTOTest[] */
        public array $items;

        public function __construct()
        {
            $this->item = new NestedEncryptItemDTOTest();
            $this->items = [new NestedEncryptItemDTOTest()];
        }
    }

    expect((new NestedEncryptParentDTOTest())->toArray())->toBe([
        'item' => ['secret' => 'secret:cba'],
        'items' => [
            ['secret' => 'secret:cba'],
        ],
    ]);
});

test('DataEncrypt: singleKey 返回值仍会加密', function () {
    final class SingleKeyReverseEncrypter implements DataEncrypterInterface
    {
        public function encrypt(string $plaintext, string $fieldName): string
        {
            return "{$fieldName}:" . strrev($plaintext);
        }
    }

    ConfigHelper::setForTest('dto.data_encrypter', new SingleKeyReverseEncrypter());

    #[ToArrayConfig(singleKey: 'secret')]
    final class SingleKeyEncryptDTOTest extends BaseDTO
    {
        #[DataEncrypt]
        public string $secret = 'abc';
    }

    expect((new SingleKeyEncryptDTOTest())->toArray())->toBe('secret:cba');
});

test('toArray pipeline: emptyArrayAsObject 只处理原始数组属性', function () {
    final class EmptyNestedDTOTest extends BaseDTO
    {
    }

    #[ToArrayConfig(emptyArrayAsObject: true)]
    final class EmptyArraySourceDTOTest extends BaseDTO
    {
        public array $items = [];

        public EmptyNestedDTOTest $nested;

        public function __construct()
        {
            $this->nested = new EmptyNestedDTOTest();
        }
    }

    $data = (new EmptyArraySourceDTOTest())->toArray();

    expect($data['items'])->toBeInstanceOf(stdClass::class)
        ->and($data['nested'])->toBe([]);
});

test('DataEncrypt: 未配置加密器时给出字段上下文', function () {
    final class MissingEncrypterDTOTest extends BaseDTO
    {
        #[DataEncrypt]
        public string $secret = 'plaintext';
    }

    try {
        (new MissingEncrypterDTOTest())->toArray();
        throw new LogicException('should not reach here');
    } catch (DTOProcessorException $e) {
        expect($e->processor)->toBe(EncryptFields::class)
            ->and($e->fieldName)->toBe('secret')
            ->and($e->getPrevious())->toBeInstanceOf(InvalidArgumentException::class)
            ->and($e->getPrevious()->getMessage())->toBe('dto.data_encrypter is not configured');
    }
});

test('toArray pipeline: add insert remove 可组合调整处理顺序', function () {
    final class PrepareOutputProcessor implements ToArrayProcessorInterface
    {
        public function __invoke(ToArrayContext $context): void
        {
            expect($context->dto)->toBeInstanceOf(MutableToArrayPipelineDTOTest::class)
                ->and($context->config)->toBeInstanceOf(ToArrayConfig::class);
            $context->data = ['name' => 'prepared'];
        }
    }

    final class AppendOutputProcessor implements ToArrayProcessorInterface
    {
        public function __invoke(ToArrayContext $context): void
        {
            if (is_array($context->data)) {
                $context->data['name'] .= '-after';
            }
        }
    }

    final class MutableToArrayPipelineDTOTest extends BaseDTO
    {
        public string $name = 'unused';

        protected static function configureToArrayPipeline(ToArrayPipeline $pipeline): void
        {
            $pipeline
                ->remove(EncryptFields::class)
                ->add(new AppendOutputProcessor())
                ->insert(new PrepareOutputProcessor(), before: AppendOutputProcessor::class);
        }
    }

    expect((new MutableToArrayPipelineDTOTest())->toArray())->toBe(['name' => 'prepared-after']);
});

test('toArray pipeline: 配置可完整替换默认处理链', function () {
    final class ConfiguredToArrayProcessor implements ToArrayProcessorInterface
    {
        public function __invoke(ToArrayContext $context): void
        {
            $context->data = ['name' => 'from-config'];
        }
    }

    ConfigHelper::setForTest('dto.to_array_processors', [ConfiguredToArrayProcessor::class]);

    final class ConfiguredToArrayPipelineDTOTest extends BaseDTO
    {
        #[DataEncrypt]
        public string $name = 'unused';
    }

    expect((new ConfiguredToArrayPipelineDTOTest())->toArray())->toBe(['name' => 'from-config']);
});

test('toArray pipeline: 处理器异常统一包装', function () {
    final class FailingToArrayProcessor implements ToArrayProcessorInterface
    {
        public function __invoke(ToArrayContext $context): void
        {
            throw new RuntimeException('encrypt failed');
        }
    }

    final class FailingToArrayPipelineDTOTest extends BaseDTO
    {
        public string $name = 'alice';

        protected static function configureToArrayPipeline(ToArrayPipeline $pipeline): void
        {
            $pipeline->add(new FailingToArrayProcessor());
        }
    }

    try {
        (new FailingToArrayPipelineDTOTest())->toArray();
        throw new LogicException('should not reach here');
    } catch (DTOProcessorException $e) {
        expect($e->processor)->toBe(FailingToArrayProcessor::class)
            ->and($e->fieldName)->toBeNull()
            ->and($e->getPrevious())->toBeInstanceOf(RuntimeException::class)
            ->and($e->getPrevious()->getMessage())->toBe('encrypt failed');
    }
});
