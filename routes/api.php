<?php

use App\Http\Middleware\RequireEdgeSecret;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::get('/oauth/introspect', function (Request $request) {

    $user = User::api();

    if (! $user) {
        return response()->json(['active' => false], 401);
    }

    $token = $user?->token() ?? null;

    return response()->json([
        'active' => true,
        'username' => $user->email ?? null,
        'client_id' => 'edge',
        'token_type' => 'access_token',
        'exp' => $token?->expires_at?->timestamp,
        'scope' => is_array($token?->scopes ?? null) ? implode(' ', $token->scopes) : '',
    ], 200, [
        'X-Auth-User-Id' => (string) $user->getAuthIdentifier(),
    ]);
})->middleware(RequireEdgeSecret::class);
