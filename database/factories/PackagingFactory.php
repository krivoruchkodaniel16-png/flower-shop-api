<?php

namespace Database\Factories;

use App\Enums\PackagingType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Packaging>
 */
class PackagingFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(PackagingType::cases());

        return [
            'name' => $type->label().' '.fake()->colorName(),
            'type' => $type,
            'color' => fake()->safeColorName(),
            'price' => fake()->numberBetween(1, 6) * 100,
            'max_flowers' => fake()->randomElement([25, 51, 101]),
            'is_active' => true,
        ];
    }
}
