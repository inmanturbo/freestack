<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Component;
use Livewire\Attributes\Validate;

new class extends Component {
    #[Validate('required|string|max:255')]
    public string $name = '';
    
    public array $redirect_uris = [''];
    
    public array $applications = [];
    public bool $showCreateForm = false;
    public ?array $newApplication = null;

    public function mount()
    {
        $this->loadApplications();
    }

    public function loadApplications()
    {
        $response = $this->api()->get('/api/oauth-applications');
        
        if ($response->successful()) {
            $this->applications = $response->json('data', []);
        } else {
            $this->applications = [];
            $errorMessage = $response->json('message', 'Failed to load applications');
            session()->flash('error', $errorMessage);
        }
    }

    public function createApplication()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'redirect_uris' => 'required|array',
            'redirect_uris.*' => 'required|url',
        ]);

        // Filter out empty redirect URIs
        $filteredUris = array_filter($this->redirect_uris, fn($uri) => !empty(trim($uri)));

        $response = $this->api()->post('/api/oauth-applications', [
            'name' => $this->name,
            'redirect_uris' => $filteredUris,
        ]);
        
        if ($response->successful()) {
            $applicationData = $response->json('data');
            $this->newApplication = $applicationData;
            $this->name = '';
            $this->redirect_uris = [''];
            $this->showCreateForm = false;
            
            $this->loadApplications();
            $this->dispatch('application-created');
        } else {
            $errorMessage = $response->json('message', 'Failed to create application');
            session()->flash('error', $errorMessage);
        }
    }

    public function revokeApplication($applicationId)
    {
        $response = $this->api()->post("/api/oauth-applications/{$applicationId}/revoke");
        
        if ($response->successful()) {
            $this->loadApplications();
            $this->dispatch('application-revoked');
        } else {
            $errorMessage = $response->json('message', 'Failed to revoke application');
            session()->flash('error', $errorMessage);
        }
    }

    public function deleteApplication($applicationId)
    {
        $response = $this->api()->delete("/api/oauth-applications/{$applicationId}");
        
        if ($response->successful()) {
            $this->loadApplications();
            $this->dispatch('application-deleted');
        } else {
            $errorMessage = $response->json('message', 'Failed to delete application');
            session()->flash('error', $errorMessage);
        }
    }

    public function regenerateSecret($applicationId)
    {
        $response = $this->api()->post("/api/oauth-applications/{$applicationId}/regenerate-secret");
        
        if ($response->successful()) {
            $applicationData = $response->json('data');
            $this->newApplication = $applicationData;
            $this->loadApplications();
            $this->dispatch('secret-regenerated');
        } else {
            $errorMessage = $response->json('message', 'Failed to regenerate secret');
            session()->flash('error', $errorMessage);
        }
    }

    public function addRedirectUri()
    {
        $this->redirect_uris[] = '';
    }

    public function removeRedirectUri($index)
    {
        if (count($this->redirect_uris) > 1) {
            unset($this->redirect_uris[$index]);
            $this->redirect_uris = array_values($this->redirect_uris); // Re-index array
        }
    }

    public function toggleCreateForm()
    {
        $this->showCreateForm = !$this->showCreateForm;
        $this->newApplication = null;
        if (!$this->showCreateForm) {
            $this->redirect_uris = [''];
        }
    }

    public function closeNewApplicationAlert()
    {
        $this->newApplication = null;
    }

    protected function api()
    {
        $token = session('api_token'); // minted by middleware
        return Http::withToken($token)->acceptJson()->baseUrl(url(''));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('OAuth2 Applications')" :subheading="__('Manage OAuth2 applications for third-party integrations')">
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

            @if ($newApplication)
                <flux:card class="p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <flux:icon.information-circle class="size-5 text-blue-400" />
                        </div>
                        <div class="ml-3 flex-1">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <flux:heading size="sm" class="mb-2">
                                        {{ __('Application Details') }}
                                    </flux:heading>
                                    <flux:text class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        {{ __('Please copy your application credentials. The secret won\'t be shown again unless regenerated.') }}
                                    </flux:text>
                                </div>
                                <flux:button 
                                    wire:click="closeNewApplicationAlert" 
                                    variant="ghost" 
                                    size="sm"
                                >
                                    <flux:icon.x-mark class="size-4" />
                                </flux:button>
                            </div>
                            
                            <div class="space-y-3">
                                <div>
                                    <flux:text class="text-sm font-medium mb-1 block">
                                        {{ __('Client ID') }}
                                    </flux:text>
                                    <div class="flex items-center space-x-2">
                                        <flux:input readonly value="{{ $newApplication['id'] }}" class="flex-1" />
                                        <flux:button 
                                            type="button" 
                                            onclick="navigator.clipboard.writeText('{{ $newApplication['id'] }}')" 
                                            size="sm"
                                        >
                                            {{ __('Copy') }}
                                        </flux:button>
                                    </div>
                                </div>
                                
                                <div>
                                    <flux:text class="text-sm font-medium mb-1 block">
                                        {{ __('Client Secret') }}
                                    </flux:text>
                                    <div class="flex items-center space-x-2">
                                        <flux:input readonly value="{{ $newApplication['secret'] }}" class="flex-1" />
                                        <flux:button 
                                            type="button" 
                                            onclick="navigator.clipboard.writeText('{{ $newApplication['secret'] }}')" 
                                            size="sm"
                                        >
                                            {{ __('Copy') }}
                                        </flux:button>
                                    </div>
                                </div>
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
                    {{ $showCreateForm ? __('Cancel') : __('Create New Application') }}
                </flux:button>
            </div>

            @if ($showCreateForm)
                <flux:card class="p-4">
                    <flux:heading size="sm" class="mb-4">{{ __('Create New OAuth2 Application') }}</flux:heading>
                    <form wire:submit="createApplication" class="space-y-4">
                        <flux:input 
                            wire:model="name" 
                            :label="__('Application Name')" 
                            type="text" 
                            required 
                            placeholder="{{ __('My OAuth2 App') }}" 
                        />
                        
                        <div>
                            <flux:text class="font-medium mb-2 block">{{ __('Redirect URLs') }}</flux:text>
                            @foreach($redirect_uris as $index => $uri)
                                <div class="flex items-center space-x-2 mb-2">
                                    <flux:input 
                                        wire:model="redirect_uris.{{ $index }}" 
                                        type="url" 
                                        required 
                                        placeholder="https://example.com/callback" 
                                        class="flex-1"
                                    />
                                    @if(count($redirect_uris) > 1)
                                        <flux:button 
                                            type="button" 
                                            wire:click="removeRedirectUri({{ $index }})" 
                                            variant="outline" 
                                            color="red" 
                                            size="sm"
                                        >
                                            <flux:icon.x-mark class="size-4" />
                                        </flux:button>
                                    @endif
                                </div>
                                @error("redirect_uris.{$index}")
                                    <flux:text size="sm" class="text-red-600 mb-2">{{ $message }}</flux:text>
                                @enderror
                            @endforeach
                            
                            <flux:button 
                                type="button" 
                                wire:click="addRedirectUri" 
                                variant="outline" 
                                size="sm" 
                                class="mb-3"
                                icon="plus"
                            >
                                {{ __('Add Redirect URL') }}
                            </flux:button>
                            
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                {{ __('Redirect URLs are where users will be redirected after authorizing your application.') }}
                            </flux:text>
                        </div>
                        
                        <flux:button type="submit" variant="primary">{{ __('Create Application') }}</flux:button>
                    </form>
                </flux:card>
            @endif

            <div class="space-y-4">
                @forelse ($applications as $application)
                    <flux:card class="p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    @if ($application['revoked'])
                                        <flux:badge color="red" size="sm">{{ __('Revoked') }}</flux:badge>
                                    @else
                                        <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                                    @endif
                                    <flux:icon.cog-6-tooth class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                                </div>
                                
                                <div class="space-y-1">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ $application['name'] }}
                                    </div>
                                    
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        Client ID: {{ $application['id'] }}
                                    </div>
                                    
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        @if(is_array($application['redirect_uris']) && count($application['redirect_uris']) > 0)
                                            @if(count($application['redirect_uris']) === 1)
                                                Redirect: {{ $application['redirect_uris'][0] }}
                                            @else
                                                Redirects:
                                                @foreach($application['redirect_uris'] as $uri)
                                                    <div class="ml-2">• {{ $uri }}</div>
                                                @endforeach
                                            @endif
                                        @endif
                                    </div>
                                    
                                    <div class="text-sm text-gray-500 dark:text-gray-500">
                                        Created {{ \Carbon\Carbon::parse($application['created_at'])->diffForHumans() }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                @if (!$application['revoked'])
                                    <flux:button 
                                        wire:click="regenerateSecret('{{ $application['id'] }}')" 
                                        variant="outline" 
                                        size="sm"
                                        wire:confirm="Are you sure you want to regenerate the secret? This will invalidate the current secret."
                                    >
                                        {{ __('Regenerate Secret') }}
                                    </flux:button>
                                    <flux:button 
                                        wire:click="revokeApplication('{{ $application['id'] }}')" 
                                        variant="outline" 
                                        color="amber" 
                                        size="sm"
                                        wire:confirm="Are you sure you want to revoke this application?"
                                    >
                                        {{ __('Revoke') }}
                                    </flux:button>
                                @endif
                                <flux:button 
                                    wire:click="deleteApplication('{{ $application['id'] }}')" 
                                    variant="danger" 
                                    size="sm"
                                    wire:confirm="Are you sure you want to delete this application? This action cannot be undone."
                                >
                                    {{ __('Delete') }}
                                </flux:button>
                            </div>
                        </div>
                    </flux:card>
                @empty
                    <flux:card class="p-8 text-center">
                        <flux:icon.cog-6-tooth class="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-4" />
                        <flux:heading size="lg">{{ __('No OAuth2 Applications') }}</flux:heading>
                        <flux:subheading>{{ __('Get started by creating your first OAuth2 application.') }}</flux:subheading>
                    </flux:card>
                @endforelse
            </div>

            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                    {{ __('OAuth2 applications allow third-party services to authenticate users and access your API on their behalf. Keep client secrets secure and regenerate them if compromised.') }}
                </flux:text>
            </div>
        </div>

        <!-- Success Messages -->
        <div class="flex items-center gap-4">
            <x-action-message class="me-3" on="application-created">
                {{ __('Application created successfully.') }}
            </x-action-message>
            <x-action-message class="me-3" on="application-revoked">
                {{ __('Application revoked successfully.') }}
            </x-action-message>
            <x-action-message class="me-3" on="application-deleted">
                {{ __('Application deleted successfully.') }}
            </x-action-message>
            <x-action-message class="me-3" on="secret-regenerated">
                {{ __('Secret regenerated successfully.') }}
            </x-action-message>
        </div>
    </x-settings.layout>
</section>