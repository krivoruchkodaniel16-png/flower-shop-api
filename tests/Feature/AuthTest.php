<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_confirms_database_connection(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('database', 'ok');
    }

    public function test_user_can_register_and_receives_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Анна',
            'email' => 'anna@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.email', 'anna@example.com')
            ->assertJsonPath('data.user.role', 'customer')
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'role'], 'token']])
            ->assertJsonMissingPath('data.user.password');

        $this->assertDatabaseHas('users', ['email' => 'anna@example.com', 'role' => 'customer']);
        // пароль хранится только в виде хеша
        $this->assertNotSame('secret123', User::first()->password);
    }

    public function test_registration_validation_errors_are_in_russian(): void
    {
        User::factory()->create(['email' => 'busy@example.com']);

        $this->postJson('/api/register', [
            'name' => '',
            'email' => 'busy@example.com',
            'password' => '123',
            'password_confirmation' => '321',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Ошибка валидации')
            ->assertJsonValidationErrors(['name', 'email', 'password'])
            ->assertJsonPath('errors.email.0', 'Пользователь с таким email уже зарегистрирован.');
    }

    public function test_login_with_wrong_password_returns_401_with_generic_message(): void
    {
        User::factory()->create(['email' => 'user@example.com', 'password' => 'right-pass1']);

        $this->postJson('/api/login', ['email' => 'user@example.com', 'password' => 'wrong-pass'])
            ->assertStatus(401)
            ->assertExactJson(['message' => 'Неверный email или пароль']);
    }

    public function test_logout_revokes_current_token(): void
    {
        User::factory()->create(['email' => 'user@example.com', 'password' => 'right-pass1']);

        $token = $this->postJson('/api/login', ['email' => 'user@example.com', 'password' => 'right-pass1'])
            ->assertOk()
            ->json('data.token');

        $this->withToken($token)->getJson('/api/user')->assertOk()->assertJsonPath('data.email', 'user@example.com');
        $this->withToken($token)->postJson('/api/logout')->assertOk();

        // сбрасываем закэшированного в guard пользователя между «запросами» теста
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/user')
            ->assertStatus(401)
            ->assertExactJson(['message' => 'Требуется авторизация']);
    }

    public function test_request_without_token_returns_401_json(): void
    {
        $this->getJson('/api/orders')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Требуется авторизация');
    }
}
