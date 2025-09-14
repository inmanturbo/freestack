<?php

namespace App\Http\Middleware;

use App\UserApiToken;
use Closure;

class EnsureUserApiToken
{
    public function handle($request, Closure $next)
    {
        if ($request->user()) {
            UserApiToken::ensure();
        }

        return $next($request);
    }
}
