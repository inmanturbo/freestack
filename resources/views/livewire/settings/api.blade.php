<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Component;
use Livewire\Attributes\Validate;

new class extends Component {
    #[Validate('required|string|max:255')]
    public string $name = '';
    
    #[Validate('array')]
    public array $scopes = [];
    
    public array $tokens = [];
    public bool $showCreateForm = false;
    public ?string $newTokenValue = null;

    public function mount()
    {
        $this->loadTokens();
    }

    public function loadTokens()
    {
        $response = $this->api()->get('/api/access-tokens');
        
        if ($response->successful()) {
            $this->tokens = $response->json('data', []);
        } else {
            $this->tokens = [];
            $errorMessage = $response->json('message', 'Failed to load tokens');
            session()->flash('error', $errorMessage);
        }
    }

    public function createToken()
    {
        $this->validate();

        $response = $this->api()->post('/api/access-tokens', [
            'name' => $this->name,
            'scopes' => $this->scopes,
        ]);
        
        if ($response->successful()) {
            $tokenData = $response->json('data');
            $this->newTokenValue = $tokenData['access_token'];
            $this->name = '';
            $this->scopes = [];
            $this->showCreateForm = false;
            
            $this->loadTokens();
            $this->dispatch('token-created');
        } else {
            $errorMessage = $response->json('message', 'Failed to create token');
            session()->flash('error', $errorMessage);
        }
    }

    public function revokeToken($tokenId)
    {
        $response = $this->api()->post("/api/access-tokens/{$tokenId}/revoke");
        
        if ($response->successful()) {
            $this->loadTokens();
            $this->dispatch('token-revoked');
        } else {
            session()->flash('error', 'Failed to revoke token');
        }
    }

    public function deleteToken($tokenId)
    {
        $response = $this->api()->delete("/api/access-tokens/{$tokenId}");
        
        if ($response->successful()) {
            $this->loadTokens();
            $this->dispatch('token-deleted');
        } else {
            session()->flash('error', 'Failed to delete token');
        }
    }

    public function toggleCreateForm()
    {
        $this->showCreateForm = !$this->showCreateForm;
        $this->newTokenValue = null;
    }

    public function copyToken()
    {
        $this->newTokenValue = null;
    }

    protected function api()
    {
        $token = session('api_token'); // minted by middleware
        return Http::withToken($token)->acceptJson()->baseUrl(url(''));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('API Access Tokens')" :subheading="__('Manage your API access tokens for authentication with external applications')">
            @if (session()->has('error'))
                <div class="rounded-md bg-red-50 dark:bg-red-950 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <flux:icon.exclamation-circle class="size-5 text-red-400 dark:text-red-300" />
                        </div>
                        <div class="ml-3">
                            <flux:text class="text-sm font-medium text-red-800 dark:text-red-200">
                                {{ session('error') }}
                            </flux:text>
                        </div>
                    </div>
                </div>
            @endif

            @if ($newTokenValue)
                <div class="rounded-md bg-blue-50 dark:bg-blue-950 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <flux:icon.information-circle class="size-5 text-blue-400 dark:text-blue-300" />
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                {{ __('New Token Created') }}
                            </h3>
                            <div class="mt-2">
                                <flux:text class="text-sm text-blue-700 dark:text-blue-300">
                                    {{ __('Please copy your new access token. For security reasons, it won\'t be shown again.') }}
                                </flux:text>
                                <div class="mt-3 flex items-center space-x-2">
                                    <flux:input readonly value="{{ $newTokenValue }}" class="bg-gray-50 dark:bg-gray-800" />
                                    <flux:button type="button" wire:click="copyToken" onclick="navigator.clipboard.writeText('{{ $newTokenValue }}')" size="sm">
                                        {{ __('Copy') }}
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex justify-between items-center">
                <flux:text variant="body" class="font-medium">{{ __('Your Tokens') }}</flux:text>
                <flux:button wire:click="toggleCreateForm" variant="primary" size="sm">
                    {{ $showCreateForm ? __('Cancel') : __('Create New Token') }}
                </flux:button>
            </div>

            @if ($showCreateForm)
                <div class="rounded-md border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 p-4">
                    <flux:text variant="body" class="font-medium mb-4">{{ __('Create New Access Token') }}</flux:text>
                    <form wire:submit="createToken" class="space-y-4">
                        <flux:input wire:model="name" :label="__('Token Name')" type="text" required placeholder="{{ __('My App Token') }}" />
                        
                        <div>
                            <flux:text variant="body" class="font-medium mb-2">{{ __('Scopes (Optional)') }}</flux:text>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <flux:checkbox wire:model="scopes" value="read" />
                                    <flux:text class="ml-2">{{ __('Read access') }}</flux:text>
                                </label>
                                <label class="flex items-center">
                                    <flux:checkbox wire:model="scopes" value="write" />
                                    <flux:text class="ml-2">{{ __('Write access') }}</flux:text>
                                </label>
                            </div>
                        </div>
                        
                        <div class="flex space-x-2">
                            <flux:button type="submit" variant="primary">{{ __('Create Token') }}</flux:button>
                        </div>
                    </form>
                </div>
            @endif

            @if (empty($tokens))
                <div class="text-center py-8">
                    <flux:icon.key class="mx-auto size-12 text-gray-400 dark:text-gray-500" />
                    <flux:text variant="body" class="mt-2 font-medium">{{ __('No access tokens') }}</flux:text>
                    <flux:text variant="caption" class="mt-1">{{ __('Get started by creating a new access token.') }}</flux:text>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($tokens as $token)
                            <li class="px-4 py-4 flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3">
                                        <flux:text variant="body" class="font-medium truncate">
                                            {{ $token['name'] }}
                                        </flux:text>
                                        @if ($token['revoked'])
                                            <flux:badge color="red">{{ __('Revoked') }}</flux:badge>
                                        @else
                                            <flux:badge color="green">{{ __('Active') }}</flux:badge>
                                        @endif
                                    </div>
                                    <div class="mt-1 flex items-center space-x-4">
                                        <flux:text variant="caption">{{ __('Created') }} {{ \Carbon\Carbon::parse($token['created_at'])->diffForHumans() }}</flux:text>
                                        @if ($token['scopes'])
                                            <flux:text variant="caption">{{ __('Scopes: :scopes', ['scopes' => implode(', ', $token['scopes'])]) }}</flux:text>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    @if (!$token['revoked'])
                                        <flux:button wire:click="revokeToken('{{ $token['id'] }}')" variant="outline" color="amber" size="xs">
                                            {{ __('Revoke') }}
                                        </flux:button>
                                    @endif
                                    <flux:button wire:click="deleteToken('{{ $token['id'] }}')" variant="outline" color="red" size="xs">
                                        {{ __('Delete') }}
                                    </flux:button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex items-center gap-4">
                <x-action-message class="me-3" on="token-created">
                    {{ __('Token created successfully.') }}
                </x-action-message>
                <x-action-message class="me-3" on="token-revoked">
                    {{ __('Token revoked successfully.') }}
                </x-action-message>
                <x-action-message class="me-3" on="token-deleted">
                    {{ __('Token deleted successfully.') }}
                </x-action-message>
            </div>
        </div>
    </x-settings.layout>
</section>
