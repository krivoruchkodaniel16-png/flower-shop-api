<?php

namespace Database\Factories;

use App\Enums\FlowerColor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Flower>
 */
class FlowerFactory extends Factory
{
    public function definition(): array
    {
        $color = fake()->randomElement(FlowerColor::cases());
        $kind = fake()->randomElement([
            'Роза', 'Тюльпан', 'Пион', 'Хризантема', 'Гербера',
            'Лилия', 'Альстромерия', 'Эустома', 'Гвоздика', 'Ирис',
        ]);

        return [
            'name' => $kind.' '.mb_strtolower($color->label()),
            'color' => $color,
            'price' => fake()->numberBetween(6, 45) * 10,
            'stock_quantity' => fake()->numberBetween(30, 300),
            'description' => fake()->realText(120),
            'is_active' => true,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock_quantity' => 0]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
