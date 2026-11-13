<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\BouquetSuggestionRequest;
use App\Http\Requests\Ai\PostcardTextRequest;
use App\Http\Resources\BouquetResource;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;

/**
 * @group ИИ-помощник
 */
class AiController extends Controller
{
    public function __construct(private readonly AiService $ai)
    {
    }

    /**
     * Варианты текста открытки. Доступ: авторизованные (не более 10 запросов в минуту).
     *
     * POST /api/ai/postcard-text {occasion, recipient, tone?: warm|formal|funny|romantic}
     * → 200 {data: {variants: [...], source: ai|template, cached: bool}}
     */
    public function postcardText(PostcardTextRequest $request): JsonResponse
    {
        $result = $this->ai->postcardText(
            $request->validated('occasion'),
            $request->validated('recipient'),
            $request->validated('tone'),
            $request->user()?->id,
        );

        return response()->json(['data' => $result]);
    }

    /**
     * Подбор букета по запросу на естественном языке. Доступ: авторизованные.
     *
     * POST /api/ai/bouquet-suggestions {query: "на юбилей маме, любит пастельные тона, до 3000 рублей"}
     * → 200 {data: {criteria, explanation, source, bouquets: [...]}}
     */
    public function bouquetSuggestions(BouquetSuggestionRequest $request): JsonResponse
    {
        $result = $this->ai->suggestBouquets($request->validated('query'), $request->user()?->id);

        return response()->json([
            'data' => [
                'criteria' => $result['criteria'],
                'explanation' => $result['explanation'],
                'source' => $result['source'],
                'bouquets' => BouquetResource::collection($result['bouquets'])->resolve($request),
            ],
        ]);
    }
}
