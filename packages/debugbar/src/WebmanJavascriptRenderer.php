<?php

namespace WebmanTech\Debugbar;

use DebugBar\DebugBar;
use DebugBar\JavascriptRenderer;
use Webman\Route;

class WebmanJavascriptRenderer extends JavascriptRenderer
{
    /**
     * @var array
     */
    protected $assetsInfo = [
        'webman' => [
            'path' => __DIR__ . '/Resources',
            'css' => [
                'webman-debugbar.css',
            ],
            'js' => [],
        ],
        'laravel' => [
            'path' => __DIR__ . '/Laravel/Resources',
            'css' => [],
            'js' => [
                'sqlqueries/widget.js',
            ],
        ]
    ];

    public function __construct(DebugBar $debugBar, $baseUrl = null, $basePath = null)
    {
        parent::__construct($debugBar, $baseUrl, $basePath);

        $this->ajaxHandlerBindToJquery = true;
        $this->ajaxHandlerBindToXHR = true;
        $this->ajaxHandlerBindToFetch = true;

        foreach ($this->assetsInfo as $name => $item) {
            $this->addAssets($item['css'], $item['js'], $item['path'], $baseUrl . '/' . $name);
        }
    }

    /**
     * 注册静态资源路由
     */
    public function registerAssetRoute(): void
    {
        Route::get($this->getBaseUrl() . '/[{path:.+}]', function ($request, $path = '') {
            // 安全检查，避免url里 /../../../password 这样的非法访问
            if (str_contains($path, '..')) {
                return response('<h1>400 Bad Request</h1>', 400);
            }
            // debugbar 的静态文件目录
            $staticBasePath = $this->getBasePath();
            // 其他文件
            foreach ($this->assetsInfo as $name => $item) {
                if (str_starts_with($path, $name . '/')) {
                    $staticBasePath = $item['path'];
                    $path = substr($path, strlen($name) + 1);
                    break;
                }
            }

            // 文件
            $file = "$staticBasePath/$path";
            if (!is_file($file)) {
                return response('<h1>404 Not Found</h1>', 404);
            }
            return response()->withFile($file);
        });
    }
}
