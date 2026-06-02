<?php

use Symfony\Component\Clock\MockClock;
use WebmanTech\CommonUtils\RuntimeCustomRegister;
use WebmanTech\CommonUtils\Timer;
use WebmanTech\CommonUtils\Timer\PcntlTimer;

beforeEach(function () {
    $this->clock = new MockClock();
    PcntlTimer::setClock($this->clock);
    RuntimeCustomRegister::register(RuntimeCustomRegister::KEY_TIMER, PcntlTimer::call(...));
});

test('delay fires once via Timer facade', function () {
    $called = false;
    Timer::delay(5, function () use (&$called) {
        $called = true;
    });

    $this->clock->modify('+6 seconds');
    PcntlTimer::tick();
    expect($called)->toBeTrue();
});

test('repeat fires periodically via Timer facade', function () {
    $count = 0;
    Timer::repeat(3, function () use (&$count) {
        $count++;
    });

    $this->clock->modify('+4 seconds');
    PcntlTimer::tick();
    expect($count)->toBe(1);

    $this->clock->modify('+4 seconds');
    PcntlTimer::tick();
    expect($count)->toBe(2);
});

test('add delegates correctly via Timer facade', function () {
    $result = null;
    Timer::add(3, function (string $a, string $b) use (&$result) {
        $result = $a . $b;
    }, ['foo', 'bar'], false);

    $this->clock->modify('+4 seconds');
    PcntlTimer::tick();
    expect($result)->toBe('foobar');
});

test('del cancels timer via Timer facade', function () {
    $called = false;
    $id = Timer::delay(5, function () use (&$called) {
        $called = true;
    });

    Timer::del($id);

    $this->clock->modify('+10 seconds');
    PcntlTimer::tick();
    expect($called)->toBeFalse();
});

test('delAll cancels all timers via Timer facade', function () {
    $called1 = false;
    $called2 = false;
    Timer::delay(3, function () use (&$called1) {
        $called1 = true;
    });
    Timer::repeat(5, function () use (&$called2) {
        $called2 = true;
    });

    Timer::delAll();

    $this->clock->modify('+10 seconds');
    PcntlTimer::tick();
    expect($called1)->toBeFalse()
        ->and($called2)->toBeFalse();
});
