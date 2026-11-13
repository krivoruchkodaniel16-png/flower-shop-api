<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Пользователь: покупатель, флорист, курьер или администратор.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property UserRole $role
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
    ];

    /** Пароль и токен никогда не попадают в JSON. */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    // ---------- Связи ----------

    /** Заказы покупателя: User hasMany Order */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** Индивидуальные букеты: User hasMany CustomBouquet */
    public function customBouquets(): HasMany
    {
        return $this->hasMany(CustomBouquet::class);
    }

    /** Доставки, назначенные курьеру: User hasMany Delivery (courier_id) */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'courier_id');
    }

    // ---------- Роли ----------

    public function hasRole(UserRole|string ...$roles): bool
    {
        foreach ($roles as $role) {
            $value = $role instanceof UserRole ? $role->value : $role;
            if ($this->role?->value === $value) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }
}
