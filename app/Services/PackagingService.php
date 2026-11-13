<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Packaging;
use Illuminate\Database\Eloquent\Collection;

class PackagingService
{
    public function list(bool $withInactive = false): Collection
    {
        return Packaging::query()
            ->when(! $withInactive, fn ($q) => $q->where('is_active', true))
            ->orderBy('price')
            ->get();
    }

    public function create(array $data): Packaging
    {
        return Packaging::create($data);
    }

    public function update(Packaging $packaging, array $data): Packaging
    {
        $packaging->update($data);

        return $packaging;
    }

    public function delete(Packaging $packaging): void
    {
        if ($packaging->customBouquets()->exists()) {
            throw new BusinessException(
                "Упаковка «{$packaging->name}» используется в индивидуальных букетах. Отключите её (is_active = false) вместо удаления."
            );
        }

        $packaging->delete();
    }
}
