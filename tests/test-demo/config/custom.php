<?php

return [
    'config_test' => [
        'exist' => 'abc',
        'exist_number' => 123,
        'exist_array' => ['1', '2'],
        'exist_bool' => false,
        'exist_fn' => function () {
            return 'abc';
        },
    ],
];
