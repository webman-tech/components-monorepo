<?php

namespace WebmanTech\DTO;

use DateTimeInterface;
use WebmanTech\DTO\Attributes\FromDataConfig;
use WebmanTech\DTO\Attributes\ToArrayConfig;
use WebmanTech\DTO\Exceptions\DTONewInstanceException;
use WebmanTech\DTO\Exceptions\DTOProcessorException;
use WebmanTech\DTO\Exceptions\DTOValidateException;
use WebmanTech\DTO\Helper\ArrayHelper;
use WebmanTech\DTO\Helper\ConfigHelper;
use WebmanTech\DTO\Integrations\Validation;
use WebmanTech\DTO\Integrations\ValidatorStopOnFirstFailureInterface;
use WebmanTech\DTO\Processor\FromDataContext;
use WebmanTech\DTO\Processor\FromDataPipeline;
use WebmanTech\DTO\Processor\ToArrayContext;
use WebmanTech\DTO\Processor\ToArrayPipeline;
use WebmanTech\DTO\Reflection\ReflectionClassReader;
use WebmanTech\DTO\Reflection\ReflectionReaderFactory;

class BaseDTO
{
    /**
     * 根据 data 构建实例
     *
     * 数据经 Processor 依次处理后实例化
     * @throws DTONewInstanceException|DTOValidateException|DTOProcessorException
     */
    public static function fromData(array $data, bool $validate = true): static
    {
        $factory = ReflectionReaderFactory::fromClass(static::class);

        $fromDataConfig = self::resolveFromDataConfig($factory);
        $context = new FromDataContext(
            data: $data,
            dtoClass: static::class,
            config: $fromDataConfig,
            validate: $validate,
            validator: static fn(array $data): array => static::validateData($data, static::getValidationRules()),
        );
        $pipeline = FromDataPipeline::create();
        static::configureFromDataPipeline($pipeline);
        $pipeline->process($context);

        try {
            return $factory->newInstanceByData($context->data);
        } catch (\Throwable $e) {
            throw new DTONewInstanceException(static::class, $e);
        }
    }

    /**
     * 获取全部的验证规则（包括嵌套 DTO 的所有规则）
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

        $fromDataConfig = self::resolveFromDataConfig();

        // 应用 bail 规则
        if ($fromDataConfig?->validatePropertiesAllWithBail) {
            foreach ($rules as $key => $keyRules) {
                // 如果第一位已经是 bail，跳过（ValidationRules 中已处理过）
                if (isset($keyRules[0]) && $keyRules[0] === 'bail') {
                    continue;
                }
                // 如果没有 bail，添加到开头
                array_unshift($rules[$key], 'bail');
            }
        }

        return $rules;
    }

    /**
     * 获取额外的验证规则
     *
     * 动态性边界：
     * - 顶层调用（本 DTO 作为规则树根执行 getValidationRules()，如顶层请求
     *   验证）时每请求实时执行，可返回随请求变化的动态值
     * - 作为嵌套 DTO 属性展开时，仅在父级规则树首次组装时执行一次，之后
     *   随规则树固化（进程 reload 前不再执行），应返回静态数组
     *
     * 嵌套字段需要动态规则时，在顶层 DTO 中以 dot-path 返回即可穿透嵌套，
     * 例如 ['address.postcode' => 'required|string']。
     *
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

        $validator = Validation::create();

        $fromDataConfig = self::resolveFromDataConfig();
        if ($fromDataConfig?->validateStopOnFirstFailure && $validator instanceof ValidatorStopOnFirstFailureInterface) {
            $validator = $validator->stopOnFirstFailure();
        }

        return $validator->validate($data, $rules, static::getValidationRuleMessages(), static::getValidationRuleCustomAttributes());
    }

    /**
     * 为当前 DTO 调整 fromData 处理器
     */
    protected static function configureFromDataPipeline(FromDataPipeline $pipeline): void
    {
    }

    /**
     * 为当前 DTO 调整 toArray 处理器
     */
    protected static function configureToArrayPipeline(ToArrayPipeline $pipeline): void
    {
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
     * @throws DTOProcessorException
     */
    public function toArray(?ToArrayConfig $toArrayConfig = null): mixed
    {
        $factory = ReflectionReaderFactory::fromClass($this);
        $toArrayConfig ??= $factory->getPropertiesToArrayConfig() ?? new ToArrayConfig();
        $context = new ToArrayContext(
            dto: $this,
            config: $toArrayConfig,
            reflection: $factory,
            propertyReader: fn(string $property): mixed => $this->{$property},
            dateTimeFormatResolver: fn(): string => $this->getDateTimeFormat(),
        );
        $pipeline = ToArrayPipeline::create();
        static::configureToArrayPipeline($pipeline);
        $pipeline->process($context);

        return $context->data;
    }

    /**
     * 解析 fromData 行为配置：类注解优先，否则按 DTO 类型取全局默认
     */
    private static function resolveFromDataConfig(?ReflectionClassReader $factory = null): ?FromDataConfig
    {
        $factory ??= ReflectionReaderFactory::fromClass(static::class);

        return $factory->getPropertiesFromDataConfig() ?? match (true) {
            is_subclass_of(static::class, BaseRequestDTO::class) => FromDataConfig::createForRequestDTO(),
            static::class === BaseDTO::class || is_subclass_of(static::class, BaseDTO::class) => FromDataConfig::createForBaseDTO(),
            default => null,
        };
    }
}
