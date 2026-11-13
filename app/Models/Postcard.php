<?php

namespace App\Models;

use App\Enums\PostcardDesign;
use Database\Factories\PostcardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Открытка к заказу.
 *
 * @property int $id
 * @property int $order_id
 * @property PostcardDesign $design
 * @property string $text
 * @property string|null $signature
 */
class Postcard extends Model
{
    /** @use HasFactory<PostcardFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'design',
        'text',
        'signature',
    ];

    protected function casts(): array
    {
        return ['design' => PostcardDesign::class];
    }

    /** Postcard belongsTo Order */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
