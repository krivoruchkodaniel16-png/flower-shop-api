<?php

namespace App\Services;

use App\Enums\FlowerColor;
use App\Models\Bouquet;
use App\Models\Occasion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * ИИ-функции магазина:
 *  1) генерация текста открытки по поводу и описанию получателя;
 *  2) подбор букета по запросу на естественном языке
 *     («на юбилей маме, любит пастельные тона, до 20 000 тенге»).
 *
 * Обращение к модели — только с сервера (ключ в .env). Ответ запрашивается в JSON
 * и проверяется валидатором. Если ИИ выключен, недоступен или ответил некорректно,
 * используется встроенный алгоритм — приложение продолжает работать.
 * Персональные данные покупателя (имя, email, телефон) в ИИ не передаются.
 */
class AiService
{
    private const TONES = [
        'warm' => 'тёплый, душевный',
        'formal' => 'официальный, сдержанный',
        'funny' => 'весёлый, с лёгким юмором',
        'romantic' => 'романтичный',
    ];

    // =====================================================================
    // 1. Текст открытки
    // =====================================================================

    /** @return array{variants: list<string>, source: string, cached: bool} */
    public function postcardText(string $occasion, string $recipient, ?string $tone, ?int $userId): array
    {
        $cacheKey = 'ai:postcard:'.md5(mb_strtolower($occasion.'|'.$recipient.'|'.$tone));

        if ($cached = Cache::get($cacheKey)) {
            return $cached + ['cached' => true];
        }

        $result = null;

        if ($this->enabled()) {
            $json = $this->chatJson(
                'postcard',
                $userId,
                'Ты — копирайтер цветочного магазина. Пиши короткие тексты для открыток к букетам на русском языке. '
                .'Отвечай строго в формате JSON: {"variants": ["текст 1", "текст 2", "текст 3"]}. '
                .'Каждый вариант — 1–3 предложения, не длиннее 300 символов, без смайликов и без подписи.',
                "Повод: {$occasion}\nПолучатель: {$recipient}\nТон: ".(self::TONES[$tone] ?? self::TONES['warm'])
            );

            $validator = Validator::make($json ?? [], [
                'variants' => ['required', 'array', 'min:1', 'max:5'],
                'variants.*' => ['required', 'string', 'min:5', 'max:500'],
            ]);

            if ($json !== null && $validator->passes()) {
                $result = ['variants' => array_values(array_map('trim', $json['variants'])), 'source' => 'ai'];
            } elseif ($json !== null) {
                Log::channel('ai')->warning('Некорректный ответ ИИ (postcard)', ['errors' => $validator->errors()->all()]);
            }
        }

        $result ??= ['variants' => $this->templatePostcards($occasion, $tone), 'source' => 'template'];

        Cache::put($cacheKey, $result, now()->addDay());

        return $result + ['cached' => false];
    }

    /** @return list<string> */
    private function templatePostcards(string $occasion, ?string $tone): array
    {
        $o = mb_strtolower($occasion);

        $templates = match (true) {
            str_contains($o, 'юбил') => [
                'С юбилеем! Пусть эти цветы напомнят, как много тепла вы подарили миру — и сколько ещё впереди.',
                'Поздравляем с юбилеем! Здоровья, радости и множества счастливых дней.',
                'Пусть каждый год приносит новые поводы для улыбки. С юбилеем!',
            ],
            str_contains($o, 'рожд') => [
                'С днём рождения! Пусть этот год будет таким же ярким, как этот букет.',
                'Поздравляю! Пусть сбываются мечты — и большие, и совсем маленькие.',
                'С днём рождения! Счастья, вдохновения и поводов для радости каждый день.',
            ],
            str_contains($o, 'мам') => [
                'Мамочка, спасибо за твою любовь и заботу. Ты самая лучшая!',
                'Для самой нежной и доброй мамы — с любовью и благодарностью.',
                'Пусть эти цветы скажут то, что не всегда получается сказать словами: я тебя люблю.',
            ],
            str_contains($o, 'март') => [
                'С 8 Марта! Весеннего настроения, тепла и улыбок.',
                'Поздравляю с праздником весны! Пусть рядом будут только приятные люди и события.',
                'С Международным женским днём! Красоты, нежности и счастья.',
            ],
            str_contains($o, 'свадьб') => [
                'Поздравляем со свадьбой! Любви, понимания и долгих счастливых лет вместе.',
                'Пусть ваш союз будет крепким, а дом — полным радости. С днём свадьбы!',
                'Совет да любовь! Пусть каждый день будет праздником.',
            ],
            str_contains($o, 'люб') => [
                'Каждый день рядом с тобой — подарок. Люблю тебя.',
                'Эти цветы — лишь малая часть того, что я чувствую.',
                'Спасибо, что ты есть. С любовью.',
            ],
            default => [
                'Пусть эти цветы поднимут настроение и подарят немного радости!',
                'Просто так — чтобы ты улыбнулась.',
                'С наилучшими пожеланиями и теплом!',
            ],
        };

        if ($tone === 'formal') {
            $templates = array_map(fn ($t) => str_replace(['тебя', 'ты ', 'Ты ', 'твою', 'тобой'], ['Вас', 'Вы ', 'Вы ', 'Вашу', 'Вами'], $t), $templates);
        }

        return $templates;
    }

    // =====================================================================
    // 2. Подбор букета по запросу
    // =====================================================================

    /**
     * @return array{criteria: array, explanation: string, source: string, bouquets: Collection<int, Bouquet>}
     */
    public function suggestBouquets(string $query, ?int $userId): array
    {
        $slugs = Occasion::pluck('slug')->all();
        $source = 'rules';
        $criteria = null;
        $explanation = null;

        $cacheKey = 'ai:suggest:'.md5(mb_strtolower(trim($query)));
        if ($cached = Cache::get($cacheKey)) {
            [$criteria, $explanation, $source] = $cached;
        } elseif ($this->enabled()) {
            $json = $this->chatJson(
                'bouquet_suggestion',
                $userId,
                'Ты — помощник флориста. Извлеки из запроса покупателя критерии подбора букета. '
                .'Отвечай строго в формате JSON: {"occasions": [...], "colors": [...], "min_price": число или null, '
                .'"max_price": число или null, "explanation": "короткое пояснение на русском"}. '
                .'occasions — коды из списка: '.implode(', ', $slugs).'. '
                .'colors — коды из списка: '.implode(', ', FlowerColor::values()).' (пастельные тона — white, pink, purple).',
                $query
            );

            $validator = Validator::make($json ?? [], [
                'occasions' => ['present', 'array'],
                'occasions.*' => ['string', Rule::in($slugs)],
                'colors' => ['present', 'array'],
                'colors.*' => ['string', Rule::enum(FlowerColor::class)],
                'min_price' => ['nullable', 'numeric', 'min:0'],
                'max_price' => ['nullable', 'numeric', 'min:0'],
                'explanation' => ['nullable', 'string', 'max:500'],
            ]);

            if ($json !== null && $validator->passes()) {
                $criteria = [
                    'occasions' => array_values(array_unique($json['occasions'])),
                    'colors' => array_values(array_unique($json['colors'])),
                    'min_price' => isset($json['min_price']) ? (float) $json['min_price'] : null,
                    'max_price' => isset($json['max_price']) ? (float) $json['max_price'] : null,
                ];
                $explanation = $json['explanation'] ?? null;
                $source = 'ai';
            } elseif ($json !== null) {
                Log::channel('ai')->warning('Некорректный ответ ИИ (suggest)', ['errors' => $validator->errors()->all()]);
            }
        }

        $criteria ??= $this->parseQuery($query);
        $explanation ??= $this->explain($criteria);
        Cache::put($cacheKey, [$criteria, $explanation, $source], now()->addHours(6));

        return [
            'criteria' => $criteria,
            'explanation' => $explanation,
            'source' => $source,
            'bouquets' => $this->findBouquets($criteria),
        ];
    }

    /**
     * Встроенный разбор запроса по ключевым словам (используется без ИИ).
     */
    public function parseQuery(string $query): array
    {
        $q = mb_strtolower($query);

        $occasionWords = [
            'anniversary' => ['юбил'],
            'birthday' => ['день рожд', 'днём рожд', 'днем рожд', 'др '],
            'mom' => ['мам', 'матер'],
            'march-8' => ['8 март', 'восьм'],
            'wedding' => ['свадьб', 'невест'],
            'love' => ['любим', 'девушк', 'жене', 'любл', 'свидан'],
            'thanks' => ['спасиб', 'благодар', 'учител', 'коллег', 'врач'],
            'no-reason' => ['без повод', 'просто так'],
        ];
        $colorWords = [
            'red' => ['красн', 'алый', 'алых'],
            'white' => ['бел', 'пастел', 'нежн'],
            'pink' => ['розов', 'пастел', 'нежн'],
            'yellow' => ['жёлт', 'желт', 'ярк', 'солнеч'],
            'orange' => ['оранж', 'ярк'],
            'purple' => ['фиолет', 'сирен', 'лаванд', 'пастел'],
            'blue' => ['син', 'голуб'],
        ];

        $occasions = [];
        foreach ($occasionWords as $slug => $words) {
            foreach ($words as $w) {
                if (str_contains($q, $w)) {
                    $occasions[] = $slug;
                    break;
                }
            }
        }

        $colors = [];
        foreach ($colorWords as $color => $words) {
            foreach ($words as $w) {
                if (str_contains($q, $w)) {
                    $colors[] = $color;
                    break;
                }
            }
        }

        $parseAmount = function (string $number, ?string $suffix): float {
            $value = (float) str_replace([' ', ','], ['', '.'], $number);
            if ($suffix && preg_match('/^(тыс|т\.|к)/u', $suffix)) {
                $value *= 1000;
            }

            return $value;
        };

        $max = null;
        $min = null;
        if (preg_match('/(?:до|не дороже|не более|в пределах)\s*(\d[\d\s]*(?:[.,]\d+)?)\s*(тыс\S*|т\.|к\b)?/u', $q, $m)) {
            $max = $parseAmount($m[1], $m[2] ?? null);
        }
        if (preg_match('/от\s*(\d[\d\s]*(?:[.,]\d+)?)\s*(тыс\S*|т\.|к\b)?/u', $q, $m)) {
            $min = $parseAmount($m[1], $m[2] ?? null);
        }

        return [
            'occasions' => array_values(array_unique($occasions)),
            'colors' => array_values(array_unique($colors)),
            'min_price' => $min,
            'max_price' => $max,
        ];
    }

    private function explain(array $c): string
    {
        $parts = [];
        if ($c['occasions']) {
            $names = Occasion::whereIn('slug', $c['occasions'])->pluck('name')->all();
            $parts[] = 'повод: '.mb_strtolower(implode(', ', $names));
        }
        if ($c['colors']) {
            $parts[] = 'цвета: '.implode(', ', array_map(fn ($v) => mb_strtolower(FlowerColor::from($v)->label()), $c['colors']));
        }
        if ($c['max_price']) {
            $parts[] = 'бюджет до '.number_format($c['max_price'], 0, ',', ' ').' ₸';
        }

        return $parts ? 'Подобрали букеты по критериям — '.implode('; ', $parts).'.' : 'Показываем популярные букеты.';
    }

    /**
     * Поиск в каталоге: бюджет — жёсткое условие, повод и цвета дают баллы релевантности.
     *
     * @return Collection<int, Bouquet>
     */
    public function findBouquets(array $c, int $limit = 3): Collection
    {
        $candidates = Bouquet::active()
            ->with(['flowers', 'occasions'])
            ->when($c['max_price'] ?? null, fn ($q, $max) => $q->where('price', '<=', $max))
            ->when($c['min_price'] ?? null, fn ($q, $min) => $q->where('price', '>=', $min))
            ->get();

        $scored = $candidates->map(function (Bouquet $b) use ($c) {
            $score = 0;
            $score += 3 * $b->occasions->whereIn('slug', $c['occasions'] ?? [])->count();
            if (! empty($c['colors'])) {
                $matching = $b->flowers->filter(fn ($f) => in_array($f->color->value, $c['colors'], true))->count();
                $total = max(1, $b->flowers->count());
                $score += 4 * $matching / $total; // доля цветов нужной гаммы
            }
            $b->setAttribute('relevance', round($score, 2));

            return $b;
        });

        $sorted = $scored->sortBy([
            ['relevance', 'desc'],
            ['price', 'desc'], // при равной релевантности — ближе к верхней границе бюджета
        ])->values();

        return new Collection($sorted->take($limit)->all());
    }

    // =====================================================================
    // Обращение к модели
    // =====================================================================

    private function enabled(): bool
    {
        return (bool) config('services.ai.enabled');
    }

    /**
     * Запрос к OpenAI-совместимому API с ответом в JSON.
     * Любая ошибка (таймаут, код 4xx/5xx, невалидный JSON) → null.
     */
    private function chatJson(string $feature, ?int $userId, string $system, string $user): ?array
    {
        $config = config('services.ai');
        $started = microtime(true);

        try {
            $response = Http::timeout($config['timeout'])
                ->acceptJson()
                ->when($config['api_key'], fn ($request, $key) => $request->withToken($key))
                ->post($config['base_url'].'/chat/completions', [
                    'model' => $config['model'],
                    'temperature' => 0.7,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                ]);

            $logContext = [
                'feature' => $feature,
                'user_id' => $userId,
                'model' => $config['model'],
                'status' => $response->status(),
                'prompt_tokens' => $response->json('usage.prompt_tokens'),
                'completion_tokens' => $response->json('usage.completion_tokens'),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ];

            if ($response->failed()) {
                Log::channel('ai')->warning('ИИ-сервис вернул ошибку', $logContext);

                return null;
            }

            Log::channel('ai')->info('Запрос к ИИ', $logContext);

            $content = (string) $response->json('choices.0.message.content');
            $data = json_decode($this->extractJson($content), true);

            return is_array($data) ? $data : null;
        } catch (Throwable $e) {
            Log::channel('ai')->error('ИИ-сервис недоступен', [
                'feature' => $feature,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /** Модели иногда оборачивают JSON в ```json ... ``` — вырезаем объект. */
    private function extractJson(string $content): string
    {
        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        return ($start !== false && $end !== false && $end > $start)
            ? substr($content, $start, $end - $start + 1)
            : $content;
    }
}
