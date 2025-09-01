<?php

use App\EdgeAuthSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Livewire\Volt\Component;

new class extends Component {
    public $sessions = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadSessions();
    }

    /**
     * Load all active sessions for the current user
     */
    public function loadSessions(): void
    {
        $user = Auth::user();
        
        if (!$user) {
            $this->sessions = [];
            return;
        }

        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->get();

        $this->sessions = $sessions->map(function ($session) {
            $userAgent = $session->user_agent ?? '';
            $edge = EdgeAuthSession::makeForSessionId($session->id);

            return (object) [
                'id' => $session->id,
                'app' => $edge->get('app_host'),
                'user_agent' => $userAgent,
                'last_activity' => $session->last_activity,
                'last_active' => \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                'is_current' => $session->id === Session::getId(),
                'device_info' => $this->parseUserAgent($userAgent),
            ];
        })->all();
    }

    /**
     * Simple user agent parsing
     */
    private function parseUserAgent(string $userAgent): array
    {
        $info = [
            'browser' => 'Unknown',
            'platform' => 'Unknown',
            'device_type' => 'desktop'
        ];

        // Detect browser
        if (str_contains($userAgent, 'Chrome')) {
            $info['browser'] = 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $info['browser'] = 'Firefox';
        } elseif (str_contains($userAgent, 'Safari') && !str_contains($userAgent, 'Chrome')) {
            $info['browser'] = 'Safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            $info['browser'] = 'Edge';
        }

        // Detect platform
        if (str_contains($userAgent, 'Windows')) {
            $info['platform'] = 'Windows';
        } elseif (str_contains($userAgent, 'Mac OS')) {
            $info['platform'] = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $info['platform'] = 'Linux';
        } elseif (str_contains($userAgent, 'iPhone')) {
            $info['platform'] = 'iOS';
            $info['device_type'] = 'mobile';
        } elseif (str_contains($userAgent, 'iPad')) {
            $info['platform'] = 'iOS';
            $info['device_type'] = 'tablet';
        } elseif (str_contains($userAgent, 'Android')) {
            $info['platform'] = 'Android';
            $info['device_type'] = str_contains($userAgent, 'Mobile') ? 'mobile' : 'tablet';
        }

        return $info;
    }

    /**
     * Logout a specific session
     */
    public function logoutSession(string $sessionId): void
    {
        $user = Auth::user();
        
        Validator::make(
            ['user' => $user], 
            ['user' => 'required'],
        )->validate();

        if ($sessionId === Session::getId()) {
            $this->dispatch('session-logout-error', message: 'Cannot logout current session. Use the main logout instead.');
            return;
        }

        EdgeAuthSession::makeForSessionId($sessionId)
            ->destroyThisSessionAndToken();

        // Reload sessions
        $this->loadSessions();

        $this->dispatch('session-logged-out', message: 'Session logged out successfully.');
    }

    /**
     * Logout all other sessions except the current one
     */
    public function logoutAllOtherSessions(): void
    {
        $user = Auth::user();
        
        Validator::make(
            ['user' => $user], 
            ['user' => 'required'],
        )->validate();

        EdgeAuthSession::makeForCurrentSession()->destroyAllOtherSessionsAndTokens();

        $this->loadSessions();

        $this->dispatch('sessions-logged-out', message: 'All other sessions logged out successfully.');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Active Sessions')" :subheading="__('Manage your active sessions across different devices')">
        <div class="my-6 w-full space-y-6">
            @if (count($sessions) > 1)
                <div class="mb-6">
                    <flux:button 
                        wire:click="logoutAllOtherSessions" 
                        variant="danger" 
                        wire:confirm="Are you sure you want to logout all other sessions?"
                        size="sm"
                    >
                        {{ __('Logout All Other Sessions') }}
                    </flux:button>
                </div>
            @endif

            <div class="space-y-4">
                @forelse ($sessions as $session)
                    <flux:card class="p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    @if ($session->is_current)
                                        <flux:badge color="green" size="sm">{{ __('Current Session') }}</flux:badge>
                                    @endif
                                    
                                    @if ($session->device_info['device_type'] === 'mobile')
                                        <flux:icon.device-phone-mobile class="w-4 h-4 text-gray-500" />
                                    @elseif ($session->device_info['device_type'] === 'tablet')
                                        <flux:icon.device-tablet class="w-4 h-4 text-gray-500" />
                                    @else
                                        <flux:icon.computer-desktop class="w-4 h-4 text-gray-500" />
                                    @endif
                                </div>
                                
                                <div class="space-y-1">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ $session->device_info['browser'] }}
                                    </div>
                                    
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $session->device_info['platform'] }}
                                    </div>
                                    
                                    <div class="text-sm text-gray-500 dark:text-gray-500">
                                        APP: {{ $session->app }} • Last active {{ $session->last_active }}
                                    </div>
                                </div>
                            </div>

                            @if (!$session->is_current)
                                <flux:button 
                                    wire:click="logoutSession('{{ $session->id }}')" 
                                    variant="danger" 
                                    size="sm"
                                    wire:confirm="Are you sure you want to logout this session?"
                                >
                                    {{ __('Logout') }}
                                </flux:button>
                            @endif
                        </div>
                    </flux:card>
                @empty
                    <flux:card class="p-8 text-center">
                        <flux:icon.device-phone-mobile class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                        <flux:heading size="lg">{{ __('No Active Sessions') }}</flux:heading>
                        <flux:subheading>{{ __('You don\'t have any active sessions.') }}</flux:subheading>
                    </flux:card>
                @endforelse
            </div>

            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                    {{ __('Sessions will automatically expire after being inactive for more than 2 hours.') }}
                </flux:text>
            </div>
        </div>

        <!-- Toast Messages -->
        <div x-data="{ show: false, message: '' }" 
             x-on:session-logged-out.window="show = true; message = $event.detail.message; setTimeout(() => show = false, 3000)"
             x-on:sessions-logged-out.window="show = true; message = $event.detail.message; setTimeout(() => show = false, 3000)"
             x-on:session-logout-error.window="show = true; message = $event.detail.message; setTimeout(() => show = false, 5000)">
            <div x-show="show" 
                 x-transition:enter="transform ease-out duration-300 transition"
                 x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                 x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed bottom-4 right-4 max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5">
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <flux:icon.check-circle class="h-6 w-6 text-green-400" />
                        </div>
                        <div class="ml-3 w-0 flex-1 pt-0.5">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="message"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-settings.layout>
</section>