<?php

use App\Http\Controllers\Api\AccessTokenController;
use App\Http\Controllers\Api\OAuth2ApplicationController;
use App\Http\Middleware\RequireEdgeSecret;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::middleware('auth:api')->prefix('access-tokens')->group(function () {
    Route::get('/', [AccessTokenController::class, 'index']);
    Route::post('/', [AccessTokenController::class, 'store']);
    Route::get('/{id}', [AccessTokenController::class, 'show']);
    Route::put('/{id}', [AccessTokenController::class, 'update']);
    Route::delete('/{id}', [AccessTokenController::class, 'destroy']);
    Route::post('/{id}/revoke', [AccessTokenController::class, 'revoke']);
});

Route::middleware('auth:api')->prefix('oauth-applications')->group(function () {
    Route::get('/', [OAuth2ApplicationController::class, 'index']);
    Route::post('/', [OAuth2ApplicationController::class, 'store']);
    Route::get('/{id}', [OAuth2ApplicationController::class, 'show']);
    Route::put('/{id}', [OAuth2ApplicationController::class, 'update']);
    Route::delete('/{id}', [OAuth2ApplicationController::class, 'destroy']);
    Route::post('/{id}/revoke', [OAuth2ApplicationController::class, 'revoke']);
    Route::post('/{id}/regenerate-secret', [OAuth2ApplicationController::class, 'regenerateSecret']);
});

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

Route::get('adminer/{key}', function ($key) {
    $data = cache()->pull('adminer:'.$key);
    abort_unless($data, 404);

    return response()->json($data);
});
