<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Bouquet */
class BouquetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'image_url' => $this->imageUrl(),
            'is_active' => $this->is_active,
            'stems_count' => $this->whenLoaded('flowers', fn () => (int) $this->flowers->sum('pivot.quantity')),
            'flowers' => FlowerResource::collection($this->whenLoaded('flowers')),
            'occasions' => OccasionResource::collection($this->whenLoaded('occasions')),
            'orders_count' => $this->whenCounted('orderItems'),
            // релевантность при ИИ-подборе букета
            'relevance' => $this->when(isset($this->resource->relevance), fn () => $this->resource->relevance),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
