<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Packaging */
class PackagingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'color' => $this->color,
            'price' => (float) $this->price,
            'max_flowers' => $this->max_flowers,
            'is_active' => $this->is_active,
        ];
    }
}
