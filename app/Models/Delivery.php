<?php

namespace App\Models;

use Database\Factories\DeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Доставка заказа.
 *
 * @property int $id
 * @property int $order_id
 * @property int|null $courier_id
 * @property string $address
 * @property string $recipient_name
 * @property string $recipient_phone
 * @property \Illuminate\Support\Carbon $delivery_date
 * @property string $time_from
 * @property string $time_to
 */
class Delivery extends Model
{
    /** @use HasFactory<DeliveryFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'courier_id',
        'address',
        'recipient_name',
        'recipient_phone',
        'delivery_date',
        'time_from',
        'time_to',
        'delivered_at',
        'courier_comment',
    ];

    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'courier_id' => 'integer',
            'delivery_date' => 'date:Y-m-d',
            'delivered_at' => 'datetime',
        ];
    }

    /** Delivery belongsTo Order */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Delivery belongsTo User (курьер) */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }
}
