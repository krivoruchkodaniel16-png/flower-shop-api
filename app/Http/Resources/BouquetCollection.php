<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Список букетов каталога. При передаче пагинатора ответ содержит
 * data, links и meta (current_page, last_page, per_page, total).
 */
class BouquetCollection extends ResourceCollection
{
    public $collects = BouquetResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
