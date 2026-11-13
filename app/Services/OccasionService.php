<?php

namespace App\Services;

use App\Models\Occasion;
use Illuminate\Database\Eloquent\Collection;

class OccasionService
{
    public function list(): Collection
    {
        return Occasion::query()->withCount('bouquets')->orderBy('name')->get();
    }

    public function create(array $data): Occasion
    {
        return Occasion::create($data);
    }

    public function update(Occasion $occasion, array $data): Occasion
    {
        $occasion->update($data);

        return $occasion;
    }

    /** Связи с букетами удаляются каскадно (bouquet_occasion). */
    public function delete(Occasion $occasion): void
    {
        $occasion->delete();
    }
}
