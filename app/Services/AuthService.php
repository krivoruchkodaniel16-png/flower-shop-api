<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    /**
     * Регистрация покупателя. Роль всегда customer — выбрать роль при регистрации нельзя.
     *
     * @return array{0: User, 1: string} пользователь и токен
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'], // хешируется cast'ом 'hashed'
            'role' => UserRole::Customer,
        ]);

        Log::info('Зарегистрирован пользователь', ['user_id' => $user->id]);

        return [$user, $user->createToken('api')->plainTextToken];
    }

    /**
     * Вход по email и паролю. При ошибке — общее сообщение без уточнения,
     * что именно неверно (защита от подбора email).
     *
     * @return array{0: User, 1: string}
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            // email в журнал не пишем — только его хеш
            Log::warning('Неудачная попытка входа', ['email_hash' => hash('sha256', $email)]);

            throw new BusinessException('Неверный email или пароль', 401);
        }

        Log::info('Вход в систему', ['user_id' => $user->id]);

        return [$user, $user->createToken('api')->plainTextToken];
    }

    /** Выход: удаляется только текущий токен (на других устройствах сессии остаются). */
    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
