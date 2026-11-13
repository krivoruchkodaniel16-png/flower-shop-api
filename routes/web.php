<?php

use Illuminate\Support\Facades\Route;

// Клиентская часть — отдельное SPA-приложение (Vue). Blade-шаблоны не используются.
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'api' => url('/api'),
    'health' => url('/api/health'),
]));
