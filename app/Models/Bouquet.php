<?php

namespace App\Models;

use Database\Factories\BouquetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Готовый букет каталога.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $price
 * @property string|null $image_path
 * @property bool $is_active
 */
class Bouquet extends Model
{
    /** @use HasFactory<BouquetFactory> */
    use HasFactory;

    /** Белый список полей сортировки (защита от произвольного ORDER BY). */
    public const SORTABLE = ['price', 'name', 'created_at'];

    protected $fillable = [
        'name',
        'description',
        'price',
        'image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // ---------- Связи ----------

    /** Состав букета: Bouquet belongsToMany Flower (bouquet_flower.quantity) */
    public function flowers(): BelongsToMany
    {
        return $this->belongsToMany(Flower::class)->withPivot('quantity')->withTimestamps();
    }

    /** Поводы: Bouquet belongsToMany Occasion */
    public function occasions(): BelongsToMany
    {
        return $this->belongsToMany(Occasion::class)->withTimestamps();
    }

    /** Bouquet hasMany OrderItem */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ---------- Скоупы ----------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Поиск по части названия или описания. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $q, string $term) {
            $q->where(function (Builder $w) use ($term) {
                $w->where('name', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%');
            });
        });
    }

    /**
     * Фильтры каталога: повод, цветы, цвет, диапазон цены.
     *
     * @param  array{occasion_id?:int, flower_ids?:array<int>, color?:string, price_min?:numeric, price_max?:numeric}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['occasion_id'] ?? null, fn (Builder $q, $id) => $q->whereHas('occasions', fn (Builder $o) => $o->whereKey($id)))
            ->when($filters['flower_ids'] ?? null, function (Builder $q, array $ids) {
                // букет должен содержать ВСЕ выбранные цветы
                foreach ($ids as $flowerId) {
                    $q->whereHas('flowers', fn (Builder $f) => $f->whereKey($flowerId));
                }
            })
            ->when($filters['color'] ?? null, fn (Builder $q, $color) => $q->whereHas('flowers', fn (Builder $f) => $f->where('color', $color)))
            ->when(isset($filters['price_min']), fn (Builder $q) => $q->where('price', '>=', $filters['price_min']))
            ->when(isset($filters['price_max']), fn (Builder $q) => $q->where('price', '<=', $filters['price_max']));
    }

    // ---------- Вспомогательное ----------

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
