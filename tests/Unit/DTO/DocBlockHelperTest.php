<?php

use WebmanTech\DTO\Helper\DocBlockHelper;

test('extractVarTypes', function () {
    $cases = [
        [
            'comment' => fixture_get_content('DTO/MyClass.php'),
            'types' => null,
        ],
        [
            'comment' => '@var string $foo',
            'types' => [
                ['type' => 'string'],
            ],
        ],
        [
            'comment' => '@var string|null $foo',
            'types' => [
                ['type' => 'string'],
                ['type' => 'null'],
            ],
        ],
        [
            'comment' => '@var int $foo',
            'types' => [
                ['type' => 'int'],
            ],
        ],
    ];

    foreach ($cases as $case) {
        $types = DocBlockHelper::extractVarTypes($case['comment']);
        expect($types)->toBe($case['types']);
    }

});
