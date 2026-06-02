<?php

use Symfony\Component\Clock\MockClock;
use WebmanTech\CommonUtils\Timer\PcntlTimer;

beforeEach(function () {
    $this->clock = new MockClock('@1000');
    PcntlTimer::setClock($this->clock);
});

test('delay fires once when time passes', function () {
    $called = false;
    PcntlTimer::add(5, function () use (&$called) {
        $called = true;
    }, [], false);

    PcntlTimer::tick();
    expect($called)->toBeFalse();

    $this->clock->modify('+6 seconds');
    PcntlTimer::tick();
    expect($called)->toBeTrue();
});

test('delay does not fire again after first trigger', function () {
    $count = 0;
    PcntlTimer::add(3, function () use (&$count) {
        $count++;
    }, [], false);

    $this->clock->modify('+4 seconds');
    PcntlTimer::tick();
    expect($count)->toBe(1);

    $this->clock->modify('+6 seconds');
    PcntlTimer::tick();
    expect($count)->toBe(1);
});

test('repeat fires on each interval', function () {
    $count = 0;
    PcntlTimer::add(5, function () use (&$count) {
        $count++;
    }, [], true);

    $this->clock->modify('+6 seconds');
    PcntlTimer::tick();
    expect($count)->toBe(1);

    $this->clock->modify('+6 seconds');
    PcntlTimer::tick();
    expect($count)->toBe(2);

    $this->clock->modify('+6 seconds');
    PcntlTimer::tick();
    expect($count)->toBe(3);
});

test('del cancels timer before it fires', function () {
    $called = false;
    $id = PcntlTimer::add(5, function () use (&$called) {
        $called = true;
    }, [], false);

    PcntlTimer::del($id);

    $this->clock->modify('+10 seconds');
    PcntlTimer::tick();
    expect($called)->toBeFalse();
});

test('del stops a repeating timer', function () {
    $count = 0;
    $id = PcntlTimer::add(3, function () use (&$count) {
        $count++;
    }, [], true);

    $this->clock->modify('+4 seconds');
    PcntlTimer::tick();
    expect($count)->toBe(1);

    PcntlTimer::del($id);

    $this->clock->modify('+6 seconds');
    PcntlTimer::tick();
    expect($count)->toBe(1);
});

test('delAll cancels all timers', function () {
    $called1 = false;
    $called2 = false;
    PcntlTimer::add(3, function () use (&$called1) {
        $called1 = true;
    }, [], false);
    PcntlTimer::add(5, function () use (&$called2) {
        $called2 = true;
    }, [], false);

    PcntlTimer::delAll();

    $this->clock->modify('+10 seconds');
    PcntlTimer::tick();
    expect($called1)->toBeFalse()
        ->and($called2)->toBeFalse();
});

test('callback receives args', function () {
    $result = null;
    PcntlTimer::add(3, function (string $a, string $b) use (&$result) {
        $result = $a . $b;
    }, ['hello', 'world'], false);

    $this->clock->modify('+4 seconds');
    PcntlTimer::tick();
    expect($result)->toBe('helloworld');
});

test('exception in callback does not break other timers', function () {
    $called = false;
    PcntlTimer::add(3, function () {
        throw new \RuntimeException('test');
    }, [], false);
    PcntlTimer::add(3, function () use (&$called) {
        $called = true;
    }, [], false);

    $this->clock->modify('+4 seconds');
    PcntlTimer::tick();
    expect($called)->toBeTrue();
});

test('interval rounds up to at least 1 second', function () {
    $called = false;
    PcntlTimer::add(0.1, function () use (&$called) {
        $called = true;
    }, [], false);

    $this->clock->modify('+2 seconds');
    PcntlTimer::tick();
    expect($called)->toBeTrue();
});

test('multiple timers with different intervals fire independently', function () {
    $shortCount = 0;
    $longCount = 0;
    PcntlTimer::add(3, function () use (&$shortCount) {
        $shortCount++;
    }, [], true);
    PcntlTimer::add(7, function () use (&$longCount) {
        $longCount++;
    }, [], true);

    $this->clock->modify('+4 seconds');
    PcntlTimer::tick();
    expect($shortCount)->toBe(1)->and($longCount)->toBe(0);

    $this->clock->modify('+4 seconds');
    PcntlTimer::tick();
    expect($shortCount)->toBe(2)->and($longCount)->toBe(1);

    $this->clock->modify('+4 seconds');
    PcntlTimer::tick();
    expect($shortCount)->toBe(3)->and($longCount)->toBe(1);
});
