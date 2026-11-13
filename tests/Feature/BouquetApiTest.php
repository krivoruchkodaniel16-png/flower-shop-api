<?php

namespace Tests\Feature;

use App\Models\Bouquet;
use App\Models\Flower;
use App\Models\Occasion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BouquetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_filters_by_occasion_and_price_with_pagination(): void
    {
        $birthday = Occasion::factory()->create();
        $cheap = Bouquet::factory()->create(['price' => 1500]);
        $expensive = Bouquet::factory()->create(['price' => 9000]);
        Bouquet::factory()->count(3)->create(['price' => 2000]); // без повода
        $cheap->occasions()->attach($birthday);
        $expensive->occasions()->attach($birthday);

        $this->getJson("/api/bouquets?occasion_id={$birthday->id}&price_max=5000")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $cheap->id)
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/bouquets?per_page=2&sort=price&order=desc')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $expensive->id)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_catalog_search_and_flower_filter(): void
    {
        $rose = Flower::factory()->create(['name' => 'Роза тестовая']);
        $withRose = Bouquet::factory()->create(['name' => 'Букет «Розовый сад»', 'description' => 'Описание']);
        $withRose->flowers()->attach($rose->id, ['quantity' => 5]);
        Bouquet::factory()->create(['name' => 'Тюльпаны', 'description' => 'Описание']);

        $this->getJson('/api/bouquets?search=сад')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/bouquets?flower_ids[]={$rose->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.flowers.0.quantity', 5);
    }

    public function test_sort_field_is_checked_against_whitelist(): void
    {
        $this->getJson('/api/bouquets?sort=password')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort']);
    }

    public function test_missing_bouquet_returns_404_with_message(): void
    {
        $this->getJson('/api/bouquets/999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Букет не найден']);
    }

    public function test_customer_cannot_create_bouquet(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/bouquets', ['name' => 'X', 'price' => 100])
            ->assertForbidden()
            ->assertJsonPath('message', 'Недостаточно прав для выполнения действия');
    }

    public function test_admin_creates_bouquet_with_composition_and_occasions(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $flower = Flower::factory()->create();
        $occasion = Occasion::factory()->create();

        $this->postJson('/api/bouquets', [
            'name' => 'Новый букет',
            'price' => 3500,
            'flowers' => [['flower_id' => $flower->id, 'quantity' => 7]],
            'occasion_ids' => [$occasion->id],
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Новый букет')
            ->assertJsonPath('data.stems_count', 7)
            ->assertJsonPath('data.occasions.0.id', $occasion->id);

        $this->assertDatabaseHas('bouquet_flower', ['flower_id' => $flower->id, 'quantity' => 7]);
    }

    public function test_bouquet_price_must_be_positive(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/bouquets', ['name' => 'X', 'price' => 0, 'flowers' => []])
            ->assertStatus(422)
            ->assertJsonPath('errors.price.0', 'Цена должна быть больше нуля.');
    }

    public function test_admin_uploads_bouquet_image(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->admin()->create());
        $bouquet = Bouquet::factory()->create();

        $this->post("/api/bouquets/{$bouquet->id}/image", [
            'image' => UploadedFile::fake()->image('bouquet.jpg', 600, 600),
        ], ['Accept' => 'application/json'])->assertOk();

        $bouquet->refresh();
        $this->assertNotNull($bouquet->image_path);
        Storage::disk('public')->assertExists($bouquet->image_path);
    }

    public function test_flower_used_in_bouquet_cannot_be_deleted(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        [, $flower] = $this->makeBouquet();

        $this->deleteJson("/api/flowers/{$flower->id}")
            ->assertStatus(409)
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('flowers', ['id' => $flower->id]);
    }
}
