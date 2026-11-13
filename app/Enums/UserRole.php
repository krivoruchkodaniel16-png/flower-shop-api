<?php

namespace App\Enums;

/**
 * Роли пользователей системы.
 */
enum UserRole: string
{
    case Customer = 'customer';
    case Florist = 'florist';
    case Courier = 'courier';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Покупатель',
            self::Florist => 'Флорист',
            self::Courier => 'Курьер',
            self::Admin => 'Администратор',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
