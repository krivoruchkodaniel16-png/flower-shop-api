<?php

namespace Database\Seeders;

use App\Enums\PackagingType;
use App\Models\Packaging;
use Illuminate\Database\Seeder;

class PackagingSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['Крафт-бумага', PackagingType::Paper, 'бежевый', 900, 51],
            ['Корейская бумага', PackagingType::Paper, 'пудровый', 1500, 51],
            ['Матовая плёнка', PackagingType::Film, 'прозрачный', 700, 101],
            ['Шляпная коробка малая', PackagingType::Box, 'белый', 3500, 25],
            ['Шляпная коробка большая', PackagingType::Box, 'чёрный', 5500, 51],
            ['Плетёная корзина', PackagingType::Basket, 'натуральный', 7000, 101],
        ];

        foreach ($items as [$name, $type, $color, $price, $max]) {
            Packaging::create([
                'name' => $name,
                'type' => $type,
                'color' => $color,
                'price' => $price,
                'max_flowers' => $max,
                'is_active' => true,
            ]);
        }
    }
}
