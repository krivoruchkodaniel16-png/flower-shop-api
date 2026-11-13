<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Журнал запросов к API: метод, путь, id пользователя, код ответа, время.
 * Тело запроса, заголовки и строка запроса НЕ записываются —
 * так пароли и токены не попадают в журнал (storage/logs/api-*.log).
 */
class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $started = microtime(true);

        $response = $next($request);

        $status = $response->getStatusCode();
        $context = [
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'user_id' => $request->user('sanctum')?->id,
            'status' => $status,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'ip' => $request->ip(),
        ];

        $level = match (true) {
            $status >= 500 => 'error',
            $status >= 400 => 'warning',
            default => 'info',
        };

        Log::channel('api')->log($level, 'API request', $context);

        return $response;
    }
}
