<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Flower */
class FlowerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color?->value,
            'color_label' => $this->color?->label(),
            'price' => (float) $this->price,
            'stock_quantity' => $this->stock_quantity,
            'in_stock' => $this->stock_quantity > 0,
            'description' => $this->description,
            'is_active' => $this->is_active,
            // количество стеблей в составе готового букета
            'quantity' => $this->whenPivotLoaded('bouquet_flower', fn () => (int) $this->pivot->quantity),
            'bouquets_count' => $this->whenCounted('bouquets'),
        ];
    }
}
