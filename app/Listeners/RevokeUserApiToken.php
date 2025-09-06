<?php

namespace App\Listeners;

use App\UserApiToken;
use Illuminate\Auth\Events\Logout;
use Laravel\Passport\Token;

class RevokeUserApiToken
{
    public function handle(Logout $event): void
    {
        if ($id = session('api_token_id')) {
            Token::where('id', $id)->update(['revoked' => true]);
        }
        UserApiToken::forget();
    }
}
