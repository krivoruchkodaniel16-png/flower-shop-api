<?php

use App\Exceptions\BusinessException;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\LogApiRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Псевдоним для проверки ролей: ->middleware('role:admin')
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Журналирование всех запросов к API
        $middleware->api(append: [
            LogApiRequest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
        |------------------------------------------------------------------
        | Единый формат ошибок API
        |------------------------------------------------------------------
        | {"message": "..."}                          — все ошибки
        | {"message": "...", "errors": {поле: [...]}} — ошибки валидации (422)
        | Стек вызовов и SQL клиенту не выводятся.
        */

        $isApi = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $isApi($request));

        // Бизнес-правила не являются ошибками сервера — в laravel.log не пишем стек
        $exceptions->dontReport([BusinessException::class]);

        // 409 (или другой код) — нарушение бизнес-правила
        $exceptions->render(function (BusinessException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return response()->json(
                array_filter(['message' => $e->getMessage(), 'details' => $e->getDetails() ?: null]),
                $e->getStatus()
            );
        });

        // 422 — ошибка валидации
        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $e->errors(),
            ], 422);
        });

        // 401 — нет токена / токен неверный или просрочен
        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return response()->json(['message' => 'Требуется авторизация'], 401);
        });

        // 403 — недостаточно прав (политики, Gate::authorize)
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            $message = $e->getMessage();
            if ($message === '' || $message === 'This action is unauthorized.') {
                $message = 'Недостаточно прав для выполнения действия';
            }

            return response()->json(['message' => $message], 403);
        });

        // 404 — запись не найдена (Route Model Binding) или неизвестный маршрут
        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            $previous = $e->getPrevious();
            if ($previous instanceof ModelNotFoundException) {
                $names = [
                    App\Models\Bouquet::class => 'Букет не найден',
                    App\Models\Flower::class => 'Цветок не найден',
                    App\Models\Packaging::class => 'Упаковка не найдена',
                    App\Models\Occasion::class => 'Повод не найден',
                    App\Models\CustomBouquet::class => 'Индивидуальный букет не найден',
                    App\Models\Order::class => 'Заказ не найден',
                    App\Models\User::class => 'Пользователь не найден',
                ];

                return response()->json(['message' => $names[$previous->getModel()] ?? 'Запись не найдена'], 404);
            }

            return response()->json(['message' => 'Ресурс не найден'], 404);
        });

        // 405 — неверный HTTP-метод
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return response()->json(['message' => 'Метод не поддерживается для этого адреса'], 405);
        });

        // Ошибки БД: нарушение внешнего ключа / уникальности — 409, остальное — 500 без SQL
        $exceptions->render(function (QueryException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            $sqlState = (string) $e->getCode();
            if (in_array($sqlState, ['23000', '23503', '23505', '19'], true)) {
                return response()->json([
                    'message' => 'Операция нарушает целостность данных: запись используется в других записях или уже существует',
                ], 409);
            }

            Log::error('Ошибка БД', ['exception' => $e->getMessage()]);

            return response()->json(['message' => 'Внутренняя ошибка сервера'], 500);
        });

        // Прочие ошибки
        $exceptions->render(function (Throwable $e, Request $request) use ($isApi) {
            if (! $isApi($request) || $e instanceof HttpResponseException) {
                return null;
            }

            if ($e instanceof HttpExceptionInterface) {
                $message = match ($e->getStatusCode()) {
                    429 => 'Слишком много запросов. Повторите позже.',
                    503 => 'Сервис временно недоступен',
                    default => $e->getMessage() ?: 'Ошибка запроса',
                };

                return response()->json(['message' => $message], $e->getStatusCode(), $e->getHeaders());
            }

            // Непредвиденная ошибка: подробности — только при APP_DEBUG=true
            return response()->json(array_filter([
                'message' => 'Внутренняя ошибка сервера',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ]), 500);
        });
    })->create();
