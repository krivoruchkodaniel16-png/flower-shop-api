<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Заказ.
 *
 * @property int $id
 * @property int $user_id
 * @property OrderStatus $status
 * @property string $items_total
 * @property string $delivery_price
 * @property string $postcard_price
 * @property string $total_price
 * @property string|null $comment
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const SORTABLE = ['created_at', 'total_price', 'delivery_date', 'id'];

    protected $fillable = [
        'user_id',
        'status',
        'items_total',
        'delivery_price',
        'postcard_price',
        'total_price',
        'comment',
        'cancelled_at',
    ];

    protected $attributes = [
        'status' => 'new',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'status' => OrderStatus::class,
            'items_total' => 'decimal:2',
            'delivery_price' => 'decimal:2',
            'postcard_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    // ---------- Связи ----------

    /** Order belongsTo User (покупатель) */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Order hasMany OrderItem */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Order hasOne Delivery */
    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    /** Order hasOne Postcard */
    public function postcard(): HasOne
    {
        return $this->hasOne(Postcard::class);
    }

    // ---------- Скоупы ----------

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $date) => $q->whereHas('delivery', fn (Builder $d) => $d->whereDate('delivery_date', '>=', $date)))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $date) => $q->whereHas('delivery', fn (Builder $d) => $d->whereDate('delivery_date', '<=', $date)))
            ->when($filters['search'] ?? null, function (Builder $q, string $term) {
                $q->where(function (Builder $w) use ($term) {
                    if (ctype_digit($term)) {
                        $w->orWhere('orders.id', (int) $term);
                    }
                    $w->orWhereHas('delivery', fn (Builder $d) => $d->where(fn (Builder $x) => $x
                        ->where('recipient_name', 'like', '%'.$term.'%')
                        ->orWhere('address', 'like', '%'.$term.'%')));
                });
            });
    }
}
