<?php

return [
    'edge_shared_secret' => env('EDGE_SHARED_SECRET', ''),
    'proxies' => explode(',', env('EDGE_PROXIES', '')),
];
