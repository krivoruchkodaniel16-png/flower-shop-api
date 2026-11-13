<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Аутентификация
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    /**
     * Регистрация покупателя.
     *
     * POST /api/register → 201 {data: {user, token, token_type}} | 422
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        [$user, $token] = $this->auth->register($request->validated());

        return $this->tokenResponse($user, $token, 201);
    }

    /**
     * Вход.
     *
     * POST /api/login → 200 {data: {user, token, token_type}} | 401 «Неверный email или пароль» | 422 | 429
     */
    public function login(LoginRequest $request): JsonResponse
    {
        [$user, $token] = $this->auth->login($request->validated('email'), $request->validated('password'));

        return $this->tokenResponse($user, $token);
    }

    /**
     * Выход — удаление текущего токена.
     *
     * POST /api/logout → 200 | 401
     */
    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return response()->json(['message' => 'Вы вышли из системы']);
    }

    /**
     * Текущий пользователь.
     *
     * GET /api/user → 200 {data: user} | 401
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    private function tokenResponse($user, string $token, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], $status);
    }
}
