<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Occasion>
 */
class OccasionFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::ucfirst(fake()->randomElement([
            'День рождения', 'Юбилей', '8 Марта', 'Свадьба', 'Годовщина',
            'Выпускной', 'День учителя', 'Без повода', 'Извинение', 'Рождение ребёнка',
        ]).' '.fake()->unique()->numberBetween(1, 99999));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
