<?php

namespace App\Models;

use App\Enums\FlowerColor;
use Database\Factories\FlowerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Цветок (единица складского учёта).
 *
 * @property int $id
 * @property string $name
 * @property FlowerColor $color
 * @property string $price
 * @property int $stock_quantity
 * @property bool $is_active
 */
class Flower extends Model
{
    /** @use HasFactory<FlowerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'price',
        'stock_quantity',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'color' => FlowerColor::class,
            'price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** Flower belongsToMany Bouquet (таблица bouquet_flower, поле quantity) */
    public function bouquets(): BelongsToMany
    {
        return $this->belongsToMany(Bouquet::class)->withPivot('quantity')->withTimestamps();
    }

    /** Flower hasMany CustomBouquetItem */
    public function customBouquetItems(): HasMany
    {
        return $this->hasMany(CustomBouquetItem::class);
    }

    // ---------- Скоупы ----------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Поиск по части названия. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where('name', 'like', '%'.$term.'%'));
    }

    /** Фильтрация: цвет, наличие на складе, активность. */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['color'] ?? null, fn (Builder $q, $color) => $q->where('color', $color))
            ->when(! empty($filters['in_stock']), fn (Builder $q) => $q->where('stock_quantity', '>', 0))
            ->when(isset($filters['is_active']), fn (Builder $q) => $q->where('is_active', (bool) $filters['is_active']));
    }
}
