<?php

return [
    'paths' => ['api/*', 'uploads/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => config('mahapreneur.cors_origins'),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
