<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Spec;

use Closure;
use OpenApi\ComponentNamer\ShortName;

/**
 * 保留原配置的类名格式，组件成员及限定键的命名沿用官方规则
 */
final class ComponentNamer extends ShortName
{
    public function __construct(private readonly true|Closure $formatter)
    {
    }

    protected function className(\ReflectionClass $class): string
    {
        return $this->formatter instanceof Closure
            ? ($this->formatter)($class->getName())
            : str_replace(' ', '', ucwords(str_replace(['\\', '-', '_'], ' ', $class->getName())));
    }
}
