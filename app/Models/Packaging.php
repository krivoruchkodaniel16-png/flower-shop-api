<?php

namespace App\Models;

use App\Enums\PackagingType;
use Database\Factories\PackagingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Упаковка для индивидуального букета.
 *
 * @property int $id
 * @property string $name
 * @property PackagingType $type
 * @property string $price
 * @property int $max_flowers
 * @property bool $is_active
 */
class Packaging extends Model
{
    /** @use HasFactory<PackagingFactory> */
    use HasFactory;

    protected $table = 'packagings';

    protected $fillable = [
        'name',
        'type',
        'color',
        'price',
        'max_flowers',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => PackagingType::class,
            'price' => 'decimal:2',
            'max_flowers' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** Packaging hasMany CustomBouquet */
    public function customBouquets(): HasMany
    {
        return $this->hasMany(CustomBouquet::class);
    }
}
