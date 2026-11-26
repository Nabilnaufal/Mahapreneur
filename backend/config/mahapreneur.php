<?php

return [
    // URL aplikasi frontend (dipakai untuk tautan email).
    'app_url' => env('FRONTEND_URL', 'http://localhost:3000'),
    // URL backend ini (dipakai untuk URL gambar dan callback DOKU).
    'api_url' => env('API_URL', 'http://localhost:8000'),
    'cors_origins' => array_map('trim', explode(',', env('CORS_ORIGIN', 'http://localhost:3000'))),

    'upload_dir' => 'uploads',
    'max_upload_kb' => (int) env('MAX_UPLOAD_MB', 5) * 1024,

    'auth_cookie' => 'mahapreneur_token',
    'auth_ttl_days' => (int) env('AUTH_TTL_DAYS', 7),

    'email_verification_ttl_hours' => (int) env('EMAIL_VERIFICATION_TTL_HOURS', 24),
    'password_reset_ttl_minutes' => (int) env('PASSWORD_RESET_TTL_MINUTES', 60),
    'mail_enabled' => env('MAIL_MAILER', 'resend') === 'resend'
        && filled(env('RESEND_API_KEY')),

    'doku' => [
        'client_id' => env('DOKU_CLIENT_ID'),
        'secret_key' => env('DOKU_SECRET_KEY'),
        'base_url' => env('DOKU_BASE_URL', 'https://api-sandbox.doku.com'),
        'va_channel' => env('DOKU_VA_CHANNEL', 'BCA'),
        'payment_ttl_minutes' => (int) env('PAYMENT_TTL_MINUTES', 60),
        // Metode online yang ditawarkan ke pembeli. Hanya aktifkan channel yang
        // sudah aktif di akun DOKU; channel nonaktif ditolak "PAYMENT CHANNEL IS INACTIVE".
        'methods' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('DOKU_METHODS', 'OVO,DANA')),
        ))),
        'channels' => [
            'OVO' => 'EMONEY_OVO',
            'DANA' => 'EMONEY_DANA',
            'SHOPEEPAY' => 'EMONEY_SHOPEEPAY',
            'QRIS' => env('DOKU_QRIS_CHANNEL', 'QRIS'),
            'VIRTUAL_ACCOUNT' => 'VIRTUAL_ACCOUNT_'.env('DOKU_VA_CHANNEL', 'BCA'),
        ],
    ],
];
