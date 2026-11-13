<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Ключевой сценарий: оформление заказа со списанием остатков и жизненный цикл заказа.
 */
class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function placeOrder(int $bouquetId, int $quantity = 1, array $extra = [])
    {
        return $this->postJson('/api/orders', array_merge([
            'items' => [['bouquet_id' => $bouquetId, 'quantity' => $quantity]],
            'delivery' => $this->deliveryPayload(),
            'postcard' => ['text' => 'С днём рождения!', 'design' => 'birthday'],
        ], $extra));
    }

    public function test_order_is_created_and_flowers_are_written_off(): void
    {
        [$bouquet, $flower] = $this->makeBouquet(stock: 20, stemsPerBouquet: 5, price: 2500);
        $customer = User::factory()->create();
        Sanctum::actingAs($customer);

        $this->placeOrder($bouquet->id, 2)
            ->assertCreated()
            ->assertJsonPath('data.status', 'new')
            ->assertJsonPath('data.items_total', 5000)
            ->assertJsonPath('data.delivery_price', 0)        // от 5000 ₽ — бесплатно
            ->assertJsonPath('data.postcard_price', 100)
            ->assertJsonPath('data.total_price', 5100)
            ->assertJsonPath('data.postcard.text', 'С днём рождения!')
            ->assertJsonPath('data.can_cancel', true);

        $this->assertDatabaseHas('flowers', ['id' => $flower->id, 'stock_quantity' => 10]);
        $this->assertDatabaseHas('orders', ['user_id' => $customer->id, 'status' => 'new']);
    }

    public function test_cannot_order_more_flowers_than_in_stock_and_nothing_is_saved(): void
    {
        [$bouquet, $flower] = $this->makeBouquet(stock: 8, stemsPerBouquet: 5);
        Sanctum::actingAs(User::factory()->create());

        $this->placeOrder($bouquet->id, 2)
            ->assertStatus(409)
            ->assertJsonPath('details.shortages.0.required', 10)
            ->assertJsonPath('details.shortages.0.available', 8);

        // транзакция откатилась: заказ не создан, остаток не изменился
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('flowers', ['id' => $flower->id, 'stock_quantity' => 8]);
    }

    public function test_delivery_must_be_in_working_hours(): void
    {
        [$bouquet] = $this->makeBouquet();
        Sanctum::actingAs(User::factory()->create());

        $this->placeOrder($bouquet->id, 1, [
            'delivery' => $this->deliveryPayload(['time_from' => '22:00', 'time_to' => '23:00']),
        ])->assertStatus(422)->assertJsonPath('message', 'Доставка выполняется с 09:00 до 21:00.');
    }

    public function test_delivery_validation_errors(): void
    {
        [$bouquet] = $this->makeBouquet();
        Sanctum::actingAs(User::factory()->create());

        $this->placeOrder($bouquet->id, 1, [
            'delivery' => $this->deliveryPayload([
                'delivery_date' => now()->subDay()->toDateString(),
                'time_from' => '14:00',
                'time_to' => '12:00',
                'recipient_phone' => 'abc',
            ]),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['delivery.delivery_date', 'delivery.time_to', 'delivery.recipient_phone']);
    }

    public function test_customer_cancels_new_order_and_flowers_return_to_stock(): void
    {
        [$bouquet, $flower] = $this->makeBouquet(stock: 20, stemsPerBouquet: 5);
        Sanctum::actingAs(User::factory()->create());

        $orderId = $this->placeOrder($bouquet->id, 1)->assertCreated()->json('data.id');
        $this->assertDatabaseHas('flowers', ['id' => $flower->id, 'stock_quantity' => 15]);

        $this->postJson("/api/orders/{$orderId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('flowers', ['id' => $flower->id, 'stock_quantity' => 20]);
    }

    public function test_full_workflow_florist_then_courier(): void
    {
        [$bouquet] = $this->makeBouquet();
        $customer = User::factory()->create();
        $florist = User::factory()->florist()->create();
        $courier = User::factory()->courier()->create();

        Sanctum::actingAs($customer);
        $orderId = $this->placeOrder($bouquet->id)->json('data.id');

        Sanctum::actingAs($florist);
        $this->getJson('/api/orders')->assertOk()->assertJsonPath('data.0.id', $orderId);
        $this->patchJson("/api/orders/{$orderId}/status", ['status' => 'assembling'])->assertOk();
        $this->patchJson("/api/orders/{$orderId}/status", ['status' => 'ready'])->assertOk();

        Sanctum::actingAs($courier);
        $this->patchJson("/api/orders/{$orderId}/status", ['status' => 'delivering'])
            ->assertOk()
            ->assertJsonPath('data.delivery.courier_id', $courier->id);
        $this->patchJson("/api/orders/{$orderId}/status", ['status' => 'delivered', 'comment' => 'Вручено лично'])
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered');

        $this->assertNotNull(Order::find($orderId)->delivery->delivered_at);

        // после начала сборки покупатель отменить заказ уже не может
        Sanctum::actingAs($customer);
        $this->postJson("/api/orders/{$orderId}/cancel")->assertStatus(409);
    }

    public function test_invalid_status_transition_returns_409(): void
    {
        [$bouquet] = $this->makeBouquet();
        Sanctum::actingAs(User::factory()->create());
        $orderId = $this->placeOrder($bouquet->id)->json('data.id');

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->patchJson("/api/orders/{$orderId}/status", ['status' => 'delivered'])
            ->assertStatus(409)
            ->assertJsonPath('message', fn (string $m) => str_contains($m, 'Нельзя перевести заказ'));
    }

    public function test_courier_cannot_assemble_and_customer_cannot_see_foreign_order(): void
    {
        [$bouquet] = $this->makeBouquet();
        Sanctum::actingAs(User::factory()->create());
        $orderId = $this->placeOrder($bouquet->id)->json('data.id');

        Sanctum::actingAs(User::factory()->courier()->create());
        $this->patchJson("/api/orders/{$orderId}/status", ['status' => 'assembling'])->assertForbidden();

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/orders/{$orderId}")->assertForbidden();
        $this->getJson('/api/orders')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_inactive_bouquet_cannot_be_ordered(): void
    {
        [$bouquet] = $this->makeBouquet();
        $bouquet->update(['is_active' => false]);
        Sanctum::actingAs(User::factory()->create());

        $this->placeOrder($bouquet->id)->assertStatus(409);
        $this->assertSame(0, Order::where('status', OrderStatus::New)->count());
    }
}
