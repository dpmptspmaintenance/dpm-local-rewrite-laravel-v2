<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    |
    | Dipakai API eksternal (Chrome extension) — extension mengirim header
    | X-API-Key dari origin chrome-extension://<id>. Autentikasi berbasis
    | header (bukan cookie), jadi credentials tidak diperlukan.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    // Origin aplikasi sendiri + semua extension Chrome.
    'allowed_origins' => [
        'https://kepegawaian.it-dpmptsp.my.id',
    ],

    'allowed_origins_patterns' => [
        '#^chrome-extension://[a-z0-9]+$#i',
        '#^http://localhost(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];
