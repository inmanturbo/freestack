<?php

use Inmanturbo\LayoutFeatures\Layout;

return [
    'active' => env('LAYOUT_FEATURES_ACTIVE', false),

    'default' => new Layout(
        'default',
        'Default',
        'examples.layout-features.default',
        'The default layout option. Minimal margins on desktops.',
        'bars-3-center-left',
    ),

    'options' => [
        'default' => new Layout(
            'default',
            'Default',
            'examples.layout-features.default',
            'The default layout option. Minimal margins on desktops.',
            'bars-3-center-left',
        ),
        'centered' => new Layout(
            'centered',
            'Centered',
            'examples.layout-features.centered',
            'The same as default, with the content centered.',
            'bars-3',
        ),
        'sidebar' => new Layout(
            'sidebar',
            'Sidebar',
            'examples.layout-features.sidebar',
            'Navigate using a sidebar.',
            'bars-3-bottom-left',
        ),
    ],
];
