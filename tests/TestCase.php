<?php

namespace Tests;

use App\Models\Bouquet;
use App\Models\Flower;
use App\Models\Packaging;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Готовый букет с одним видом цветов.
     */
    protected function makeBouquet(int $stock = 100, int $stemsPerBouquet = 5, float $price = 2500): array
    {
        $flower = Flower::factory()->create(['stock_quantity' => $stock, 'price' => 150]);
        $bouquet = Bouquet::factory()->create(['price' => $price]);
        $bouquet->flowers()->attach($flower->id, ['quantity' => $stemsPerBouquet]);

        return [$bouquet, $flower];
    }

    /**
     * Корректные данные доставки (послезавтра, 12:00–14:00).
     */
    protected function deliveryPayload(array $overrides = []): array
    {
        return array_merge([
            'address' => 'г. Екатеринбург, ул. Ленина, д. 1, кв. 5',
            'recipient_name' => 'Ольга Иванова',
            'recipient_phone' => '+7 912 345-67-89',
            'delivery_date' => now()->addDays(2)->toDateString(),
            'time_from' => '12:00',
            'time_to' => '14:00',
        ], $overrides);
    }

    protected function makePackaging(array $attributes = []): Packaging
    {
        return Packaging::factory()->create(array_merge(['price' => 150, 'max_flowers' => 25, 'is_active' => true], $attributes));
    }
}
