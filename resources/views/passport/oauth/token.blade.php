<?php

use Livewire\Volt\Component;

new class extends Component {
    public $token;
    public $client;
    public $scopes;

    public function mount($token, $client = null, $scopes = [])
    {
        $this->token = $token;
        $this->client = $client;
        $this->scopes = $scopes;
    }

    public function copyToken()
    {
        $this->dispatch('token-copied');
    }

    public function goToDashboard()
    {
        return redirect()->route('dashboard');
    }

    public function goToSettings()
    {
        return redirect()->route('settings.api');
    }
}; ?>

<x-layouts.auth>
    <div class="max-w-2xl mx-auto py-12">
        <div class="bg-white dark:bg-gray-900 shadow-xl rounded-lg overflow-hidden">
            <!-- Header -->
            <div class="px-8 py-6 bg-gradient-to-r from-green-600 to-emerald-700 text-white">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-white/10 rounded-full">
                        <flux:icon.check-circle class="w-8 h-8" />
                    </div>
                    <div>
                        <flux:heading size="lg" class="text-white">
                            {{ __('Authorization Successful') }}
                        </flux:heading>
                        <flux:text class="text-green-100">
                            {{ __('Your access token has been generated') }}
                        </flux:text>
                    </div>
                </div>
            </div>

            <div class="px-8 py-6">
                <!-- Success Message -->
                <div class="mb-8 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <div class="flex items-start space-x-3">
                        <flux:icon.check-circle class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" />
                        <div>
                            <flux:text class="text-green-800 dark:text-green-200">
                                @if($client)
                                    {{ __('You have successfully authorized :app to access your account.', ['app' => $client->name]) }}
                                @else
                                    {{ __('Your access token has been successfully generated.') }}
                                @endif
                            </flux:text>
                        </div>
                    </div>
                </div>

                @if($client)
                    <!-- Application Info -->
                    <div class="mb-8">
                        <flux:heading size="sm" class="mb-4 flex items-center">
                            <flux:icon.cube class="w-5 h-5 mr-2 text-gray-500" />
                            {{ __('Authorized Application') }}
                        </flux:heading>

                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <flux:text class="font-medium">{{ $client->name }}</flux:text>
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400 block mt-1">
                                {{ __('Client ID: :id', ['id' => $client->id]) }}
                            </flux:text>
                        </div>
                    </div>
                @endif

                @if(!empty($scopes))
                    <!-- Granted Permissions -->
                    <div class="mb-8">
                        <flux:heading size="sm" class="mb-4 flex items-center">
                            <flux:icon.key class="w-5 h-5 mr-2 text-gray-500" />
                            {{ __('Granted Permissions') }}
                        </flux:heading>

                        <div class="space-y-2">
                            @foreach($scopes as $scope)
                                <div class="flex items-center space-x-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                    <flux:icon.check class="w-4 h-4 text-green-500 flex-shrink-0" />
                                    <flux:text class="font-medium">{{ ucfirst($scope) }}</flux:text>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($token)
                    <!-- Access Token -->
                    <div class="mb-8">
                        <flux:heading size="sm" class="mb-4 flex items-center">
                            <flux:icon.key class="w-5 h-5 mr-2 text-gray-500" />
                            {{ __('Access Token') }}
                        </flux:heading>

                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-2">
                                <flux:text size="sm" class="font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('Bearer Token') }}
                                </flux:text>
                                <flux:button
                                    size="xs"
                                    variant="outline"
                                    onclick="navigator.clipboard.writeText('{{ $token }}').then(() => {
                                        this.textContent = 'Copied!';
                                        setTimeout(() => this.textContent = 'Copy', 2000);
                                    })"
                                >
                                    {{ __('Copy') }}
                                </flux:button>
                            </div>

                            <div class="font-mono text-sm bg-white dark:bg-gray-900 rounded border p-3 break-all">
                                {{ $token }}
                            </div>

                            <flux:text size="xs" class="text-gray-500 dark:text-gray-400 mt-2 block">
                                {{ __('Keep this token secure. It provides access to your account.') }}
                            </flux:text>
                        </div>
                    </div>
                @endif

                <!-- Next Steps -->
                <div class="mb-8">
                    <flux:heading size="sm" class="mb-4 flex items-center">
                        <flux:icon.light-bulb class="w-5 h-5 mr-2 text-amber-500" />
                        {{ __('What\'s Next?') }}
                    </flux:heading>

                    <div class="space-y-3">
                        <div class="flex items-start space-x-3">
                            <flux:icon.arrow-right class="w-4 h-4 text-gray-400 mt-1 flex-shrink-0" />
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                @if($client)
                                    {{ __('You can now use :app with your account. The application will handle authentication automatically.', ['app' => $client->name]) }}
                                @else
                                    {{ __('You can use this access token to authenticate API requests to your account.') }}
                                @endif
                            </flux:text>
                        </div>
                        <div class="flex items-start space-x-3">
                            <flux:icon.arrow-right class="w-4 h-4 text-gray-400 mt-1 flex-shrink-0" />
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                {{ __('You can manage your authorized applications and tokens in your account settings.') }}
                            </flux:text>
                        </div>
                        <div class="flex items-start space-x-3">
                            <flux:icon.arrow-right class="w-4 h-4 text-gray-400 mt-1 flex-shrink-0" />
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                {{ __('You can revoke access at any time from the OAuth applications settings.') }}
                            </flux:text>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <flux:button
                        wire:click="goToDashboard"
                        variant="primary"
                        size="lg"
                        class="flex-1 justify-center"
                        icon="home"
                    >
                        {{ __('Go to Dashboard') }}
                    </flux:button>

                    <flux:button
                        wire:click="goToSettings"
                        variant="outline"
                        size="lg"
                        class="flex-1 justify-center"
                        icon="cog-6-tooth"
                    >
                        {{ __('Manage Tokens') }}
                    </flux:button>
                </div>

                <!-- Security Notice -->
                <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                    <div class="flex items-start space-x-3">
                        <flux:icon.shield-exclamation class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                        <div>
                            <flux:text size="sm" class="text-amber-800 dark:text-amber-200">
                                <strong>{{ __('Security Reminder:') }}</strong>
                                {{ __('Keep your access tokens secure and never share them publicly. You can revoke access at any time from your settings.') }}
                            </flux:text>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.auth>