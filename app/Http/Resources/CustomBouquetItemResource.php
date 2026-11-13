<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CustomBouquetItem */
class CustomBouquetItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'flower_id' => $this->flower_id,
            'quantity' => $this->quantity,
            'flower' => new FlowerResource($this->whenLoaded('flower')),
            'subtotal' => $this->whenLoaded('flower', fn () => round((float) $this->flower->price * $this->quantity, 2)),
        ];
    }
}
