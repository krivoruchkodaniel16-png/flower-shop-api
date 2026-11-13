<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Позиция заказа: готовый букет либо индивидуальный букет.
 *
 * @property int $id
 * @property int $order_id
 * @property int|null $bouquet_id
 * @property int|null $custom_bouquet_id
 * @property int $quantity
 * @property string $unit_price
 */
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'bouquet_id',
        'custom_bouquet_id',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
        ];
    }

    /** OrderItem belongsTo Order */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** OrderItem belongsTo Bouquet (может быть null) */
    public function bouquet(): BelongsTo
    {
        return $this->belongsTo(Bouquet::class);
    }

    /** OrderItem belongsTo CustomBouquet (может быть null) */
    public function customBouquet(): BelongsTo
    {
        return $this->belongsTo(CustomBouquet::class);
    }

    public function subtotal(): float
    {
        return round((float) $this->unit_price * $this->quantity, 2);
    }
}
