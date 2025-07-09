<?php

namespace WebmanTech\DTO\Integrations;

interface RequestInterface
{
    /**
     * 获取 query 上的某个参数
     */
    public function get(string $key): null|string|array;

    /**
     * 获取 query 上的所有参数
     * @return array<string, string|array>
     */
    public function getAll(): array;

    /**
     * 获取 post 上的某个参数
     */
    public function post(string $key): null|string|array;

    /**
     * 获取 post 上的所有参数
     * @return array<string, string|array>
     */
    public function postAll(): array;

    /**
     * 获取 header 上的某个参数，如果有多个值，只返回第一个
     */
    public function header(string $key): ?string;

    /**
     * 获取 header 上的所有参数，如果相同 key 有多个值，只返回 第一个，key 必须是小写
     * @return array<string, string>
     */
    public function headerAll(): array;

    /**
     * 获取 body 上的原始内容
     */
    public function rawBody(): string;
}
