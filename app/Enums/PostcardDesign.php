<?php

namespace App\Enums;

/**
 * Оформление открытки.
 */
enum PostcardDesign: string
{
    case Classic = 'classic';
    case Romantic = 'romantic';
    case Birthday = 'birthday';
    case Minimal = 'minimal';

    public function label(): string
    {
        return match ($this) {
            self::Classic => 'Классическая',
            self::Romantic => 'Романтическая',
            self::Birthday => 'Праздничная',
            self::Minimal => 'Минималистичная',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
