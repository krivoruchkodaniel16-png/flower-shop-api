<?php

namespace Database\Factories;

use App\Models\Bouquet;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'bouquet_id' => Bouquet::factory(),
            'custom_bouquet_id' => null,
            'quantity' => 1,
            'unit_price' => fake()->numberBetween(15, 90) * 100,
        ];
    }
}
