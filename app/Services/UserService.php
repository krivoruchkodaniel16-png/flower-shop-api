<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->withCount('orders')
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->where('role', $role))
            ->when($filters['search'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$term.'%')
                ->orWhere('email', 'like', '%'.$term.'%')))
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    /**
     * Смена роли. Администратор не может снять роль сам с себя —
     * иначе система может остаться без администратора.
     */
    public function changeRole(User $actor, User $user, UserRole $role): User
    {
        if ($actor->id === $user->id && $role !== UserRole::Admin) {
            throw new BusinessException('Нельзя снять роль администратора с самого себя');
        }

        $user->update(['role' => $role]);

        // при смене роли старые токены отзываются — права применятся при следующем входе
        $user->tokens()->delete();

        Log::info('Изменена роль пользователя', ['user_id' => $user->id, 'role' => $role->value, 'by' => $actor->id]);

        return $user;
    }
}
