<?php

use WebmanTech\CommonUtils\View;

test('renderPHP', function () {
    $content = View::renderPHP(fixture_get_path('CommonUtils/view/render_test.php'), [
        'name' => 'webman-tech',
        'age' => 18,
    ]);
    expect($content)->toContain('webman-tech', '18', 'div');
});
