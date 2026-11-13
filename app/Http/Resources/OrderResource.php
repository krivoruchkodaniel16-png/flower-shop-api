<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'items_total' => (float) $this->items_total,
            'delivery_price' => (float) $this->delivery_price,
            'postcard_price' => (float) $this->postcard_price,
            'total_price' => (float) $this->total_price,
            'comment' => $this->comment,
            'customer' => new UserResource($this->whenLoaded('user')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'delivery' => new DeliveryResource($this->whenLoaded('delivery')),
            'postcard' => new PostcardResource($this->whenLoaded('postcard')),
            // подсказка клиенту: какие статусы может выставить текущий пользователь
            // (окончательная проверка всё равно выполняется на сервере)
            'next_statuses' => $user ? $this->nextStatusesFor($user->role) : [],
            'can_cancel' => $user
                && $user->id === $this->user_id
                && $this->status === OrderStatus::New,
            'created_at' => $this->created_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
        ];
    }

    /** @return list<array{value:string,label:string}> */
    private function nextStatusesFor(?UserRole $role): array
    {
        if (! $role) {
            return [];
        }

        return collect($this->status->allowedTransitions())
            ->filter(fn (OrderStatus $s) => in_array($role, $s->rolesAllowedToSet(), true))
            ->map(fn (OrderStatus $s) => ['value' => $s->value, 'label' => $s->label()])
            ->values()
            ->all();
    }
}
