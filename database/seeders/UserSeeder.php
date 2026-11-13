<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Тестовые учётные записи (пароль у всех: password).
     */
    public function run(): void
    {
        $accounts = [
            ['name' => 'Администратор', 'email' => 'admin@flowers.test', 'role' => UserRole::Admin],
            ['name' => 'Мария Флорист', 'email' => 'florist@flowers.test', 'role' => UserRole::Florist],
            ['name' => 'Иван Курьер', 'email' => 'courier@flowers.test', 'role' => UserRole::Courier],
            ['name' => 'Пётр Курьеров', 'email' => 'courier2@flowers.test', 'role' => UserRole::Courier],
            ['name' => 'Анна Покупатель', 'email' => 'customer@flowers.test', 'role' => UserRole::Customer],
        ];

        foreach ($accounts as $account) {
            User::factory()->create($account + ['password' => 'password']);
        }

        // Дополнительные покупатели
        User::factory()->count(6)->create();
    }
}
