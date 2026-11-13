<?php

namespace App\Models;

use Database\Factories\OccasionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Повод для букета (справочник).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 */
class Occasion extends Model
{
    /** @use HasFactory<OccasionFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    /** Occasion belongsToMany Bouquet (таблица bouquet_occasion) */
    public function bouquets(): BelongsToMany
    {
        return $this->belongsToMany(Bouquet::class)->withTimestamps();
    }
}
