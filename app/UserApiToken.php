<?php

namespace App;

use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Token;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;

class UserApiToken
{
    public static function ensure(): ?string
    {
        $token = session('api_token');
        $expiresAt = session('api_token_expires_at');

        if ($token && $expiresAt && now()->lt($expiresAt) && static::isPassportTokenValid($token)) {
            return $token;
        }

        $user = Auth::user();
        abort_unless($user, 401);

        // Name it and scope it; give it a short expiry (e.g., 60 min).
        $accessToken = EdgeAuthSession::makeForCurrentSession()->issueToken();

        if (! $accessToken?->accessToken) {
            return null;
        }

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

    protected static function isPassportTokenValid(string $jwt): bool
    {
        $parser = new Parser(new JoseEncoder);
        $token = $parser->parse($jwt);

        $jti = $token->claims()->get('jti'); // the access_token.id

        $record = Token::find($jti);

        if (! $record) {
            return false;
        }

        if ($record->revoked) {
            return false;
        }

        if ($record->expires_at && $record->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
