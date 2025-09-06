<?php

namespace App;

use Illuminate\Support\Facades\Auth;

class UserApiToken
{
    public static function ensure(): string
    {
        $token = session('api_token');
        $expiresAt = session('api_token_expires_at');

        if ($token && $expiresAt && now()->lt($expiresAt)) {
            return $token;
        }

        $user = Auth::user();
        abort_unless($user, 401);

        // Name it and scope it; give it a short expiry (e.g., 60 min).
        $accessToken = $user->createToken(
            'livewire-writes',
            ['post:write'],
            now()->addMinutes(55) // keep a little earlier than Passport::tokensExpireIn
        );

        $plain = $accessToken->accessToken; // Bearer
        session([
            'api_token' => $plain,
            'api_token_id' => $accessToken->token->id, // DB id of oauth_access_tokens
            'api_token_expires_at' => $accessToken->token->expires_at,
        ]);

        return $plain;
    }

    public static function forget(): void
    {
        session()->forget(['api_token', 'api_token_id', 'api_token_expires_at']);
    }
}
