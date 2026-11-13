<?php

namespace App\Enums;

/**
 * Тип упаковки букета.
 */
enum PackagingType: string
{
    case Paper = 'paper';
    case Film = 'film';
    case Box = 'box';
    case Basket = 'basket';

    public function label(): string
    {
        return match ($this) {
            self::Paper => 'Крафт-бумага',
            self::Film => 'Плёнка',
            self::Box => 'Шляпная коробка',
            self::Basket => 'Корзина',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
