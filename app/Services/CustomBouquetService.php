<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\CustomBouquet;
use App\Models\Flower;
use App\Models\Packaging;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Конструктор букетов: расчёт цены и проверка правил сборки.
 *
 * Бизнес-правила конструктора:
 *  1. Цветы и упаковка должны быть в продаже (is_active).
 *  2. В букете не менее shop.custom_min_flowers стеблей.
 *  3. Количество стеблей не превышает вместимость упаковки (max_flowers).
 *  4. Общее количество цветов — нечётное (по традиции; чётное число дарят на траурные события).
 *  5. Каждого цветка должно хватать на складе (окончательно проверяется при заказе).
 */
class CustomBouquetService
{
    public const RELATIONS = ['items.flower', 'packaging'];

    public function paginate(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return CustomBouquet::query()
            ->where('user_id', $user->id)
            ->with(self::RELATIONS)
            ->withExists('orderItems')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function load(CustomBouquet $bouquet): CustomBouquet
    {
        $bouquet->load(self::RELATIONS);
        $bouquet->setAttribute('order_items_exists', $bouquet->orderItems()->exists());

        return $bouquet;
    }

    /**
     * Расчёт стоимости без сохранения. Нарушения правил возвращаются
     * как предупреждения — конструктор показывает их пользователю «на лету».
     */
    public function calculate(array $data): array
    {
        $packaging = Packaging::findOrFail($data['packaging_id']);
        $flowers = $this->flowersFor($data['items']);
        $problems = $this->checkRules($packaging, $data['items'], $flowers, checkStock: true);

        $lines = collect($data['items'])->map(function (array $item) use ($flowers) {
            $flower = $flowers[$item['flower_id']];

            return [
                'flower_id' => $flower->id,
                'name' => $flower->name,
                'color' => $flower->color->value,
                'unit_price' => (float) $flower->price,
                'quantity' => (int) $item['quantity'],
                'subtotal' => round((float) $flower->price * $item['quantity'], 2),
                'in_stock' => $flower->stock_quantity,
            ];
        })->values();

        $flowersTotal = (float) $lines->sum('subtotal');
        $assembly = (float) config('shop.assembly_fee');

        return [
            'items' => $lines,
            'flowers_count' => (int) $lines->sum('quantity'),
            'flowers_total' => round($flowersTotal, 2),
            'packaging' => [
                'id' => $packaging->id,
                'name' => $packaging->name,
                'price' => (float) $packaging->price,
                'max_flowers' => $packaging->max_flowers,
            ],
            'assembly_fee' => $assembly,
            'total' => round($flowersTotal + (float) $packaging->price + $assembly, 2),
            'is_valid' => $problems === [],
            'warnings' => $problems,
        ];
    }

    public function create(User $user, array $data): CustomBouquet
    {
        $packaging = Packaging::findOrFail($data['packaging_id']);
        $this->assertValid($packaging, $data['items']);

        return DB::transaction(function () use ($user, $data) {
            $bouquet = CustomBouquet::create([
                'user_id' => $user->id,
                'packaging_id' => $data['packaging_id'],
                'name' => $data['name'],
                'comment' => $data['comment'] ?? null,
            ]);

            $bouquet->items()->createMany($this->normalizeItems($data['items']));

            return $this->load($bouquet);
        });
    }

    public function update(CustomBouquet $bouquet, array $data): CustomBouquet
    {
        $this->assertNotOrdered($bouquet);

        $bouquet->loadMissing('items');
        $packagingId = $data['packaging_id'] ?? $bouquet->packaging_id;
        $items = $data['items'] ?? $bouquet->items->map(fn ($i) => [
            'flower_id' => $i->flower_id,
            'quantity' => $i->quantity,
        ])->all();

        $this->assertValid(Packaging::findOrFail($packagingId), $items);

        return DB::transaction(function () use ($bouquet, $data, $packagingId) {
            $bouquet->update([
                'packaging_id' => $packagingId,
                'name' => $data['name'] ?? $bouquet->name,
                'comment' => array_key_exists('comment', $data) ? $data['comment'] : $bouquet->comment,
            ]);

            if (isset($data['items'])) {
                $bouquet->items()->delete();
                $bouquet->items()->createMany($this->normalizeItems($data['items']));
            }

            return $this->load($bouquet->refresh());
        });
    }

    public function delete(CustomBouquet $bouquet): void
    {
        $this->assertNotOrdered($bouquet);

        $bouquet->delete();
    }

    /**
     * Проверка правил сборки. Возвращает список нарушений (пустой — всё в порядке).
     *
     * @param  array<int, array{flower_id:int, quantity:int}>  $items
     * @param  Collection<int, Flower>  $flowers  ключ — id цветка
     * @return list<string>
     */
    public function checkRules(Packaging $packaging, array $items, Collection $flowers, bool $checkStock = false): array
    {
        $problems = [];

        if (! $packaging->is_active) {
            $problems[] = "Упаковка «{$packaging->name}» сейчас недоступна.";
        }

        foreach ($items as $item) {
            $flower = $flowers[$item['flower_id']] ?? null;
            if (! $flower) {
                continue;
            }
            if (! $flower->is_active) {
                $problems[] = "Цветок «{$flower->name}» снят с продажи.";
            } elseif ($checkStock && $flower->stock_quantity < $item['quantity']) {
                $problems[] = "Цветка «{$flower->name}» в наличии только {$flower->stock_quantity} шт.";
            }
        }

        $total = (int) array_sum(array_column($items, 'quantity'));
        $min = (int) config('shop.custom_min_flowers');

        if ($total < $min) {
            $problems[] = "В букете должно быть не менее {$min} цветов (сейчас {$total}).";
        }
        if ($total > $packaging->max_flowers) {
            $problems[] = "Упаковка «{$packaging->name}» вмещает не более {$packaging->max_flowers} цветов, а в букете {$total}.";
        }
        if ($total > 0 && $total % 2 === 0) {
            $problems[] = "В букете чётное количество цветов ({$total}). По традиции дарят нечётное — добавьте или уберите один цветок.";
        }

        return $problems;
    }

    /** @return Collection<int, Flower> */
    public function flowersFor(array $items): Collection
    {
        return Flower::whereIn('id', array_column($items, 'flower_id'))->get()->keyBy('id');
    }

    private function assertValid(Packaging $packaging, array $items): void
    {
        $problems = $this->checkRules($packaging, $items, $this->flowersFor($items));

        if ($problems !== []) {
            throw new BusinessException($problems[0], 409, ['problems' => $problems]);
        }
    }

    private function assertNotOrdered(CustomBouquet $bouquet): void
    {
        if ($bouquet->isOrdered()) {
            throw new BusinessException('Букет «'.$bouquet->name.'» уже заказан — изменить или удалить его нельзя. Соберите новый букет в конструкторе.');
        }
    }

    private function normalizeItems(array $items): array
    {
        return array_map(fn (array $i) => [
            'flower_id' => (int) $i['flower_id'],
            'quantity' => (int) $i['quantity'],
        ], $items);
    }
}
