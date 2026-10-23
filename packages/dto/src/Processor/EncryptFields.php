<?php

namespace WebmanTech\DTO\Processor;

use InvalidArgumentException;
use WebmanTech\DTO\Attributes\DataEncrypt;
use WebmanTech\DTO\DataEncrypterInterface;
use WebmanTech\DTO\Exceptions\DTOProcessorException;
use WebmanTech\DTO\Helper\ConfigHelper;

/**
 * 加密带有 #[DataEncrypt] 注解的字段
 */
final class EncryptFields implements ToArrayProcessorInterface
{
    private ?DataEncrypterInterface $encrypter = null;

    public function __invoke(ToArrayContext $context): void
    {
        if (!is_array($context->data)) {
            return;
        }
        $fields = $context->reflection->getAttributionList(DataEncrypt::class);
        foreach (array_keys($fields) as $field) {
            if (!array_key_exists($field, $context->data)) {
                continue;
            }
            $context->data[$field] = $this->encrypt($context->data[$field], $field);
        }
    }

    private function encrypt(mixed $value, string $field): mixed
    {
        try {
            if (is_array($value)) {
                return array_map(
                    fn(mixed $item): mixed => is_string($item) ? $this->encrypter()->encrypt($item, $field) : $item,
                    $value,
                );
            }

            return is_string($value) ? $this->encrypter()->encrypt($value, $field) : $value;
        } catch (\Throwable $e) {
            throw new DTOProcessorException(self::class, $field, $e);
        }
    }

    private function encrypter(): DataEncrypterInterface
    {
        if ($this->encrypter) {
            return $this->encrypter;
        }

        $encrypter = ConfigHelper::get('dto.data_encrypter');
        if ($encrypter instanceof DataEncrypterInterface) {
            return $this->encrypter = $encrypter;
        }
        if (is_string($encrypter) && is_a($encrypter, DataEncrypterInterface::class, true)) {
            return $this->encrypter = new $encrypter();
        }

        throw new InvalidArgumentException('dto.data_encrypter is not configured');
    }
}
