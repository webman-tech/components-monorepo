<?php

use App\Support\PrefixCipher;
use WebmanTech\DTO\Processor\DecryptFields;

// dto 包在真实 laravel 环境下（框架自带 validator() helper）的字段加解密链路

test('入参字段先解密再校验', function () {
    $cipher = new PrefixCipher();

    $response = $this->postJson('/e2e/dto-crypto', [
        // 密文本身不是合法 email；能通过 email 规则即证明解密发生在验证之前
        'secret' => $cipher->encrypt('user@example.com', 'secret'),
    ]);

    $response->assertOk()
        ->assertJsonPath('data.secret', 'user@example.com');
});

test('解密后不满足验证规则时返回 422', function () {
    $cipher = new PrefixCipher();

    $response = $this->postJson('/e2e/dto-crypto', [
        'secret' => $cipher->encrypt('not-an-email', 'secret'),
    ]);

    $response->assertStatus(422);

    $data = $response->json();
    expect($data['code'])->toBe(422)
        ->and($data['errors'])->toHaveKey('secret');
});

test('出参字段在 toArray 时加密', function () {
    $cipher = new PrefixCipher();

    $data = $this->postJson('/e2e/dto-crypto', [
        'secret' => $cipher->encrypt('user@example.com', 'secret'),
    ])->assertOk()->json('data');

    expect($data['token'])->toBe($cipher->encrypt('plain-token', 'token'))
        // 密文可被同一算法解回明文（往返一致）
        ->and($cipher->decrypt($data['token'], 'token'))->toBe('plain-token')
        // 数组字段逐项加密
        ->and($data['tags'])->toBe([
            $cipher->encrypt('alpha', 'tags'),
            $cipher->encrypt('beta', 'tags'),
        ])
        // 非字符串字段原样透传
        ->and($data['count'])->toBe(2)
        // 未标记 #[DataEncrypt] 的字段不参与加密
        ->and($data['result'])->toBe('ok');
});

test('密文被篡改时返回 400 并带处理器与字段信息', function () {
    $response = $this->postJson('/e2e/dto-crypto', ['secret' => 'tampered']);

    $response->assertStatus(400)
        ->assertJson([
            'code' => 400,
            'processor' => DecryptFields::class,
            'field' => 'secret',
        ]);
});
