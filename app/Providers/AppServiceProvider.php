<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Ответы API Resource оборачиваются в "data" (стандарт Laravel)
        JsonResource::wrap('data');

        // Защита входа от перебора паролей: 5 попыток в минуту на email+IP
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip())
                ->response(fn () => response()->json(['message' => 'Слишком много попыток входа. Повторите через минуту.'], 429));
        });

        // Ограничение частоты обращений к ИИ: 10 запросов в минуту на пользователя
        RateLimiter::for('ai', function (Request $request) {
            return Limit::perMinute(10)
                ->by((string) ($request->user()?->id ?? $request->ip()))
                ->response(fn () => response()->json(['message' => 'Слишком много запросов к ИИ-помощнику. Подождите минуту.'], 429));
        });
    }
}
