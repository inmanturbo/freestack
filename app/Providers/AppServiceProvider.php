<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->setupEloquent();
        $this->setupPassport();
    }

    protected function setupEloquent()
    {
        Model::unguard();
    }

    protected function setupPassport()
    {
        // Personal Access Tokens TTL (edge tickets)
        Passport::personalAccessTokensExpireIn(now()->addMinutes(120));

        // OPTIONAL: restrict scopes globally
        Passport::tokensCan([
            'edge' => 'Used by the EdgeAuth gateway',
            'read' => 'Read Data',
            'write' => 'Write Data',
        ]);

        Passport::defaultScopes(['edge']);

        // Configure headless views for custom OAuth flow
        Passport::authorizationView('passport.oauth.authorize');
    }
}
