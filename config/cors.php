<?php

/*
|--------------------------------------------------------------------------
| CORS (Cross-Origin Resource Sharing)
|--------------------------------------------------------------------------
| Клиент (Vue) работает на отдельном адресе (по умолчанию http://localhost:5173),
| поэтому браузер отправляет к API кросс-доменные запросы и preflight OPTIONS.
| Разрешаем только адреса клиента из .env (CORS_ALLOWED_ORIGINS), без «*».
| Авторизация выполняется Bearer-токеном, cookie не используются —
| поэтому supports_credentials = false.
*/

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173'))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];
