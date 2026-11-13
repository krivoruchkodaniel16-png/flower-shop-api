<?php

namespace App\Policies;

use App\Models\CustomBouquet;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Индивидуальные букеты: владелец — полный доступ, администратор — только просмотр.
 */
class CustomBouquetPolicy
{
    public function view(User $user, CustomBouquet $customBouquet): Response
    {
        return $user->isAdmin() || $customBouquet->user_id === $user->id
            ? Response::allow()
            : Response::deny('Это не ваш букет');
    }

    public function update(User $user, CustomBouquet $customBouquet): Response
    {
        return $customBouquet->user_id === $user->id
            ? Response::allow()
            : Response::deny('Изменять можно только свои букеты');
    }

    public function delete(User $user, CustomBouquet $customBouquet): Response
    {
        return $customBouquet->user_id === $user->id
            ? Response::allow()
            : Response::deny('Удалять можно только свои букеты');
    }
}
