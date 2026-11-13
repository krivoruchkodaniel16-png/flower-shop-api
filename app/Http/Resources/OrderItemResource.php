<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\OrderItem */
class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->bouquet_id ? 'bouquet' : 'custom',
            'bouquet_id' => $this->bouquet_id,
            'custom_bouquet_id' => $this->custom_bouquet_id,
            'name' => $this->when(
                $this->relationLoaded('bouquet') || $this->relationLoaded('customBouquet'),
                fn () => $this->bouquet?->name ?? $this->customBouquet?->name
            ),
            'bouquet' => new BouquetResource($this->whenLoaded('bouquet')),
            'custom_bouquet' => new CustomBouquetResource($this->whenLoaded('customBouquet')),
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'subtotal' => $this->subtotal(),
        ];
    }
}
