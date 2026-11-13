<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Flower;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FlowerService
{
    public function paginate(array $filters, bool $canSeeInactive = false): LengthAwarePaginator
    {
        $sort = $filters['sort'] ?? 'name';
        $direction = $filters['order'] ?? 'asc';
        $showInactive = $canSeeInactive && ! empty($filters['include_inactive']);

        return Flower::query()
            ->when(! $showInactive, fn ($q) => $q->active())
            ->search($filters['search'] ?? null)
            ->filter($filters)
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 50)
            ->withQueryString();
    }

    public function create(array $data): Flower
    {
        return Flower::create($data);
    }

    public function update(Flower $flower, array $data): Flower
    {
        $flower->update($data);

        return $flower;
    }

    /**
     * Цветок, входящий в готовые или индивидуальные букеты, удалить нельзя — 409.
     */
    public function delete(Flower $flower): void
    {
        $inBouquets = $flower->bouquets()->count();
        $inCustom = $flower->customBouquetItems()->count();

        if ($inBouquets > 0 || $inCustom > 0) {
            throw new BusinessException(
                "Цветок «{$flower->name}» используется в букетах (готовых: {$inBouquets}, индивидуальных: {$inCustom}). "
                .'Удалите его из состава или снимите с продажи (is_active = false).'
            );
        }

        $flower->delete();
    }

    /**
     * Поступление (+) или списание (−) цветов. Остаток не может стать отрицательным.
     * Строка блокируется (lockForUpdate), чтобы не конфликтовать с оформлением заказов.
     */
    public function changeStock(Flower $flower, int $delta, ?string $reason, User $actor): Flower
    {
        return DB::transaction(function () use ($flower, $delta, $reason, $actor) {
            /** @var Flower $locked */
            $locked = Flower::whereKey($flower->id)->lockForUpdate()->firstOrFail();

            $newStock = $locked->stock_quantity + $delta;
            if ($newStock < 0) {
                throw new BusinessException(
                    "Нельзя списать ".abs($delta)." шт.: на складе «{$locked->name}» только {$locked->stock_quantity} шт."
                );
            }

            $locked->update(['stock_quantity' => $newStock]);

            Log::info('Изменён остаток цветов', [
                'flower_id' => $locked->id,
                'delta' => $delta,
                'stock' => $newStock,
                'reason' => $reason,
                'user_id' => $actor->id,
            ]);

            return $locked;
        });
    }
}
