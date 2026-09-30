<?php

declare(strict_types=1);

use WebmanTech\CommonUtils\Cache\ArrayCache;
use WebmanTech\CommonUtils\Cache\NullCache;

/**
 * psr/simple-cache 各版本的接口签名差异：
 * - 1.0：参数、返回值均无类型声明
 * - 2.0：参数有类型声明，返回值无
 * - 3.0：参数、返回值均有类型声明
 *
 * composer.json 声明兼容 ^1.0|^2.0|^3.0，因此实现类：
 * - 参数不能声明具体类型（1.0 的接口参数无类型，收窄会因逆变违规导致 Fatal error）
 * - 返回值必须声明且与 3.0 接口一致（3.0 的接口要求返回类型）
 */
describe('PSR-16 version compatibility', function () {
    it('keeps cache methods compatible with psr/simple-cache 1.0/2.0/3.0', function (string $class) {
        $cache = new \ReflectionClass($class);
        $interface = new \ReflectionClass(\Psr\SimpleCache\CacheInterface::class);

        $violations = [];
        foreach ($interface->getMethods() as $interfaceMethod) {
            $method = $cache->getMethod($interfaceMethod->getName());

            // 参数必须无类型（或等价的 mixed），兼容 1.0
            foreach ($method->getParameters() as $parameter) {
                $type = $parameter->getType();
                if ($type !== null && !($type instanceof \ReflectionNamedType && $type->getName() === 'mixed')) {
                    $violations[] = sprintf(
                        '%s::%s() 参数 $%s 声明了 %s 类型（psr/simple-cache 1.0 下不兼容）',
                        $class,
                        $method->getName(),
                        $parameter->getName(),
                        $type
                    );
                }
            }

            // 返回类型必须声明且与 3.0 接口一致，兼容 3.0
            if ($method->getReturnType() === null) {
                $violations[] = sprintf(
                    '%s::%s() 缺少返回类型（psr/simple-cache 3.0 下不兼容）',
                    $class,
                    $method->getName()
                );
            } elseif ((string)$method->getReturnType() !== (string)$interfaceMethod->getReturnType()) {
                $violations[] = sprintf(
                    '%s::%s() 返回类型 %s 与接口的 %s 不一致',
                    $class,
                    $method->getName(),
                    $method->getReturnType(),
                    $interfaceMethod->getReturnType()
                );
            }
        }

        expect($violations)->toBe([]);
    })->with([
        'ArrayCache' => [ArrayCache::class],
        'NullCache' => [NullCache::class],
    ]);
});
