<?php

namespace Database\Factories;

use App\Models\Packaging;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CustomBouquet>
 */
class CustomBouquetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'packaging_id' => Packaging::factory(),
            'name' => 'Мой букет '.fake()->numberBetween(1, 999),
            'comment' => fake()->optional()->sentence(),
        ];
    }
}
