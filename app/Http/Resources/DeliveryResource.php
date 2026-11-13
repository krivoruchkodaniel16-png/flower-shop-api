<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Delivery */
class DeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'address' => $this->address,
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'delivery_date' => $this->delivery_date?->format('Y-m-d'),
            'time_from' => substr((string) $this->time_from, 0, 5),
            'time_to' => substr((string) $this->time_to, 0, 5),
            'courier_id' => $this->courier_id,
            'courier' => new UserResource($this->whenLoaded('courier')),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'courier_comment' => $this->courier_comment,
        ];
    }
}
