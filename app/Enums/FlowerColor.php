<?php

namespace App\Enums;

/**
 * Цвет цветка (используется в фильтрах каталога и в ИИ-подборе букета).
 */
enum FlowerColor: string
{
    case Red = 'red';
    case White = 'white';
    case Pink = 'pink';
    case Yellow = 'yellow';
    case Orange = 'orange';
    case Purple = 'purple';
    case Blue = 'blue';
    case Green = 'green';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Red => 'Красный',
            self::White => 'Белый',
            self::Pink => 'Розовый',
            self::Yellow => 'Жёлтый',
            self::Orange => 'Оранжевый',
            self::Purple => 'Фиолетовый',
            self::Blue => 'Синий',
            self::Green => 'Зелёный',
            self::Mixed => 'Разноцветный',
        };
    }

    /** Пастельная гамма — для подбора «нежных» букетов. */
    public static function pastel(): array
    {
        return [self::White, self::Pink, self::Purple];
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
