<?php

namespace Tests\Fixtures\Auth;

use Webman\Http\Request;
use WebmanTech\Auth\Middleware\Authentication;

class OptionalAuthentication extends Authentication
{
    protected function isOptionalRoute(Request $request): bool
    {
        return $request->get('optional') === 'true';
    }
}
