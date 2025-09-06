<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\UserApiToken;
use Closure;

class EnsureUserApiToken
{
    public function handle($request, Closure $next)
    {
        if (User::web()) {
            UserApiToken::ensure();
        }

        return $next($request);
    }
}
