<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Bouquet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BouquetService
{
    private const RELATIONS = ['flowers', 'occasions'];

    /**
     * Каталог: фильтр (повод, цветы, цвет, цена), поиск, сортировка по белому списку, пагинация.
     */
    public function paginate(array $filters, bool $canSeeInactive = false): LengthAwarePaginator
    {
        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['order'] ?? ($sort === 'created_at' ? 'desc' : 'asc');
        $showInactive = $canSeeInactive && ! empty($filters['include_inactive']);

        return Bouquet::query()
            ->with(self::RELATIONS)
            ->when(! $showInactive, fn ($q) => $q->active())
            ->search($filters['search'] ?? null)
            ->filter($filters)
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 12)
            ->withQueryString();
    }

    public function load(Bouquet $bouquet): Bouquet
    {
        return $bouquet->load(self::RELATIONS)->loadCount('orderItems');
    }

    public function create(array $data): Bouquet
    {
        return DB::transaction(function () use ($data) {
            $bouquet = Bouquet::create(Arr::only($data, ['name', 'description', 'price', 'is_active']));
            $this->syncRelations($bouquet, $data);

            Log::info('Создан букет', ['bouquet_id' => $bouquet->id]);

            return $bouquet->load(self::RELATIONS);
        });
    }

    public function update(Bouquet $bouquet, array $data): Bouquet
    {
        return DB::transaction(function () use ($bouquet, $data) {
            $bouquet->update(Arr::only($data, ['name', 'description', 'price', 'is_active']));
            $this->syncRelations($bouquet, $data);

            return $bouquet->load(self::RELATIONS);
        });
    }

    /**
     * Удаление. Букет, который уже заказывали, удалить нельзя (история заказов) — 409.
     */
    public function delete(Bouquet $bouquet): void
    {
        if ($bouquet->orderItems()->exists()) {
            throw new BusinessException(
                'Букет «'.$bouquet->name.'» есть в заказах, удалить его нельзя. Снимите букет с продажи (is_active = false).'
            );
        }

        DB::transaction(function () use ($bouquet) {
            $path = $bouquet->image_path;
            $bouquet->delete(); // состав и поводы удаляются каскадно
            if ($path) {
                Storage::disk('public')->delete($path);
            }
        });
    }

    /** Загрузка фото букета в storage/app/public/bouquets. */
    public function uploadImage(Bouquet $bouquet, UploadedFile $file): Bouquet
    {
        $old = $bouquet->image_path;
        $path = $file->store('bouquets', 'public');

        $bouquet->update(['image_path' => $path]);

        if ($old) {
            Storage::disk('public')->delete($old);
        }

        return $bouquet->load(self::RELATIONS);
    }

    /** Синхронизация связей «многие ко многим»: состав (с количеством) и поводы. */
    private function syncRelations(Bouquet $bouquet, array $data): void
    {
        if (array_key_exists('flowers', $data)) {
            $bouquet->flowers()->sync(
                collect($data['flowers'])->mapWithKeys(fn (array $f) => [
                    (int) $f['flower_id'] => ['quantity' => (int) $f['quantity']],
                ])->all()
            );
        }

        if (array_key_exists('occasion_ids', $data)) {
            $bouquet->occasions()->sync($data['occasion_ids'] ?? []);
        }
    }
}
