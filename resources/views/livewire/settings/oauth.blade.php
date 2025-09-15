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
    public array $copied = []; // Track copied state for different fields

    // Edit functionality
    public ?string $editingApplicationId = null;
    public array $editingRedirectUris = [];

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

            $this->dispatch('application-created');

            // Load applications after dispatch to avoid clearing newApplication
            $this->loadApplications();
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

            // Also store in session as backup
            session()->flash('oauth_secret_regenerated', $applicationData);

            $this->dispatch('secret-regenerated');

            // Load applications after dispatch to avoid clearing newApplication
            $this->loadApplications();
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
        $this->cancelEditing(); // Close any active edit forms
        if (!$this->showCreateForm) {
            $this->redirect_uris = [''];
        }
    }

    public function closeNewApplicationAlert()
    {
        $this->newApplication = null;
        session()->forget('oauth_secret_regenerated');
    }

    public function startEditing($applicationId)
    {
        $application = collect($this->applications)->firstWhere('id', $applicationId);
        if ($application) {
            $this->editingApplicationId = $applicationId;
            $this->editingRedirectUris = $application['redirect_uris'] ?? [''];
            $this->showCreateForm = false; // Close create form if open
        }
    }

    public function cancelEditing()
    {
        $this->editingApplicationId = null;
        $this->editingRedirectUris = [];
    }

    public function saveRedirectUris()
    {
        $this->validate([
            'editingRedirectUris' => 'required|array',
            'editingRedirectUris.*' => 'required|url',
        ]);

        // Filter out empty redirect URIs
        $filteredUris = array_filter($this->editingRedirectUris, fn($uri) => !empty(trim($uri)));

        $response = $this->api()->put("/api/oauth-applications/{$this->editingApplicationId}", [
            'redirect_uris' => $filteredUris,
        ]);

        if ($response->successful()) {
            $this->editingApplicationId = null;
            $this->editingRedirectUris = [];
            $this->loadApplications();
            $this->dispatch('application-updated');
        } else {
            $errorMessage = $response->json('message', 'Failed to update redirect URIs');
            session()->flash('error', $errorMessage);
        }
    }

    public function addEditingRedirectUri()
    {
        $this->editingRedirectUris[] = '';
    }

    public function removeEditingRedirectUri($index)
    {
        if (count($this->editingRedirectUris) > 1) {
            unset($this->editingRedirectUris[$index]);
            $this->editingRedirectUris = array_values($this->editingRedirectUris); // Re-index array
        }
    }

    public function markCopied($key)
    {
        $this->copied[$key] = true;
        // Reset after 2 seconds using JS timeout
        $this->dispatch('copy-success', key: $key);
    }

    public function resetCopied($key)
    {
        unset($this->copied[$key]);
    }

    protected function api()
    {
        $token = session('api_token'); // minted by middleware

        return Http::withToken($token)->acceptJson()->baseUrl(url(''));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('OAuth Applications')" :subheading="__('Manage OAuth applications for third-party integrations')">
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

            @if ($newApplication || session()->has('oauth_secret_regenerated'))
                @php
                    $applicationData = $newApplication ?? session('oauth_secret_regenerated');
                @endphp
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
                            
                            <div class="space-y-4">
                                <!-- Client ID -->
                                <div class="space-y-2">
                                    <flux:subheading size="sm">Client ID</flux:subheading>
                                    <div class="flex flex-col space-y-2 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-2">
                                        <flux:input
                                            readonly
                                            value="{{ $applicationData['id'] }}"
                                            class="flex-1 font-mono text-sm"
                                            onclick="this.select()"
                                        />
                                        @if(isset($copied['new-client-id']))
                                            <div class="flex items-center justify-center w-16 h-8">
                                                <flux:icon.check class="size-4 text-green-600" />
                                            </div>
                                        @else
                                            <flux:button
                                                type="button"
                                                onclick="copyToClipboard('{{ $applicationData['id'] }}', 'new-client-id', '{{ $this->getId() }}')"
                                                size="sm"
                                                variant="outline"
                                            >
                                                Copy
                                            </flux:button>
                                        @endif
                                    </div>
                                </div>

                                <!-- Client Secret -->
                                <div class="space-y-2">
                                    <flux:subheading size="sm">Client Secret</flux:subheading>
                                    <div class="flex flex-col space-y-2 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-2">
                                        <flux:input
                                            readonly
                                            value="{{ $applicationData['secret'] }}"
                                            class="flex-1 font-mono text-sm"
                                            onclick="this.select()"
                                        />
                                        @if(isset($copied['new-client-secret']))
                                            <div class="flex items-center justify-center w-16 h-8">
                                                <flux:icon.check class="size-4 text-green-600" />
                                            </div>
                                        @else
                                            <flux:button
                                                type="button"
                                                onclick="copyToClipboard('{{ $applicationData['secret'] }}', 'new-client-secret', '{{ $this->getId() }}')"
                                                size="sm"
                                                variant="outline"
                                            >
                                                Copy
                                            </flux:button>
                                        @endif
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
                    <flux:heading size="sm" class="mb-4">{{ __('Create New OAuth Application') }}</flux:heading>
                    <form wire:submit="createApplication" class="space-y-4">
                        <flux:input 
                            wire:model="name" 
                            :label="__('Application Name')" 
                            type="text" 
                            required 
                            placeholder="{{ __('My OAuth App') }}" 
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
                        @if ($editingApplicationId === $application['id'])
                            <!-- Edit Form -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <flux:heading size="sm">{{ __('Edit Redirect URIs') }}</flux:heading>
                                    <flux:button
                                        wire:click="cancelEditing"
                                        variant="ghost"
                                        size="sm"
                                    >
                                        <flux:icon.x-mark class="size-4" />
                                    </flux:button>
                                </div>

                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    <strong>{{ $application['name'] }}</strong> ({{ $application['id'] }})
                                </div>

                                <form wire:submit="saveRedirectUris" class="space-y-4">
                                    <div>
                                        <flux:text class="font-medium mb-2 block">{{ __('Redirect URLs') }}</flux:text>
                                        @foreach($editingRedirectUris as $index => $uri)
                                            <div class="flex items-center space-x-2 mb-2">
                                                <flux:input
                                                    wire:model="editingRedirectUris.{{ $index }}"
                                                    type="url"
                                                    required
                                                    placeholder="https://example.com/callback"
                                                    class="flex-1"
                                                />
                                                @if(count($editingRedirectUris) > 1)
                                                    <flux:button
                                                        type="button"
                                                        wire:click="removeEditingRedirectUri({{ $index }})"
                                                        variant="outline"
                                                        color="red"
                                                        size="sm"
                                                    >
                                                        <flux:icon.x-mark class="size-4" />
                                                    </flux:button>
                                                @endif
                                            </div>
                                            @error("editingRedirectUris.{$index}")
                                                <flux:text size="sm" class="text-red-600 mb-2">{{ $message }}</flux:text>
                                            @enderror
                                        @endforeach

                                        <flux:button
                                            type="button"
                                            wire:click="addEditingRedirectUri"
                                            variant="outline"
                                            size="sm"
                                            class="mb-3"
                                            icon="plus"
                                        >
                                            {{ __('Add Redirect URL') }}
                                        </flux:button>
                                    </div>

                                    <div class="flex space-x-2">
                                        <flux:button type="submit" variant="primary" size="sm">
                                            {{ __('Save Changes') }}
                                        </flux:button>
                                        <flux:button
                                            type="button"
                                            wire:click="cancelEditing"
                                            variant="outline"
                                            size="sm"
                                        >
                                            {{ __('Cancel') }}
                                        </flux:button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <!-- Normal Display -->
                            <div class="space-y-4">
                                <!-- Header with title, status, and actions -->
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-3">
                                        <div>
                                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 text-lg">
                                                {{ $application['name'] }}
                                            </h3>
                                            <div class="flex items-center gap-2 mt-1">
                                                @if ($application['revoked'])
                                                    <flux:badge color="red" size="sm">{{ __('Revoked') }}</flux:badge>
                                                @else
                                                    <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                                                @endif
                                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                                    Created {{ \Carbon\Carbon::parse($application['created_at'])->diffForHumans() }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions Dropdown -->
                                    <flux:dropdown position="bottom-end">
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />

                                        <flux:menu>
                                            @if (!$application['revoked'])
                                                <flux:menu.item wire:click="startEditing('{{ $application['id'] }}')">
                                                    <flux:icon.pencil-square class="size-4" />
                                                    Edit Redirects
                                                </flux:menu.item>
                                                <flux:menu.item
                                                    wire:click="regenerateSecret('{{ $application['id'] }}')"
                                                    wire:confirm="Are you sure you want to regenerate the secret? This will invalidate the current secret."
                                                >
                                                    <flux:icon.key class="size-4" />
                                                    Regenerate Secret
                                                </flux:menu.item>
                                                <flux:menu.separator />
                                                <flux:menu.item
                                                    wire:click="revokeApplication('{{ $application['id'] }}')"
                                                    wire:confirm="Are you sure you want to revoke this application?"
                                                    variant="danger"
                                                >
                                                    <flux:icon.no-symbol class="size-4" />
                                                    Revoke
                                                </flux:menu.item>
                                            @endif
                                            <flux:menu.item
                                                wire:click="deleteApplication('{{ $application['id'] }}')"
                                                wire:confirm="Are you sure you want to delete this application? This action cannot be undone."
                                                variant="danger"
                                            >
                                                <flux:icon.trash class="size-4" />
                                                Delete
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>

                                <!-- Client ID -->
                                <div class="space-y-2">
                                    <flux:subheading size="sm">Client ID</flux:subheading>
                                    <div class="flex flex-col space-y-2 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-2">
                                        <flux:input
                                            readonly
                                            value="{{ $application['id'] }}"
                                            class="flex-1 font-mono text-sm"
                                            onclick="this.select()"
                                        />
                                        @if(isset($copied['client-id-' . $application['id']]))
                                            <div class="flex items-center justify-center w-16 h-8">
                                                <flux:icon.check class="size-4 text-green-600" />
                                            </div>
                                        @else
                                            <flux:button
                                                type="button"
                                                size="sm"
                                                variant="outline"
                                                onclick="copyToClipboard('{{ $application['id'] }}', 'client-id-{{ $application['id'] }}', '{{ $this->getId() }}')"
                                            >
                                                Copy
                                            </flux:button>
                                        @endif
                                    </div>
                                </div>

                                <!-- Redirect URIs -->
                                @if(is_array($application['redirect_uris']) && count($application['redirect_uris']) > 0)
                                    <div class="space-y-2">
                                        <flux:subheading size="sm">
                                            {{ count($application['redirect_uris']) === 1 ? 'Redirect URI' : 'Redirect URIs' }}
                                        </flux:subheading>
                                        <div class="space-y-2">
                                            @foreach($application['redirect_uris'] as $uri)
                                                <div class="flex flex-col space-y-2 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-2">
                                                    <flux:input
                                                        readonly
                                                        value="{{ $uri }}"
                                                        class="flex-1 font-mono text-sm"
                                                        onclick="this.select()"
                                                    />
                                                    @if(isset($copied['uri-' . $application['id'] . '-' . $loop->index]))
                                                        <div class="flex items-center justify-center w-16 h-8">
                                                            <flux:icon.check class="size-4 text-green-600" />
                                                        </div>
                                                    @else
                                                        <flux:button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            onclick="copyToClipboard('{{ $uri }}', 'uri-{{ $application['id'] }}-{{ $loop->index }}', '{{ $this->getId() }}')"
                                                        >
                                                            Copy
                                                        </flux:button>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </flux:card>
                @empty
                    <flux:card class="p-8 text-center">
                        <flux:icon.cog-6-tooth class="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-4" />
                        <flux:heading size="lg">{{ __('No OAuth Applications') }}</flux:heading>
                        <flux:subheading>{{ __('Get started by creating your first OAuth application.') }}</flux:subheading>
                    </flux:card>
                @endforelse
            </div>

            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                    {{ __('OAuth applications allow third-party services to authenticate users and access your API on their behalf. Keep client secrets secure and regenerate them if compromised.') }}
                </flux:text>
            </div>
        </div>

        <!-- Success Messages -->
        <div class="flex items-center gap-4">
            <x-action-message class="me-3" on="application-created">
                {{ __('Application created successfully.') }}
            </x-action-message>
            <x-action-message class="me-3" on="application-updated">
                {{ __('Redirect URIs updated successfully.') }}
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

<script>
// Global clipboard utility function with fallback
window.copyToClipboard = function(text, key, wireId) {
    // Find the correct Livewire component instance
    const component = window.Livewire.find(wireId);

    function handleSuccess() {
        if (component) {
            component.call('markCopied', key);
        }
    }

    function fallbackCopy() {
        // Create a temporary textarea
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            document.execCommand('copy');
            handleSuccess();
        } catch (err) {
            console.error('Fallback copy failed: ', err);
        }

        document.body.removeChild(textArea);
    }

    if (navigator.clipboard && window.isSecureContext) {
        // Modern clipboard API
        navigator.clipboard.writeText(text).then(() => {
            handleSuccess();
        }).catch(err => {
            console.error('Failed to copy: ', err);
            fallbackCopy();
        });
    } else {
        // Fallback for older browsers or non-secure contexts
        fallbackCopy();
    }
};
</script>

@script
<script>
$wire.on('copy-success', (event) => {
    // Reset the copied state after 2 seconds
    setTimeout(() => {
        $wire.call('resetCopied', event.key);
    }, 2000);
});
</script>
@endscript