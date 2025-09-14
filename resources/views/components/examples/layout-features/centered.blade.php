<x-layouts.app.header container :title="$title ?? null">
    <flux:main container>
        {{ $slot }}
    </flux:main>
</x-layouts.app.header>