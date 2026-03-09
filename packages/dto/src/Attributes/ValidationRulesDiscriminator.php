<?php

namespace WebmanTech\DTO\Attributes;

use BackedEnum;
use WebmanTech\DTO\BaseDTO;

final class ValidationRulesDiscriminator
{
    /**
     * @param string $property 关联的字段
     * @param array<array-key, class-string<BaseDTO>> $mapping 映射关系
     */
    public function __construct(
        public string $property,
        public array  $mapping,
    )
    {
    }

    public static function fromData(array|self $data): self
    {
        if ($data instanceof self) {
            return $data;
        }

        return new self(
            property: $data['property'],
            mapping: $data['mapping'],
        );
    }

    public function toArray(): array
    {
        return [
            'property' => $this->property,
            'mapping' => $this->mapping,
        ];
    }

    /**
     * 根据 discriminator 从上下文中创建对应的 DTO 实例
     *
     * @param mixed $value 原始值
     * @param array $context 上下文数据
     * @param bool $nullable 是否可为空
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function makeValueFromContext(mixed $value, array $context, bool $nullable = false): mixed
    {
        // 从上下文中获取 discriminator 字段的值
        $discriminatorValue = $context[$this->property] ?? null;

        if ($discriminatorValue === null) {
            throw new \InvalidArgumentException(
                sprintf('Discriminator property "%s" not found in context', $this->property)
            );
        }

        // 如果 discriminatorValue 是枚举，转换为值
        if ($discriminatorValue instanceof BackedEnum) {
            $discriminatorValue = $discriminatorValue->value;
        }

        // 根据 discriminator 值找到对应的 DTO 类
        $dtoClass = $this->mapping[$discriminatorValue] ?? null;

        if ($dtoClass === null) {
            throw new \InvalidArgumentException(
                sprintf('Discriminator value "%s" not found in mapping', $discriminatorValue)
            );
        }

        // 验证 DTO 类存在
        if (!class_exists($dtoClass)) {
            throw new \InvalidArgumentException(
                sprintf('Discriminator mapping class "%s" does not exist', $dtoClass)
            );
        }

        // 验证是 BaseDTO 的子类
        // @phpstan-ignore-next-line
        if (!is_a($dtoClass, BaseDTO::class, true)) {
            throw new \InvalidArgumentException(
                sprintf('Discriminator mapping class "%s" must extend BaseDTO', $dtoClass)
            );
        }

        // 检查值是否为数组
        if (!is_array($value)) {
            throw new \InvalidArgumentException(
                sprintf('Discriminator value must be array, got %s', gettype($value))
            );
        }

        // 空数组且字段可空时，返回 null
        if ($value === [] && $nullable) {
            return null;
        }

        // 使用对应的 DTO 类创建实例
        return $dtoClass::fromData($value, validate: false);
    }
}
