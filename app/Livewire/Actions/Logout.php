<?php

namespace App\Livewire\Actions;

use App\EdgeAuthSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke()
    {
        Auth::guard('web')->logout();

        EdgeAuthSession::makeForCurrentSession()->destroyThisSessionAndToken();

        Session::invalidate();
        Session::regenerateToken();

        return redirect('/');
    }
}
