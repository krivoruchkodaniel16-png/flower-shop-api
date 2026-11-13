<?php

namespace Database\Factories;

use App\Enums\PostcardDesign;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Postcard>
 */
class PostcardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'design' => fake()->randomElement(PostcardDesign::cases()),
            'text' => fake()->randomElement([
                'С днём рождения! Пусть каждый день будет таким же ярким, как эти цветы.',
                'Спасибо, что ты есть! С любовью.',
                'Поздравляю с праздником! Счастья, тепла и весеннего настроения.',
                'Просто так — чтобы ты улыбнулась.',
            ]),
            'signature' => fake()->firstName(),
        ];
    }
}
