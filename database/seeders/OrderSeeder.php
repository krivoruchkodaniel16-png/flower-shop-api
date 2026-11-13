<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PostcardDesign;
use App\Enums\UserRole;
use App\Models\Bouquet;
use App\Models\CustomBouquet;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Демонстрационные заказы во всех статусах (для очереди флориста,
 * экрана курьера и статистики). Создаются напрямую, без списания остатков.
 */
class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('role', UserRole::Customer)->get();
        $mainCustomer = $customers->firstWhere('email', 'customer@flowers.test');
        $courier = User::where('email', 'courier@flowers.test')->first();
        $bouquets = Bouquet::all();

        // [статус, сдвиг даты доставки в днях]
        $plan = [
            [OrderStatus::Delivered, -20], [OrderStatus::Delivered, -14], [OrderStatus::Delivered, -9],
            [OrderStatus::Delivered, -5], [OrderStatus::Delivered, -2], [OrderStatus::Cancelled, -3],
            [OrderStatus::Delivering, 0], [OrderStatus::Ready, 1], [OrderStatus::Ready, 1],
            [OrderStatus::Assembling, 1], [OrderStatus::Assembling, 2], [OrderStatus::New, 2],
            [OrderStatus::New, 3], [OrderStatus::New, 4], [OrderStatus::New, 5],
        ];

        foreach ($plan as $i => [$status, $dayShift]) {
            $customer = $i % 3 === 0 ? $mainCustomer : $customers->random();
            $deliveryDate = Carbon::today()->addDays($dayShift);
            $createdAt = $deliveryDate->copy()->subDays(2)->setTime(12, 0);

            // Позиции: 1–2 готовых букета, иногда индивидуальный букет покупателя
            $lines = [];
            foreach ($bouquets->random(fake()->numberBetween(1, 2)) as $bouquet) {
                $lines[] = ['bouquet_id' => $bouquet->id, 'custom_bouquet_id' => null, 'quantity' => 1, 'unit_price' => (float) $bouquet->price];
            }
            $custom = CustomBouquet::where('user_id', $customer->id)->first();
            if ($custom && $i % 4 === 1) {
                $lines[] = ['bouquet_id' => null, 'custom_bouquet_id' => $custom->id, 'quantity' => 1, 'unit_price' => $custom->price()];
            }

            $itemsTotal = array_sum(array_map(fn ($l) => $l['unit_price'] * $l['quantity'], $lines));
            $deliveryPrice = $itemsTotal >= config('shop.free_delivery_from') ? 0 : config('shop.delivery_price');
            $hasPostcard = $i % 2 === 0;
            $postcardPrice = $hasPostcard ? config('shop.postcard_price') : 0;

            $order = Order::create([
                'user_id' => $customer->id,
                'status' => $status,
                'items_total' => $itemsTotal,
                'delivery_price' => $deliveryPrice,
                'postcard_price' => $postcardPrice,
                'total_price' => $itemsTotal + $deliveryPrice + $postcardPrice,
                'comment' => $i % 5 === 0 ? 'Позвонить получателю за 30 минут' : null,
                'cancelled_at' => $status === OrderStatus::Cancelled ? $createdAt->copy()->addHour() : null,
            ]);
            $order->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

            $order->items()->createMany($lines);

            $from = 10 + ($i % 8);
            $order->delivery()->create([
                'courier_id' => in_array($status, [OrderStatus::Delivering, OrderStatus::Delivered], true) ? $courier->id : null,
                'address' => 'г. Екатеринбург, ул. '.fake()->randomElement(['Ленина', 'Малышева', '8 Марта', 'Мира', 'Радищева']).', д. '.fake()->numberBetween(1, 120),
                'recipient_name' => fake()->name(),
                'recipient_phone' => fake()->numerify('+7 9## ###-##-##'),
                'delivery_date' => $deliveryDate->toDateString(),
                'time_from' => sprintf('%02d:00', $from),
                'time_to' => sprintf('%02d:00', $from + 2),
                'delivered_at' => $status === OrderStatus::Delivered ? $deliveryDate->copy()->setTime($from + 1, 15) : null,
            ]);

            if ($hasPostcard) {
                $order->postcard()->create([
                    'design' => fake()->randomElement(PostcardDesign::cases()),
                    'text' => fake()->randomElement([
                        'С днём рождения! Пусть сбудется всё задуманное.',
                        'Люблю тебя! Спасибо за каждый день вместе.',
                        'Мамочка, ты самая лучшая! Обнимаю.',
                        'Спасибо за помощь и поддержку!',
                    ]),
                    'signature' => fake()->firstName(),
                ]);
            }
        }
    }
}
