<x-dynamic-component :component="Inmanturbo\LayoutFeatures\Layout::layoutComponent()" :title="$title ?? null">
    {{ $slot }}
</x-dynamic-component>