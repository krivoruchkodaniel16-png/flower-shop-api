<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Postcard */
class PostcardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'design' => $this->design?->value,
            'design_label' => $this->design?->label(),
            'text' => $this->text,
            'signature' => $this->signature,
        ];
    }
}
