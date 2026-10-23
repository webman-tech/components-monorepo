<?php

namespace WebmanTech\DTO\Processor;

use InvalidArgumentException;
use WebmanTech\DTO\Attributes\DataDecrypt;
use WebmanTech\DTO\DataDecrypterInterface;
use WebmanTech\DTO\Exceptions\DTOProcessorException;
use WebmanTech\DTO\Helper\ConfigHelper;
use WebmanTech\DTO\Reflection\ReflectionReaderFactory;

/**
 * 解密带有 #[DataDecrypt] 注解的字段
 */
final class DecryptFields implements ProcessorInterface
{
    private ?DataDecrypterInterface $decrypter = null;

    public function __invoke(FromDataContext $context): void
    {
        $fields = ReflectionReaderFactory::fromClass($context->dtoClass)->getAttributionList(DataDecrypt::class);
        foreach (array_keys($fields) as $field) {
            if (!array_key_exists($field, $context->data)) {
                continue;
            }

            try {
                $context->data[$field] = $this->decryptValue($context->data[$field], $field);
            } catch (\Throwable $e) {
                throw new DTOProcessorException(self::class, $field, $e);
            }
        }
    }

    private function decryptValue(mixed $value, string $field): mixed
    {
        if (is_array($value)) {
            return array_map(
                fn(mixed $item): mixed => is_string($item) ? $this->decrypter()->decrypt($item, $field) : $item,
                $value,
            );
        }

        return is_string($value) ? $this->decrypter()->decrypt($value, $field) : $value;
    }

    private function decrypter(): DataDecrypterInterface
    {
        if ($this->decrypter) {
            return $this->decrypter;
        }

        $decrypter = ConfigHelper::get('dto.data_decrypter');
        if ($decrypter instanceof DataDecrypterInterface) {
            return $this->decrypter = $decrypter;
        }
        if (is_string($decrypter) && is_a($decrypter, DataDecrypterInterface::class, true)) {
            return $this->decrypter = new $decrypter();
        }

        throw new InvalidArgumentException('dto.data_decrypter is not configured');
    }
}
