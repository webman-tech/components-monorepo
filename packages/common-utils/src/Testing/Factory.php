<?php

namespace WebmanTech\CommonUtils\Testing;

use Webman\Database\Initializer;
use Webman\Route as WebmanRoute;
use Webman\Util;
use WebmanTech\CommonUtils\Constants;
use WebmanTech\CommonUtils\Local;
use WebmanTech\CommonUtils\Runtime;
use WebmanTech\CommonUtils\RuntimeCustomRegister;
use function WebmanTech\CommonUtils\base_path;
use function WebmanTech\CommonUtils\config_path;

final class Factory
{
    public static function registerTestRuntime(
        string $baseDir,
        string $vendorDir,
    ): void
    {
        Runtime::$RUNTIME = Constants::RUNTIME_CUSTOM;

        RuntimeCustomRegister::register(RuntimeCustomRegister::KEY_BASE_PATH, function (?string $path = null) use ($baseDir): string {
            return Local::combinePath($baseDir, $path ?? '');
        });
        RuntimeCustomRegister::register(RuntimeCustomRegister::KEY_RUNTIME_PATH, function (?string $path = null): string {
            return Local::getBasePath('runtime' . ($path ? ('/' . $path) : ''));
        });
        RuntimeCustomRegister::register(RuntimeCustomRegister::KEY_CONFIG_PATH, function (?string $path = null): string {
            return Local::getBasePath('config' . ($path ? ('/' . $path) : ''));
        });
        RuntimeCustomRegister::register(RuntimeCustomRegister::KEY_APP_PATH, function (?string $path = null): string {
            return Local::getBasePath('app' . ($path ? ('/' . $path) : ''));
        });
        RuntimeCustomRegister::register(RuntimeCustomRegister::KEY_VENDOR_PATH, function (?string $path = null) use ($vendorDir): string {
            return Local::combinePath($vendorDir, $path ?? '');
        });
        RuntimeCustomRegister::register(RuntimeCustomRegister::KEY_CONFIG_GET, TestConfig::staticGet(...));
        RuntimeCustomRegister::register(RuntimeCustomRegister::KEY_CONTAINER_GET, TestContainer::get(...));
        RuntimeCustomRegister::register(RuntimeCustomRegister::KEY_CONTAINER_HAS, TestContainer::has(...));
        RuntimeCustomRegister::register(RuntimeCustomRegister::KEY_CONTAINER_MAKE, TestContainer::make(...));
        RuntimeCustomRegister::register(RuntimeCustomRegister::KEY_LOG_CHANNEL, TestLogger::channel(...));
        RuntimeCustomRegister::register(RuntimeCustomRegister::KEY_LANG_GET_LOCALE, TestLang::getLocale(...));
        RuntimeCustomRegister::register(RuntimeCustomRegister::KEY_LANG_SET_LOCALE, TestLang::setLocale(...));
        RuntimeCustomRegister::register(RuntimeCustomRegister::KEY_REQUEST, TestRequest::instance(...));

        // 加载 config 配置
        self::loadAllConfig(['route']);
        // 注册 webman 路由（必须，否则后续直接调用 Route::add 会报错）
        if (class_exists(WebmanRoute::class)) {
            WebmanRoute::load([]);
        }
        // 引入 db，之前是在 support\Model 中引入的
        if (class_exists(Initializer::class)) {
            Initializer::init(config('database'));
        }
    }

    /**
     * @see \support\App::loadAllConfig()
     */
    private static function loadAllConfig(array $excludes = []): void
    {
        TestConfig::load(config_path(), $excludes);
        $directory = base_path() . '/plugin';
        foreach (Util::scanDir($directory, false) as $name) {
            $dir = "$directory/$name/config";
            if (is_dir($dir)) {
                TestConfig::load($dir, $excludes, "plugin.$name");
            }
        }
    }
}
