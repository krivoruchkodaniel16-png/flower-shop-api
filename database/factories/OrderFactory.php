<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $items = fake()->numberBetween(20, 90) * 100;
        $delivery = $items >= config('shop.free_delivery_from') ? 0 : config('shop.delivery_price');

        return [
            'user_id' => User::factory(),
            'status' => OrderStatus::New,
            'items_total' => $items,
            'delivery_price' => $delivery,
            'postcard_price' => 0,
            'total_price' => $items + $delivery,
            'comment' => fake()->optional()->sentence(),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'cancelled_at' => $status === OrderStatus::Cancelled ? now() : null,
        ]);
    }
}
