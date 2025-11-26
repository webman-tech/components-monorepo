<?php

use WebmanTech\CommonUtils\Session;

test('get set', function () {
    $session = Session::getCurrent();
    $session->set('token', 'abc123');
    expect($session->get('token'))->toBe('abc123')
        ->and($session->get('missing', 'default'))->toBe('default');
});
