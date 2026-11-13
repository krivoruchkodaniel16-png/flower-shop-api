<?php

namespace App\Models;

use Database\Factories\CustomBouquetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Индивидуальный букет из конструктора.
 *
 * @property int $id
 * @property int $user_id
 * @property int $packaging_id
 * @property string $name
 * @property string|null $comment
 */
class CustomBouquet extends Model
{
    /** @use HasFactory<CustomBouquetFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'packaging_id',
        'name',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'packaging_id' => 'integer',
        ];
    }

    /** CustomBouquet belongsTo User */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** CustomBouquet belongsTo Packaging */
    public function packaging(): BelongsTo
    {
        return $this->belongsTo(Packaging::class);
    }

    /** CustomBouquet hasMany CustomBouquetItem */
    public function items(): HasMany
    {
        return $this->hasMany(CustomBouquetItem::class);
    }

    /** CustomBouquet belongsToMany Flower через custom_bouquet_items */
    public function flowers(): BelongsToMany
    {
        return $this->belongsToMany(Flower::class, 'custom_bouquet_items')->withPivot('quantity')->withTimestamps();
    }

    /** CustomBouquet hasMany OrderItem */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Общее количество стеблей. */
    public function flowersCount(): int
    {
        $this->loadMissing('items');

        return (int) $this->items->sum('quantity');
    }

    /**
     * Текущая стоимость: цветы × количество + упаковка + сборка.
     * Не хранится в БД, так как полностью определяется составом (3НФ).
     */
    public function price(): float
    {
        $this->loadMissing(['items.flower', 'packaging']);

        $flowers = $this->items->sum(fn (CustomBouquetItem $item) => (float) $item->flower->price * $item->quantity);

        return round($flowers + (float) $this->packaging->price + (float) config('shop.assembly_fee'), 2);
    }

    /** Букет уже заказан — менять и удалять его нельзя. */
    public function isOrdered(): bool
    {
        return $this->orderItems()->exists();
    }
}
