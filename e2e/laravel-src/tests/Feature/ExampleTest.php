<?php

// 覆盖骨架默认 ExampleTest（其访问的 / welcome 路由已被 e2e 路由替换），
// 改为验证骨架自带的 health 路由

test('health 路由返回成功响应', function () {
    $response = $this->get('/up');

    $response->assertStatus(200);
});
