<?php

return [
    'paths' => ['api/*', 'uploads/*'],
    'allowed_methods' => ['*'],
    // Baca langsung dari env agar tidak bergantung pada urutan pemuatan file config.
    'allowed_origins' => array_map('trim', explode(',', env('CORS_ORIGIN', 'http://localhost:3000'))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
