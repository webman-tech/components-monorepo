<?php

namespace WebmanTech\DTO\Integrations;

use WebmanTech\DTO\Exceptions\DTOValidateException;

interface ValidatorInterface
{
    /**
     * 验证
     * @param array<string, mixed> $data 数据
     * @param array<string, array> $rules 验证规则
     * @param array<string, string> $messages 错误消息
     * @param array<string, string> $customAttributes 自定义属性名
     * @return array<string, mixed> 验证后数据
     * @throws DTOValidateException 验证失败时抛出
     */
    public function validate(array $data = [], array $rules = [], array $messages = [], array $customAttributes = []): array;
}
