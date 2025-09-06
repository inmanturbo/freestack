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
        <div class="my-6 w-full space-y-6">
            @if (session()->has('error'))
                <div class="rounded-md bg-red-50 dark:bg-red-950 p-4">
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
                <flux:card class="p-4 bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <flux:icon.information-circle class="size-5 text-blue-400 dark:text-blue-300" />
                        </div>
                        <div class="ml-3 flex-1">
                            <flux:heading size="sm" class="text-blue-800 dark:text-blue-200 mb-2">
                                {{ __('New Token Created') }}
                            </flux:heading>
                            <flux:text class="text-sm text-blue-700 dark:text-blue-300 mb-3">
                                {{ __('Please copy your new access token. For security reasons, it won\'t be shown again.') }}
                            </flux:text>
                            <div class="flex items-center space-x-2">
                                <flux:input readonly value="{{ $newTokenValue }}" class="flex-1 bg-white dark:bg-gray-800 border-blue-300 dark:border-blue-600" />
                                <flux:button type="button" wire:click="copyToken" onclick="navigator.clipboard.writeText('{{ $newTokenValue }}')" size="sm">
                                    {{ __('Copy') }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </flux:card>
            @endif

            <div class="flex justify-between items-center mb-4">
                <div>
                    <!-- This space can be used for future controls -->
                </div>
                <flux:button wire:click="toggleCreateForm" variant="primary" size="sm">
                    {{ $showCreateForm ? __('Cancel') : __('Create New Token') }}
                </flux:button>
            </div>

            @if ($showCreateForm)
                <flux:card class="p-4">
                    <flux:heading size="sm" class="mb-4">{{ __('Create New Access Token') }}</flux:heading>
                    <form wire:submit="createToken" class="space-y-4">
                        <flux:input wire:model="name" :label="__('Token Name')" type="text" required placeholder="{{ __('My App Token') }}" />
                        
                        <div>
                            <flux:text class="font-medium mb-2 block">{{ __('Scopes (Optional)') }}</flux:text>
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
                        
                        <flux:button type="submit" variant="primary">{{ __('Create Token') }}</flux:button>
                    </form>
                </flux:card>
            @endif

            <div class="space-y-4">
                @forelse ($tokens as $token)
                    <flux:card class="p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    @if ($token['revoked'])
                                        <flux:badge color="red" size="sm">{{ __('Revoked') }}</flux:badge>
                                    @else
                                        <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                                    @endif
                                    <flux:icon.key class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                                </div>
                                
                                <div class="space-y-1">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ $token['name'] }}
                                    </div>
                                    
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        Created {{ \Carbon\Carbon::parse($token['created_at'])->diffForHumans() }}
                                    </div>
                                    
                                    @if ($token['scopes'])
                                        <div class="text-sm text-gray-500 dark:text-gray-500">
                                            Scopes: {{ implode(', ', $token['scopes']) }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                @if (!$token['revoked'])
                                    <flux:button 
                                        wire:click="revokeToken('{{ $token['id'] }}')" 
                                        variant="outline" 
                                        color="amber" 
                                        size="sm"
                                        wire:confirm="Are you sure you want to revoke this token?"
                                    >
                                        {{ __('Revoke') }}
                                    </flux:button>
                                @endif
                                <flux:button 
                                    wire:click="deleteToken('{{ $token['id'] }}')" 
                                    variant="danger" 
                                    size="sm"
                                    wire:confirm="Are you sure you want to delete this token?"
                                >
                                    {{ __('Delete') }}
                                </flux:button>
                            </div>
                        </div>
                    </flux:card>
                @empty
                    <flux:card class="p-8 text-center">
                        <flux:icon.key class="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-4" />
                        <flux:heading size="lg">{{ __('No Access Tokens') }}</flux:heading>
                        <flux:subheading>{{ __('Get started by creating a new access token.') }}</flux:subheading>
                    </flux:card>
                @endforelse
            </div>

            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                    {{ __('Access tokens provide secure access to your account via the API. Keep them safe and revoke any tokens you no longer need.') }}
                </flux:text>
            </div>
        </div>

        <!-- Success Messages -->
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
    </x-settings.layout>
</section>
