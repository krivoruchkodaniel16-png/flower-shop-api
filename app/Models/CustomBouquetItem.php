<?php

namespace App\Models;

use Database\Factories\CustomBouquetItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Позиция индивидуального букета.
 *
 * @property int $id
 * @property int $custom_bouquet_id
 * @property int $flower_id
 * @property int $quantity
 */
class CustomBouquetItem extends Model
{
    /** @use HasFactory<CustomBouquetItemFactory> */
    use HasFactory;

    protected $fillable = [
        'custom_bouquet_id',
        'flower_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    /** CustomBouquetItem belongsTo CustomBouquet */
    public function customBouquet(): BelongsTo
    {
        return $this->belongsTo(CustomBouquet::class);
    }

    /** CustomBouquetItem belongsTo Flower */
    public function flower(): BelongsTo
    {
        return $this->belongsTo(Flower::class);
    }
}
