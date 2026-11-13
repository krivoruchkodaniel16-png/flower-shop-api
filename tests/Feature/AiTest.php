<?php

namespace Tests\Feature;

use App\Models\Bouquet;
use App\Models\Flower;
use App\Models\Occasion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Внешний ИИ-сервис в тестах подменяется заглушкой Http::fake().
 */
class AiTest extends TestCase
{
    use RefreshDatabase;

    public function test_postcard_text_from_ai_service(): void
    {
        config(['services.ai.enabled' => true, 'services.ai.base_url' => 'http://ai.test/v1']);
        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => '{"variants": ["Мама, с юбилеем! Ты лучшая.", "С юбилеем, родная!"]}']]],
                'usage' => ['prompt_tokens' => 80, 'completion_tokens' => 40],
            ]),
        ]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/ai/postcard-text', ['occasion' => 'Юбилей', 'recipient' => 'маме, 60 лет'])
            ->assertOk()
            ->assertJsonPath('data.source', 'ai')
            ->assertJsonCount(2, 'data.variants');

        Http::assertSentCount(1);
    }

    public function test_postcard_falls_back_to_templates_when_ai_fails(): void
    {
        config(['services.ai.enabled' => true, 'services.ai.base_url' => 'http://ai.test/v1']);
        Http::fake(['ai.test/*' => Http::response('Service Unavailable', 503)]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/ai/postcard-text', ['occasion' => 'День рождения', 'recipient' => 'коллеге'])
            ->assertOk()
            ->assertJsonPath('data.source', 'template')
            ->assertJsonCount(3, 'data.variants');
    }

    public function test_bouquet_suggestion_without_ai_uses_rules_and_respects_budget(): void
    {
        $mom = Occasion::factory()->create(['slug' => 'mom', 'name' => 'Для мамы']);
        $anniversary = Occasion::factory()->create(['slug' => 'anniversary', 'name' => 'Юбилей']);
        $pink = Flower::factory()->create(['color' => 'pink']);
        $red = Flower::factory()->create(['color' => 'red']);

        $pastel = Bouquet::factory()->create(['price' => 2900]);
        $pastel->flowers()->attach($pink->id, ['quantity' => 7]);
        $pastel->occasions()->attach([$mom->id, $anniversary->id]);

        $bright = Bouquet::factory()->create(['price' => 2500]);
        $bright->flowers()->attach($red->id, ['quantity' => 7]);

        $tooExpensive = Bouquet::factory()->create(['price' => 8000]);
        $tooExpensive->flowers()->attach($pink->id, ['quantity' => 25]);
        $tooExpensive->occasions()->attach($mom->id);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/ai/bouquet-suggestions', [
            'query' => 'на юбилей маме, любит пастельные тона, до 3000 рублей',
        ])->assertOk();

        $response->assertJsonPath('data.source', 'rules')
            ->assertJsonPath('data.criteria.max_price', 3000)
            ->assertJsonPath('data.bouquets.0.id', $pastel->id);

        $ids = collect($response->json('data.bouquets'))->pluck('id');
        $this->assertFalse($ids->contains($tooExpensive->id));
    }
}
