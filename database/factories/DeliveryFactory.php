<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Delivery>
 */
class DeliveryFactory extends Factory
{
    public function definition(): array
    {
        $from = fake()->numberBetween(9, 18);

        return [
            'order_id' => Order::factory(),
            'courier_id' => null,
            'address' => 'г. Екатеринбург, ул. '.fake()->randomElement([
                'Ленина', 'Малышева', 'Куйбышева', '8 Марта', 'Белинского', 'Мира', 'Радищева',
            ]).', д. '.fake()->numberBetween(1, 120).', кв. '.fake()->numberBetween(1, 200),
            'recipient_name' => fake()->name(),
            'recipient_phone' => fake()->numerify('+7 9## ###-##-##'),
            'delivery_date' => now()->addDays(fake()->numberBetween(1, 7))->toDateString(),
            'time_from' => sprintf('%02d:00', $from),
            'time_to' => sprintf('%02d:00', $from + 2),
            'delivered_at' => null,
            'courier_comment' => null,
        ];
    }
}
