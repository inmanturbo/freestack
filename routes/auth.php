<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('login', 'auth.login')
        ->name('login');

    Volt::route('register', 'auth.register')
        ->name('register');

    Volt::route('forgot-password', 'auth.forgot-password')
        ->name('password.request');

    Volt::route('reset-password/{token}', 'auth.reset-password')
        ->name('password.reset');

});

Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'auth.confirm-password')
        ->name('password.confirm');

    Route::get('/oauth/bridge', function (Request $request) {
        $user = User::current();

        // Issue a Passport Personal Access Token (scope as you like, e.g. ['edge'])
        $result   = $user->createToken('edge-ticket', ['edge']);
        $plain    = $result->accessToken;        // the bearer string to hand to Nginx via ?ticket=
        $tokenId  = $result->token->id;          // DB id for revocation on logout
        session(['current_passport_token_id' => $tokenId]);

        // Build return URL and append ?ticket=<token>
        $return = rtrim(
            $request->query('scheme', 'http') . '://' .
            $request->query('host', '') .
            $request->query('return', ''),
            "\/?"
        );
        $sep = str_contains($return, '?') ? '&' : '?';

        Log::info('/authenticate: issued passport PAT', ['token_id' => $tokenId]);

        return redirect()->to($return . $sep . 'ticket=' . $plain);
    });
});

Route::post('logout', App\Livewire\Actions\Logout::class)
    ->name('logout');
