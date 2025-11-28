<?php

use Illuminate\Session\Store as IlluminateSessionStore;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Symfony\Component\HttpFoundation\Session\SessionInterface as SymfonySessionInterface;
use WebmanTech\CommonUtils\Session;
use WebmanTech\CommonUtils\Testing\TestSession;
use Workerman\Protocols\Http\Session as WebmanSession;

describe('different adapter test', function () {
    $cases = [
        [
            'instance_class' => TestSession::class,
            'get_session' => function () {
                TestSession::clear();
                return Session::getCurrent();
            },
        ],
        [
            'instance_class' => WebmanSession::class,
            'get_session' => function () {
                $session = new WebmanSession('abc');
                return Session::from($session);
            },
        ],
        [
            'instance_class' => SymfonySessionInterface::class,
            'get_session' => function () {
                $session = new SymfonySession(
                    new Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage()
                );
                return Session::from($session);
            },
        ],
        [
            'instance_class' => IlluminateSessionStore::class,
            'get_session' => function () {
                $store = new IlluminateSessionStore('app', new Illuminate\Session\ArraySessionHandler(5), 'test-id');
                $store->start();
                return Session::from($store);
            },
        ],
    ];

    foreach ($cases as $case) {
        test($case['instance_class'], function () use ($case) {
            $tokenKey = 'token';
            $tokenValue = 'abc123';
            $defaultValue = 'default';
            /** @var Session $session */
            $session = $case['get_session']();

            expect($session->getRaw())->toBeInstanceOf($case['instance_class']);

            $session->set($tokenKey, $tokenValue);

            expect($session->get($tokenKey))->toBe($tokenValue)
                ->and($session->get('missing', $defaultValue))->toBe($defaultValue);

            $session->delete($tokenKey);
            expect($session->get($tokenKey))->toBeNull();
        });
    }
});
