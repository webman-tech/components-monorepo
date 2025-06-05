<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

//pest()->extend(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

use Webman\Context;
use Webman\Http\Request;
use Workerman\Protocols\Http\Session;

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

pest()->afterEach(function () {
    // request_create_one() 后会在 Context 中添加信息，需要每次清理，否则会造成污染
    Context::reset();
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function fixture_get_path(string $path): string
{
    return __DIR__ . '/Fixtures/' . $path;
}

function fixture_get_content(string $path): false|string
{
    return file_get_contents(fixture_get_path($path));
}

function fixture_get_require(string $path)
{
    return require fixture_get_path($path);
}

function request_create_one(): Request
{
    $buffer = strtr(fixture_get_content('misc/request_sample.txt'), [
        "\n" => "\r\n",
    ]);
    $request = new Request($buffer);

    // 设置请求对象到上下文，是的 webman 下 request() 可以获取到
    Context::set(Request::class, $request);

    // 设置 sessionid, 使得 session() 可以用
    $request->setHeader('cookie', Session::$name . '=sessionid;');
    // 清空 session 信息，防止数据前后污染
    $request->session()->flush();

    return $request;
}
