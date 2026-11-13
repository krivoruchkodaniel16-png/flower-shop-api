<?php

namespace App\Enums;

/**
 * Статусы заказа и допустимые переходы между ними.
 *
 *   new ──► assembling ──► ready ──► delivering ──► delivered
 *    │           │           │
 *    └───────────┴───────────┴──► cancelled
 */
enum OrderStatus: string
{
    case New = 'new';
    case Assembling = 'assembling';
    case Ready = 'ready';
    case Delivering = 'delivering';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новый',
            self::Assembling => 'Собирается',
            self::Ready => 'Готов к доставке',
            self::Delivering => 'Доставляется',
            self::Delivered => 'Доставлен',
            self::Cancelled => 'Отменён',
        };
    }

    /**
     * Статусы, в которые можно перейти из текущего.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Assembling, self::Cancelled],
            self::Assembling => [self::Ready, self::Cancelled],
            self::Ready => [self::Delivering, self::Cancelled],
            self::Delivering => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /**
     * Роли, которым разрешено переводить заказ В этот статус.
     *
     * @return list<UserRole>
     */
    public function rolesAllowedToSet(): array
    {
        return match ($this) {
            self::Assembling, self::Ready => [UserRole::Florist, UserRole::Admin],
            self::Delivering, self::Delivered => [UserRole::Courier, UserRole::Admin],
            self::Cancelled => [UserRole::Admin],
            self::New => [],
        };
    }

    /** Заказ ещё не завершён (цветы зарезервированы). */
    public function isActive(): bool
    {
        return ! in_array($this, [self::Delivered, self::Cancelled], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
