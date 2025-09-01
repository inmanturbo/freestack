<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireEdgeSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $provided = (string) $request->header('X-Edge-Secret', '');
        $expected = (string) config('app.edge_shared_secret', env('EDGE_SHARED_SECRET', ''));

        // Constant-time compare; reject if missing or wrong
        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            abort(403); // Forbidden
        }

        return $next($request);
    }
}
