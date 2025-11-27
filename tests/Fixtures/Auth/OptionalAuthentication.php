<?php

namespace Tests\Fixtures\Auth;

use WebmanTech\Auth\Middleware\Authentication;
use WebmanTech\CommonUtils\Request;

class OptionalAuthentication extends Authentication
{
    protected function isOptionalRoute(Request $request): bool
    {
        return $request->get('optional') === 'true';
    }
}
