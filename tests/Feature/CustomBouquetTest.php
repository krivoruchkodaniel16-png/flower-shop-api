<?php

namespace Tests\Feature;

use App\Models\CustomBouquet;
use App\Models\Flower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomBouquetTest extends TestCase
{
    use RefreshDatabase;

    public function test_constructor_calculates_price(): void
    {
        config(['shop.assembly_fee' => 300]);
        $packaging = $this->makePackaging(['price' => 150]);
        $rose = Flower::factory()->create(['price' => 100, 'stock_quantity' => 50]);
        $tulip = Flower::factory()->create(['price' => 200, 'stock_quantity' => 50]);

        // 3 × 100 + 2 × 200 = 700; + упаковка 150 + сборка 300 = 1150
        $this->postJson('/api/custom-bouquets/calculate', [
            'packaging_id' => $packaging->id,
            'items' => [
                ['flower_id' => $rose->id, 'quantity' => 3],
                ['flower_id' => $tulip->id, 'quantity' => 2],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.flowers_total', 700)
            ->assertJsonPath('data.total', 1150)
            ->assertJsonPath('data.flowers_count', 5)
            ->assertJsonPath('data.is_valid', true);
    }

    public function test_even_number_of_flowers_is_rejected_on_save(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $packaging = $this->makePackaging();
        $rose = Flower::factory()->create(['stock_quantity' => 50]);

        $this->postJson('/api/custom-bouquets', [
            'name' => 'Чётный',
            'packaging_id' => $packaging->id,
            'items' => [['flower_id' => $rose->id, 'quantity' => 4]],
        ])
            ->assertStatus(409)
            ->assertJsonPath('message', fn (string $m) => str_contains($m, 'нечётное'));

        $this->assertDatabaseCount('custom_bouquets', 0);
    }

    public function test_packaging_capacity_is_checked(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $packaging = $this->makePackaging(['max_flowers' => 5]);
        $rose = Flower::factory()->create(['stock_quantity' => 50]);

        $this->postJson('/api/custom-bouquets', [
            'name' => 'Слишком большой',
            'packaging_id' => $packaging->id,
            'items' => [['flower_id' => $rose->id, 'quantity' => 7]],
        ])->assertStatus(409);
    }

    public function test_user_saves_custom_bouquet_and_others_cannot_see_it(): void
    {
        $owner = User::factory()->create();
        $packaging = $this->makePackaging();
        $rose = Flower::factory()->create(['stock_quantity' => 50]);

        Sanctum::actingAs($owner);
        $id = $this->postJson('/api/custom-bouquets', [
            'name' => 'Для мамы',
            'packaging_id' => $packaging->id,
            'items' => [['flower_id' => $rose->id, 'quantity' => 5]],
        ])
            ->assertCreated()
            ->assertJsonPath('data.flowers_count', 5)
            ->json('data.id');

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/custom-bouquets/{$id}")->assertForbidden();
        $this->deleteJson("/api/custom-bouquets/{$id}")->assertForbidden();

        $this->assertNotNull(CustomBouquet::find($id));
    }
}
