<?php

use WebmanTech\AmisAdmin\Amis;
use WebmanTech\AmisAdmin\Amis\Component;
use WebmanTech\AmisAdmin\Controller\RenderController;

/**
 * Amis 静态资源的基础 url
 * 建议生产环境使用指定的版本，否则会存在因版本变更引起的问题
 * 更加建议使用公司自己的 cdn，或者将静态资源下载后放到本地，提交速度
 */
$amisAssetBaseUrl = 'https://unpkg.com/amis@latest/sdk/';

return [
    /**
     * amis 资源
     */
    'assets' => [
        /**
         * html 上的 lang 属性
         */
        'lang' => fn() => locale(),
        /**
         * 静态资源，建议下载下来放到 public 目录下然后替换链接
         * @link https://aisuda.bce.baidu.com/amis/zh-CN/docs/start/getting-started#sdk
         */
        'css' => [
            $amisAssetBaseUrl . 'sdk.css',
            $amisAssetBaseUrl . 'helper.css',
            $amisAssetBaseUrl . 'iconfont.css',
        ],
        'js' => [
            $amisAssetBaseUrl . 'sdk.js',
            'https://unpkg.com/history@4.10.1/umd/history.js', // 使用 app 必须
            // 可以添加复杂的 script 脚本
            /*[
                'type' => 'script',
                'content' => <<<JS
window.xxx = xxx;
JS,
            ]*/
        ],
        /**
         * 切换主题
         * @link https://aisuda.bce.baidu.com/amis/zh-CN/docs/start/getting-started#%E5%88%87%E6%8D%A2%E4%B8%BB%E9%A2%98
         */
        'theme' => '',
        /**
         * 语言
         * @link https://aisuda.bce.baidu.com/amis/zh-CN/docs/extend/i18n
         */
        'locale' => fn() => str_replace('_', '-', locale()),
        /**
         * debug
         * @link https://aisuda.bce.baidu.com/amis/zh-CN/docs/extend/debug
         */
        'debug' => false,
    ],
    /**
     * @see Amis::renderApp()
     */
    'app' => [
        /**
         * @link https://aisuda.bce.baidu.com/amis/zh-CN/components/app
         */
        'amisJSON' => [
            'brandName' => config('app.name', 'App Admin'),
            'logo' => '/favicon.ico',
            'api' => route('admin.pages'), // 修改成获取菜单的路由
        ],
        'title' => config('app.name'),
    ],
    /**
     * @see Amis::renderPage()
     */
    'page' => [
        /**
         * @link https://aisuda.bce.baidu.com/amis/zh-CN/docs/start/getting-started
         */
        'amisJSON' => [],
    ],
    /**
     * 登录页面配置
     * @see RenderController::login()
     */
    'page_login' => function() {
        return [
            //'background' => '#eee', // 可以使用图片, 'url(http://xxxx)'
            'login_api' => route('admin.login'),
            'success_redirect' => route('admin'),
        ];
    },
    /**
     * 用于全局替换组件的默认参数
     * @see Component::$config
     */
    'components' => [
        // 例如: 将列表页的字段默认左显示
        /*\WebmanTech\AmisAdmin\Amis\GridColumn::class => [
            'schema' => [
                'align' => 'left',
            ],
        ],*/
    ],
    /**
     * 默认的验证器
     * 返回一个 \WebmanTech\AmisAdmin\Validator\ValidatorInterface
     */
    'validator' => fn() => new \WebmanTech\AmisAdmin\Validator\NullValidator(),
    //'validator' => fn() => new \WebmanTech\AmisAdmin\Validator\LaravelValidator(\support\Container::get(\Illuminate\Contracts\Validation\Factory::class)),
    /**
     * 用于获取当前请求的路径，当部署到二级目录时有用
     */
    'request_path_getter' => null,

    /**
     * 从 amis 官方 editor 导出的 json 文件，快速渲染出一个页面。
     *
     * 约定：将 json 文件放到 `resource/amis-json/{name}.json`，然后访问 `{group}/{name}` 即可预览。
     *
     * 支持变量占位符（递归替换所有字符串）：
     * - `{{xxx}}`：读取 `json_page.vars` 返回的变量
     * - `{{route:admin.login}}`：调用 `route('admin.login')`
     * - `{{config:app.name}}`：读取 `config('app.name')`
     * - `{{env:APP_ENV}}`：读取 env
     */
    'json_page' => [
        /**
         * json 文件目录
         */
        'path' => base_path('resource/amis-json'),
        /**
         * json 文件扩展名（支持自定义）
         */
        'ext' => '.json',
        /**
         * 自定义变量（可返回数组，或返回 callable）
         *
         * - 支持在 schema 中使用 `{{login_api}}` / `{{logout_api}}` 这类占位符
         * - 如需跨框架统一 request，可将参数类型标注为 `WebmanTech\CommonUtils\Request`
         */
        'vars' => function ($request) {
            return [
                // 'login_api' => route('admin.login'),
            ];
        },
        /**
         * 可选路由注册（默认关闭，避免未加鉴权时暴露页面）
         * - enable: 是否注册路由
         * - group:  路由前缀
         * - middleware: 中间件（建议加上登录/权限）
         */
        'route' => [
            'enable' => false,
            'group' => '/amis-json',
            'middleware' => [],
        ],
    ],
];
