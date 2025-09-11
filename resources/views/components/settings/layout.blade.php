<div x-data="{ sidebarOpen: true }" class="flex items-start max-md:flex-col">
    <div x-show="sidebarOpen" x-transition class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist>
            <flux:navlist.item :href="route('settings.profile')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item :href="route('settings.password')" wire:navigate>{{ __('Password') }}</flux:navlist.item>
            <flux:navlist.item :href="route('settings.sessions')" wire:navigate>{{ __('Sessions') }}</flux:navlist.item>
            <flux:navlist.item :href="route('settings.appearance')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
            <flux:navlist.item :href="route('settings.api')" wire:navigate>{{ __('API Tokens') }}</flux:navlist.item>
            <flux:navlist.item :href="route('settings.oauth')" wire:navigate>{{ __('OAuth Apps') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <div class="flex items-center gap-4 mb-4">
            <flux:button 
                x-show="sidebarOpen"
                x-on:click="sidebarOpen = false" 
                variant="ghost" 
                size="sm" 
                icon="chevron-down"
                class="md:inline-block"
                :title="__('Close sidebar')"
                :aria-label="__('Close sidebar')"
            >
            </flux:button>
            <flux:button 
                x-show="!sidebarOpen"
                x-on:click="sidebarOpen = true" 
                variant="ghost" 
                size="sm" 
                icon="chevron-right"
                class="md:inline-block"
                :title="__('Open sidebar')"
                :aria-label="__('Open sidebar')"
            >
            </flux:button>
            <div class="flex-1">
                <flux:heading>{{ $heading ?? '' }}</flux:heading>
                <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>
            </div>
        </div>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
