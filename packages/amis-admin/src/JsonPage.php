<?php

namespace WebmanTech\AmisAdmin;

use Webman\Route;
use WebmanTech\AmisAdmin\Controller\JsonPageController;
use WebmanTech\AmisAdmin\Helper\ConfigHelper;
use WebmanTech\CommonUtils\Local;
use WebmanTech\CommonUtils\Request;

use function WebmanTech\CommonUtils\base_path;
use function WebmanTech\CommonUtils\config;
use function WebmanTech\CommonUtils\get_env;

final class JsonPage
{
    public static function registerRoute(): void
    {
        $routeConfig = (array)ConfigHelper::get('json_page.route', []);
        if (!($routeConfig['enable'] ?? false)) {
            return;
        }

        $group = (string)($routeConfig['group'] ?? '/amis-json');
        $middleware = $routeConfig['middleware'] ?? [];

        Route::group($group, function (): void {
            Route::get('/{page:.+}', [JsonPageController::class, 'show']);
        })->middleware($middleware);
    }

    /**
     * 加载 amis editor 导出的 json 文件，并进行变量替换。
     *
     * - 默认目录：`resource/amis-json`
     * - 默认扩展名：`.json`
     * - 变量占位符：`{{xxx}}` / `{{route:xxx}}` / `{{config:xxx}}` / `{{env:xxx}}`
     *
     * @throws \JsonException
     */
    public static function loadSchema(string $page, object|null $request = null): array
    {
        $page = self::normalizePage($page);
        if ($page === '' || str_contains($page, '..') || str_contains($page, '\\')) {
            throw new \InvalidArgumentException('Invalid page name', 400);
        }

        $baseDir = self::getSchemaDir();
        $ext = self::getSchemaExt();
        $file = Local::combinePath($baseDir, $page . $ext);

        $realBaseDir = realpath($baseDir);
        if ($realBaseDir === false) {
            throw new \RuntimeException("Schema dir not exists: {$baseDir}", 500);
        }

        $realFile = realpath($file);
        if ($realFile === false || !is_file($realFile)) {
            throw new \RuntimeException("Schema file not found: {$file}", 404);
        }

        $realBaseDir = rtrim($realBaseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($realFile, $realBaseDir)) {
            throw new \RuntimeException('Invalid schema file path', 400);
        }

        $content = file_get_contents($realFile);
        if ($content === false) {
            throw new \RuntimeException("Read schema file failed: {$realFile}", 500);
        }

        $content = self::stripBom($content);
        $schema = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($schema)) {
            throw new \RuntimeException('Schema root must be object/array', 500);
        }

        return self::replaceVars($schema, $request);
    }

    /**
     * 对 schema 进行变量替换（递归处理）。
     *
     * @param array $schema
     * @throws \Throwable
     */
    public static function replaceVars(array $schema, object|null $request = null): array
    {
        $vars = self::getVars($request);
        return self::replaceValue($schema, $vars, $request);
    }

    /**
     * @throws \Throwable
     */
    private static function getVars(object|null $request = null): array
    {
        $vars = ConfigHelper::get('json_page.vars', []);
        if (is_callable($vars)) {
            $vars = self::callCallable($vars, $request);
        }
        if (!is_array($vars)) {
            $vars = [];
        }

        return $vars;
    }

    private static function getSchemaDir(): string
    {
        $dir = ConfigHelper::get('json_page.path');
        if (is_string($dir) && $dir !== '') {
            return rtrim($dir, DIRECTORY_SEPARATOR);
        }

        return rtrim(base_path('resource/amis-json'), DIRECTORY_SEPARATOR);
    }

    private static function getSchemaExt(): string
    {
        $ext = ConfigHelper::get('json_page.ext', '.json');
        if (!is_string($ext) || $ext === '') {
            return '.json';
        }

        return str_starts_with($ext, '.') ? $ext : ('.' . $ext);
    }

    private static function normalizePage(string $page): string
    {
        $page = trim($page);
        $page = ltrim($page, '/');
        $page = rtrim($page, '/');

        if (str_ends_with($page, '.json')) {
            $page = substr($page, 0, -5);
        }

        return $page;
    }

    /**
     * @throws \Throwable
     */
    private static function replaceValue(mixed $value, array $vars, object|null $request = null): mixed
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = self::replaceValue($v, $vars, $request);
            }
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if (preg_match('/^\\{\\{\\s*(.+?)\\s*\\}\\}$/', $trimmed, $m)) {
            $resolved = self::resolveExpr($m[1], $vars, $request);
            return $resolved === null ? $value : $resolved;
        }

        return (string)preg_replace_callback('/\\{\\{\\s*(.+?)\\s*\\}\\}/', function (array $m) use ($vars, $request) {
            $resolved = self::resolveExpr($m[1], $vars, $request);
            if ($resolved === null) {
                return $m[0];
            }
            if (is_scalar($resolved)) {
                return (string)$resolved;
            }
            return json_encode($resolved, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $value);
    }

    /**
     * @throws \Throwable
     */
    private static function resolveExpr(string $expr, array $vars, object|null $request = null): mixed
    {
        $expr = trim($expr);
        if ($expr === '') {
            return null;
        }

        if (str_contains($expr, ':')) {
            [$type, $arg] = explode(':', $expr, 2);
            $type = trim($type);
            $arg = trim($arg);
            return match ($type) {
                'route' => self::resolveRoute($arg),
                'config' => config($arg),
                'env' => get_env($arg),
                default => null,
            };
        }

        $found = false;
        $varValue = self::getVar($vars, $expr, $found);
        if (!$found) {
            return null;
        }

        if (is_callable($varValue)) {
            return self::callCallable($varValue, $request);
        }

        return $varValue;
    }

    private static function resolveRoute(string $name): ?string
    {
        if ($name === '') {
            return null;
        }
        if (!function_exists('route')) {
            return null;
        }

        /** @var callable-string $fn */
        $fn = 'route';
        $url = $fn($name);
        return $url === null ? null : (string)$url;
    }

    private static function getVar(array $vars, string $key, bool &$found): mixed
    {
        if (array_key_exists($key, $vars)) {
            $found = true;
            return $vars[$key];
        }

        if (!str_contains($key, '.')) {
            $found = false;
            return null;
        }

        $cursor = $vars;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                $found = false;
                return null;
            }
            $cursor = $cursor[$segment];
        }

        $found = true;
        return $cursor;
    }

    /**
     * @throws \Throwable
     */
    private static function callCallable(callable $callable, object|null $request = null): mixed
    {
        if ($request === null) {
            return $callable();
        }

        $ref = match (true) {
            is_array($callable) && isset($callable[0], $callable[1]) => new \ReflectionMethod($callable[0], (string)$callable[1]),
            is_string($callable) && str_contains($callable, '::') => new \ReflectionMethod($callable),
            default => new \ReflectionFunction($callable),
        };

        if ($ref->getNumberOfParameters() < 1) {
            return $callable();
        }

        $params = $ref->getParameters();
        $firstParam = $params[0] ?? null;
        if ($firstParam?->hasType()) {
            $type = $firstParam->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin() && $type->getName() === Request::class) {
                return $callable(Request::from($request));
            }
        }

        return $callable($request);
    }

    private static function stripBom(string $content): string
    {
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            return substr($content, 3);
        }
        return $content;
    }
}
