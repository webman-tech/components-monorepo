<?php

namespace WebmanTech\DTO\Integrations;

interface RequestInterface
{
    /**
     * 获取请求方法，必须是大写的
     */
    public function getMethod(): string;

    /**
     * 获取请求类型，全部转为小写
     */
    public function getContentType(): string;

    /**
     * 获取 query 上的某个参数
     */
    public function get(string $key): null|string|array;

    /**
     * 获取 path 上的某个参数
     */
    public function path(string $key): null|string;

    /**
     * 获取 header 上的某个参数，如果有多个值，只返回第一个
     */
    public function header(string $key): ?string;

    /**
     * 获取 cookie 上的某个参数
     */
    public function cookie(string $name): ?string;

    /**
     * 获取 body 上的原始内容
     */
    public function rawBody(): string;

    /**
     * 获取 post form 上的某个参数
     */
    public function postForm(string $key): null|string|array|object;

    /**
     * 获取 post json 上的某个参数
     */
    public function postJson(string $key): null|string|int|float|bool|array;

    /**
     * 获取 query 上的所有参数
     * @return array<string, string|array>
     */
    public function allGet(): array;

    /**
     * 获取 post 上的所有 form 参数
     * @return array<string, string|array>
     */
    public function allPostForm(): array;

    /**
     * 获取 post 上的所有 json 参数
     * @return array<string, string|array>
     */
    public function allPostJson(): array;
}
