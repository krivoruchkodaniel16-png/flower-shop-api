<?php

namespace Database\Factories;

use App\Models\CustomBouquet;
use App\Models\Flower;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CustomBouquetItem>
 */
class CustomBouquetItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'custom_bouquet_id' => CustomBouquet::factory(),
            'flower_id' => Flower::factory(),
            'quantity' => fake()->randomElement([1, 3, 5, 7]),
        ];
    }
}
