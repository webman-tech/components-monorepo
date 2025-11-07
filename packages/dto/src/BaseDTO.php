<?php

namespace WebmanTech\DTO;

use BackedEnum;
use DateTime;
use DateTimeInterface;
use stdClass;
use WebmanTech\DTO\Attributes\ToArrayConfig;
use WebmanTech\DTO\Exceptions\DTONewInstanceException;
use WebmanTech\DTO\Exceptions\DTOValidateException;
use WebmanTech\DTO\Helper\ArrayHelper;
use WebmanTech\DTO\Helper\ConfigHelper;
use WebmanTech\DTO\Integrations\Validation;
use WebmanTech\DTO\Reflection\ReflectionReaderFactory;

class BaseDTO
{
    /**
     * 根据 data 构建实例
     * @throws DTONewInstanceException|DTOValidateException
     */
    public static function fromData(array $data, bool $validate = true): static
    {
        $factory = ReflectionReaderFactory::fromClass(static::class);

        if ($validate) {
            // 必须的规则
            $rules = static::getValidationRules();
            $data = static::validateData($data, $rules);
        }

        try {
            return $factory->newInstanceByData($data);
        } catch (\Throwable $e) {
            throw new DTONewInstanceException(static::class, $e);
        }
    }

    /**
     * 获取全部的验证规则
     * @return array<string, array>
     */
    public static function getValidationRules(): array
    {
        // 必须的规则
        $rules = ReflectionReaderFactory::fromClass(static::class)->getPropertiesValidationRules();
        if ($extraRules = static::getExtraValidationRules()) {
            // 合并自定义规则
            foreach ($extraRules as $key => $keyRules) {
                if (is_string($keyRules)) {
                    $keyRules = explode('|', $keyRules);
                }
                $keyRules = ArrayHelper::wrap($keyRules);
                if (!isset($rules[$key])) {
                    $rules[$key] = $keyRules;
                } else {
                    $allRules = array_merge($rules[$key], $keyRules);
                    $uniqueAllRules = [];
                    foreach ($allRules as $rule) {
                        if (is_string($rule) && in_array($rule, $uniqueAllRules, true)) {
                            continue;
                        }
                        $uniqueAllRules[] = $rule;
                    }
                    $rules[$key] = $uniqueAllRules;
                }
            }
        }

        return $rules;
    }

    /**
     * 获取额外的验证规则
     * @return array<string, array|string>
     */
    protected static function getExtraValidationRules(): array
    {
        return [];
    }

    /**
     * 验证规则的错误信息
     * @return array<string, string>
     */
    protected static function getValidationRuleMessages(): array
    {
        return [];
    }

    /**
     * 验证规则的自定义属性
     * @return array<string, string>
     */
    protected static function getValidationRuleCustomAttributes(): array
    {
        return [];
    }

    /**
     * 验证数据
     * @param array<string, mixed> $data
     * @return array<string, mixed> 验证过的数据
     * @throws DTOValidateException
     */
    protected static function validateData(array $data, array $rules): array
    {
        if (!$rules) {
            return $data;
        }
        return Validation::create()->validate($data, $rules, static::getValidationRuleMessages(), static::getValidationRuleCustomAttributes());
    }

    /**
     * 日期格式
     */
    protected function getDateTimeFormat(): string
    {
        return ConfigHelper::get('dto.to_array_default_datetime_format', DateTimeInterface::ATOM);
    }

    /**
     * 转为数组
     * @return array<string, mixed>|mixed
     */
    public function toArray(?ToArrayConfig $toArrayConfig = null): mixed
    {
        $data = [];

        $factory = ReflectionReaderFactory::fromClass($this);

        $toArrayConfig ??= $factory->getPropertiesToArrayConfig() ?? new ToArrayConfig();

        if ($toArrayConfig->singleKey) {
            $data = $this->{$toArrayConfig->singleKey};
            if ($data instanceof self) {
                $data = $data->toArray();
            }
            return $data;
        }

        if ($toArrayConfig->only) {
            $properties = $toArrayConfig->only;
        } else {
            $properties = $factory->getPropertyNameList();
            if ($toArrayConfig->include) {
                $properties = array_merge($properties, $toArrayConfig->include);
            }
            if ($toArrayConfig->exclude) {
                $properties = array_diff($properties, $toArrayConfig->exclude);
            }
        }
        foreach ($properties as $property) {
            $value = $this->{$property};
            if (is_array($value)) {
                $value = array_map(function ($item) {
                    if ($item instanceof self) {
                        return $item->toArray();
                    }
                    return $item;
                }, $value);
                if ($toArrayConfig->ignoreNull) {
                    $value = array_filter($value, fn($v) => $v !== null);
                }
                if (
                    $toArrayConfig->emptyArrayAsObject === true
                    || (
                        is_array($toArrayConfig->emptyArrayAsObject)
                        && in_array($property, $toArrayConfig->emptyArrayAsObject, true)
                    )
                ) {
                    $value = $value ?: new stdClass();
                }
            } elseif ($value instanceof self) {
                $value = $value->toArray();
                if ($toArrayConfig->ignoreNull) {
                    $value = array_filter($value, fn($v) => $v !== null);
                }
            } elseif ($value instanceof DateTime) {
                $value = $value->format($this->getDateTimeFormat());
            } elseif ($value instanceof BackedEnum) {
                $value = $value->value;
            }
            if ($toArrayConfig->ignoreNull && $value === null) {
                continue;
            }
            $data[$property] = $value;
        }

        return $data;
    }
}
