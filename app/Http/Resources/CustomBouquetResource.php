<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CustomBouquet */
class CustomBouquetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $full = $this->relationLoaded('items') && $this->relationLoaded('packaging');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'comment' => $this->comment,
            'packaging_id' => $this->packaging_id,
            'packaging' => new PackagingResource($this->whenLoaded('packaging')),
            'items' => CustomBouquetItemResource::collection($this->whenLoaded('items')),
            'flowers_count' => $this->whenLoaded('items', fn () => (int) $this->items->sum('quantity')),
            'assembly_fee' => (float) config('shop.assembly_fee'),
            'price' => $this->when($full, fn () => $this->price()),
            // букет уже входит в заказ — редактирование запрещено
            'is_ordered' => $this->when(isset($this->order_items_exists), fn () => (bool) $this->order_items_exists),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
