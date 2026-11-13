<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateUserRoleRequest;
use App\Http\Requests\User\UserIndexRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Пользователи (администратор)
 */
class UserController extends Controller
{
    public function __construct(private readonly UserService $users)
    {
    }

    /** GET /api/users?role=&search=&per_page= Доступ: admin. */
    public function index(UserIndexRequest $request): AnonymousResourceCollection
    {
        return UserResource::collection($this->users->paginate($request->validated()));
    }

    /** PATCH /api/users/{user}/role {role} → 200 | 409 (снять роль с себя). Доступ: admin. */
    public function updateRole(UpdateUserRoleRequest $request, User $user): UserResource
    {
        return new UserResource(
            $this->users->changeRole($request->user(), $user, UserRole::from($request->validated('role')))
        );
    }
}
