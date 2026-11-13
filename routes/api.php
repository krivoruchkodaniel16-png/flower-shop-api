<?php

use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BouquetController;
use App\Http\Controllers\Api\CustomBouquetController;
use App\Http\Controllers\Api\FlowerController;
use App\Http\Controllers\Api\OccasionController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PackagingController;
use App\Http\Controllers\Api\StatisticsController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API цветочного магазина (префикс /api задаётся в bootstrap/app.php)
|--------------------------------------------------------------------------
| Доступ:
|   публичные        — каталог, справочники, расчёт цены букета, регистрация и вход;
|   auth:sanctum     — свои букеты, заказы, ИИ-помощник;
|   role:admin       — управление каталогом, справочниками, пользователями, отчёты;
|   role:admin,florist — учёт остатков цветов.
| Проверка владельца данных — политики OrderPolicy и CustomBouquetPolicy.
*/

// ---------- Служебное ----------

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

// ---------- Публичные маршруты ----------

Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('auth.login');

Route::apiResource('bouquets', BouquetController::class)->only(['index', 'show']);
Route::apiResource('flowers', FlowerController::class)->only(['index', 'show']);
Route::apiResource('packagings', PackagingController::class)->only(['index', 'show']);
Route::apiResource('occasions', OccasionController::class)->only(['index', 'show']);

Route::post('/custom-bouquets/calculate', [CustomBouquetController::class, 'calculate'])
    ->name('custom-bouquets.calculate');

// ---------- Авторизованные пользователи ----------

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/user', [AuthController::class, 'me'])->name('auth.me');

    // Индивидуальные букеты (конструктор) — владелец проверяется политикой
    Route::apiResource('custom-bouquets', CustomBouquetController::class);

    // Заказы
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
    Route::prefix('orders/{order}')->name('orders.')->group(function () {
        Route::post('/cancel', [OrderController::class, 'cancel'])->name('cancel');
        Route::patch('/status', [OrderController::class, 'updateStatus'])->name('status');
        Route::put('/postcard', [OrderController::class, 'updatePostcard'])->name('postcard');
        Route::patch('/courier', [OrderController::class, 'assignCourier'])
            ->middleware('role:admin')
            ->name('courier');
    });

    // ИИ-помощник (ограничение частоты — RateLimiter "ai")
    Route::prefix('ai')->name('ai.')->middleware('throttle:ai')->group(function () {
        Route::post('/postcard-text', [AiController::class, 'postcardText'])->name('postcard-text');
        Route::post('/bouquet-suggestions', [AiController::class, 'bouquetSuggestions'])->name('bouquet-suggestions');
    });

    // Учёт остатков: флорист и администратор
    Route::patch('/flowers/{flower}/stock', [FlowerController::class, 'updateStock'])
        ->middleware('role:admin,florist')
        ->name('flowers.stock');

    // ---------- Администратор ----------
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('bouquets', BouquetController::class)->except(['index', 'show']);
        Route::post('/bouquets/{bouquet}/image', [BouquetController::class, 'uploadImage'])->name('bouquets.image');

        Route::apiResource('flowers', FlowerController::class)->except(['index', 'show']);
        Route::apiResource('packagings', PackagingController::class)->except(['index', 'show']);
        Route::apiResource('occasions', OccasionController::class)->except(['index', 'show']);

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');

        Route::get('/stats', StatisticsController::class)->name('stats');
    });
});
