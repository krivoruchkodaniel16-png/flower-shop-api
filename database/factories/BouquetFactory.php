<?php

namespace Database\Factories;

use App\Models\Bouquet;
use App\Models\Flower;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bouquet>
 */
class BouquetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Букет «'.fake()->randomElement([
                'Нежность', 'Утро', 'Весна', 'Комплимент', 'Восторг', 'Акварель',
                'Шёпот', 'Праздник', 'Облако', 'Солнце', 'Вдохновение', 'Мелодия',
            ]).' '.fake()->numberBetween(1, 999).'»',
            'description' => fake()->realText(200),
            'price' => fake()->numberBetween(15, 120) * 100,
            'image_path' => null,
            'is_active' => true,
        ];
    }

    /** Букет с составом из N случайных цветов (по 3–11 шт.). */
    public function withFlowers(int $count = 2): static
    {
        return $this->afterCreating(function (Bouquet $bouquet) use ($count) {
            $flowers = Flower::factory()->count($count)->create();
            foreach ($flowers as $flower) {
                $bouquet->flowers()->attach($flower->id, ['quantity' => fake()->randomElement([3, 5, 7, 9, 11])]);
            }
        });
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
