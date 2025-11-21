<?php

use WebmanTech\CommonUtils\EnvAttr;

beforeEach(function () {
    EnvAttr::reset();
});

test('get not exist', function () {
    expect(EnvAttr::get('NOT_EXIST'))->toBeNull()
        ->and(EnvAttr::get('NOT_EXIST', 'default_string'))->toBe('default_string')
        ->and(EnvAttr::get('NOT_EXIST', 1))->toBe(1)
        ->and(EnvAttr::get('NOT_EXIST', false))->toBe(false)
        ->and(EnvAttr::get('NOT_EXIST', fn() => 'callable'))->toBe('callable') // 默认值为 callback 时会解析
    ;
});

test('get exist', function () {
    // 从 env.php 中读取
    expect(EnvAttr::get('TEST_ABC'))->toBe('abc')
        ->and(EnvAttr::get('TEST_FROM_ABC'))->toBe('abc')
        ->and(EnvAttr::get('TEST_OVERWRITE_ABC'))->toBe('overwrite_abc');
});

test('set', function () {
    EnvAttr::changeSupportReadonly(false);

    EnvAttr::set('TEST_NEW_ABC', 'xyz');
    expect(EnvAttr::get('TEST_NEW_ABC'))->toBe('xyz');
    $callback = fn() => 'callback';
    EnvAttr::set('TEST_CALLBACK', $callback);
    expect(EnvAttr::get('TEST_CALLBACK'))->toBe($callback); // callback 在 set 下不会被解析
});

test('support readonly', function () {
    // get 之后默认会变为只读，此时调用 set 会报错
    expect(EnvAttr::get('TEST_NEW_ABC'))->toBeNull()
        ->and(fn() => EnvAttr::set('TEST_NEW_ABC', 'xyz'))->toThrow(\InvalidArgumentException::class);
    // reset 之后取消只读
    EnvAttr::reset();

    // 可以正常设置
    EnvAttr::set('TEST_NEW_ABC', 'xyz');
    expect(EnvAttr::get('TEST_NEW_ABC'))->toBe('xyz')
        ->and(fn() => EnvAttr::set('TEST_NEW_ABC', 'xyz'))->toThrow(\InvalidArgumentException::class);

    // 关闭只读支持后，可以正常多次设置
    EnvAttr::reset();
    EnvAttr::changeSupportReadonly(false);

    expect(EnvAttr::get('TEST_NEW_ABC'))->toBeNull();
    EnvAttr::set('TEST_NEW_ABC', 'xyz');
    EnvAttr::set('TEST_NEW_ABC', 'xyz2');
    expect(EnvAttr::get('TEST_NEW_ABC'))->toBe('xyz2');
});

test('support define', function () {
    EnvAttr::changeSupportReadonly(false);

    // 默认不支持 define，因此取不到
    expect(EnvAttr::get('DEFINE_ABC'))->toBeNull();
    // 改为支持 define
    EnvAttr::changeSupportDefine(true);
    // 能够获取到 define 的配置
    expect(EnvAttr::get('DEFINE_ABC'))->toBe('define_abc');

    // 设置 define
    EnvAttr::set('DEFINE_NEW_ABC', 'new_define_abc');
    expect(defined('DEFINE_NEW_ABC'))->toBeFalse() // 通过 EnvAttr::set 不会直接 define 值
    ->and(EnvAttr::get('DEFINE_NEW_ABC'))->toBe('new_define_abc') // 但通过 EnvAttr::get 已经可以取到了
    ;
    // 可以主动转为 define
    EnvAttr::transToDefine();
    expect(defined('DEFINE_NEW_ABC'))->toBeTrue();

    // 通过 define 设置
    define('DEFINE_NEW_ABC2', 'new_define_abc2');
    expect(EnvAttr::get('DEFINE_NEW_ABC2'))->toBe('new_define_abc2');
});

test('support sys env', function () {
    expect(EnvAttr::get('ENV_ABC'))->toBeNull();

    $_SERVER['ENV_ABC'] = 'env_abc';
    // 默认支持取系统 env 的变量
    expect(EnvAttr::get('ENV_ABC'))->toBe('env_abc');
    // 修改不去支持
    EnvAttr::changeSupportSysEnv(false);
    // 取不到了
    expect(EnvAttr::get('ENV_ABC'))->toBeNull();
});
