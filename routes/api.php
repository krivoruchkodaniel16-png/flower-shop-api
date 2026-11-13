<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    try {
        DB::select('select 1');
        $database = 'ok';
    } catch (\Throwable) {
        $database = 'unavailable';
    }

    return response()->json([
        'app' => config('app.name'),
        'time' => now()->toIso8601String(),
        'database' => $database,
    ], $database === 'ok' ? 200 : 503);
})->name('health');
