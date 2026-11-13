<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PostcardDesign;
use App\Enums\UserRole;
use App\Exceptions\BusinessException;
use App\Models\Bouquet;
use App\Models\CustomBouquet;
use App\Models\Delivery;
use App\Models\Flower;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Заказы — ключевой сценарий приложения.
 *
 * Бизнес-правила:
 *  1. Нельзя заказать больше цветов, чем есть на складе; при оформлении остатки
 *     списываются, при отмене — возвращаются (транзакция + lockForUpdate).
 *  2. Доставка — только в рабочее время (09:00–21:00), интервал не короче 60 минут,
 *     начало не раньше чем через 3 часа после оформления.
 *  3. Статус меняется только по допустимым переходам (OrderStatus::allowedTransitions)
 *     и только ролью, которой этот переход разрешён (OrderPolicy).
 *  4. Покупатель может отменить заказ и изменить открытку, пока заказ в статусе «Новый».
 *  5. В заказ нельзя добавить снятый с продажи букет или чужой индивидуальный букет.
 */
class OrderService
{
    public const RELATIONS = [
        'user',
        'items.bouquet',
        'items.customBouquet.items.flower',
        'items.customBouquet.packaging',
        'delivery.courier',
        'postcard',
    ];

    public function __construct(private readonly CustomBouquetService $customBouquets)
    {
    }

    // =====================================================================
    // Списки
    // =====================================================================

    /**
     * Список заказов с учётом роли:
     *  покупатель — свои; флорист — очередь на сборку; курьер — свободные готовые и свои;
     *  администратор — все.
     */
    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        $sort = $filters['sort'] ?? ($user->hasRole(UserRole::Florist, UserRole::Courier) ? 'delivery_date' : 'created_at');
        $direction = $filters['order'] ?? ($sort === 'delivery_date' ? 'asc' : 'desc');

        $query = Order::query()->with(self::RELATIONS);
        $this->scopeForUser($query, $user);
        $query->filter($filters);

        if ($sort === 'delivery_date') {
            // сортировка по дате доставки через подзапрос к связанной таблице
            $query->orderBy(
                Delivery::select('delivery_date')->whereColumn('deliveries.order_id', 'orders.id'),
                $direction
            );
        } else {
            $query->orderBy('orders.'.$sort, $direction);
        }

        return $query->orderBy('orders.id', 'desc')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();
    }

    private function scopeForUser(Builder $query, User $user): void
    {
        match ($user->role) {
            UserRole::Customer => $query->where('orders.user_id', $user->id),
            UserRole::Florist => $query->whereIn('orders.status', [
                OrderStatus::New->value, OrderStatus::Assembling->value, OrderStatus::Ready->value,
            ]),
            UserRole::Courier => $query->where(function (Builder $q) use ($user) {
                $q->whereHas('delivery', fn (Builder $d) => $d->where('courier_id', $user->id))
                    ->orWhere(fn (Builder $free) => $free
                        ->where('orders.status', OrderStatus::Ready->value)
                        ->whereHas('delivery', fn (Builder $d) => $d->whereNull('courier_id')));
            }),
            UserRole::Admin => null,
        };
    }

    public function load(Order $order): Order
    {
        return $order->load(self::RELATIONS);
    }

    // =====================================================================
    // Ключевой сценарий: оформление заказа
    // =====================================================================

    public function create(User $user, array $data): Order
    {
        $this->assertDeliveryTime($data['delivery']);

        $order = DB::transaction(function () use ($user, $data) {
            [$lines, $needed] = $this->resolveLines($user, $data['items']);

            // Проверка и списание остатков (строки цветов заблокированы до конца транзакции)
            $this->writeOffFlowers($needed);

            $itemsTotal = round(array_sum(array_map(fn ($l) => $l['unit_price'] * $l['quantity'], $lines)), 2);
            $deliveryPrice = $itemsTotal >= (float) config('shop.free_delivery_from') ? 0.0 : (float) config('shop.delivery_price');
            $postcardPrice = ! empty($data['postcard']) ? (float) config('shop.postcard_price') : 0.0;

            $order = Order::create([
                'user_id' => $user->id,
                'status' => OrderStatus::New,
                'items_total' => $itemsTotal,
                'delivery_price' => $deliveryPrice,
                'postcard_price' => $postcardPrice,
                'total_price' => $itemsTotal + $deliveryPrice + $postcardPrice,
                'comment' => $data['comment'] ?? null,
            ]);

            $order->items()->createMany($lines);

            $order->delivery()->create(Arr::only($data['delivery'], [
                'address', 'recipient_name', 'recipient_phone', 'delivery_date', 'time_from', 'time_to',
            ]));

            if (! empty($data['postcard'])) {
                $order->postcard()->create([
                    'design' => $data['postcard']['design'] ?? PostcardDesign::Classic->value,
                    'text' => $data['postcard']['text'],
                    'signature' => $data['postcard']['signature'] ?? null,
                ]);
            }

            return $order;
        });

        Log::info('Оформлен заказ', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'total' => (float) $order->total_price,
            'items' => count($data['items']),
        ]);

        return $this->load($order);
    }

    /**
     * Позиции заказа с ценами и потребность в цветах.
     *
     * @return array{0: list<array>, 1: array<int,int>} [позиции, flower_id => нужно штук]
     */
    private function resolveLines(User $user, array $items): array
    {
        $lines = [];
        $needed = [];

        foreach ($items as $index => $item) {
            $position = $index + 1;
            $quantity = (int) $item['quantity'];
            $hasBouquet = ! empty($item['bouquet_id']);
            $hasCustom = ! empty($item['custom_bouquet_id']);

            if ($hasBouquet === $hasCustom) {
                throw new BusinessException("Позиция №{$position}: укажите либо готовый букет, либо индивидуальный.", 422);
            }

            if ($hasBouquet) {
                $bouquet = Bouquet::with('flowers')->findOrFail($item['bouquet_id']);

                if (! $bouquet->is_active) {
                    throw new BusinessException("Букет «{$bouquet->name}» снят с продажи.");
                }

                foreach ($bouquet->flowers as $flower) {
                    $needed[$flower->id] = ($needed[$flower->id] ?? 0) + $flower->pivot->quantity * $quantity;
                }

                $lines[] = [
                    'bouquet_id' => $bouquet->id,
                    'custom_bouquet_id' => null,
                    'quantity' => $quantity,
                    'unit_price' => (float) $bouquet->price,
                ];

                continue;
            }

            $custom = CustomBouquet::with(['items.flower', 'packaging'])->findOrFail($item['custom_bouquet_id']);

            if ($custom->user_id !== $user->id) {
                throw new BusinessException('Нельзя заказать индивидуальный букет другого пользователя.', 403);
            }

            $customItems = $custom->items->map(fn ($i) => ['flower_id' => $i->flower_id, 'quantity' => $i->quantity])->all();
            $problems = $this->customBouquets->checkRules(
                $custom->packaging,
                $customItems,
                $custom->items->pluck('flower', 'flower_id')
            );
            if ($problems !== []) {
                throw new BusinessException("Букет «{$custom->name}»: {$problems[0]}");
            }

            foreach ($custom->items as $ci) {
                $needed[$ci->flower_id] = ($needed[$ci->flower_id] ?? 0) + $ci->quantity * $quantity;
            }

            $lines[] = [
                'bouquet_id' => null,
                'custom_bouquet_id' => $custom->id,
                'quantity' => $quantity,
                'unit_price' => $custom->price(),
            ];
        }

        return [$lines, $needed];
    }

    /**
     * Проверка остатков и списание. Строки блокируются в порядке id,
     * чтобы параллельные заказы не «продали» одни и те же цветы и не возникла взаимоблокировка.
     *
     * @param  array<int,int>  $needed  flower_id => количество
     */
    private function writeOffFlowers(array $needed): void
    {
        if ($needed === []) {
            return;
        }

        ksort($needed);

        $flowers = Flower::whereIn('id', array_keys($needed))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $shortages = [];
        foreach ($needed as $flowerId => $quantity) {
            $flower = $flowers[$flowerId];
            if ($flower->stock_quantity < $quantity) {
                $shortages[] = [
                    'flower_id' => $flowerId,
                    'name' => $flower->name,
                    'required' => $quantity,
                    'available' => $flower->stock_quantity,
                ];
            }
        }

        if ($shortages !== []) {
            Log::warning('Заказ отклонён: недостаточно цветов', ['shortages' => $shortages]);

            $text = implode('; ', array_map(
                fn ($s) => "{$s['name']} — нужно {$s['required']}, в наличии {$s['available']}",
                $shortages
            ));

            throw new BusinessException('Недостаточно цветов на складе: '.$text, 409, ['shortages' => $shortages]);
        }

        foreach ($needed as $flowerId => $quantity) {
            $flowers[$flowerId]->decrement('stock_quantity', $quantity);
        }
    }

    /** Возврат цветов на склад при отмене заказа. */
    private function returnFlowers(Order $order): void
    {
        $order->load(['items.bouquet.flowers', 'items.customBouquet.items']);

        $returned = [];
        foreach ($order->items as $item) {
            $composition = $item->bouquet
                ? $item->bouquet->flowers->map(fn ($f) => [$f->id, $f->pivot->quantity])
                : $item->customBouquet->items->map(fn ($i) => [$i->flower_id, $i->quantity]);

            foreach ($composition as [$flowerId, $qty]) {
                $returned[$flowerId] = ($returned[$flowerId] ?? 0) + $qty * $item->quantity;
            }
        }

        ksort($returned);
        foreach ($returned as $flowerId => $qty) {
            Flower::whereKey($flowerId)->increment('stock_quantity', $qty);
        }
    }

    /**
     * Правила времени доставки.
     */
    private function assertDeliveryTime(array $delivery): void
    {
        $from = $delivery['time_from'];
        $to = $delivery['time_to'];
        $workFrom = config('shop.work_from');
        $workTo = config('shop.work_to');

        if ($from < $workFrom || $to > $workTo) {
            throw new BusinessException("Доставка выполняется с {$workFrom} до {$workTo}.", 422);
        }

        $start = Carbon::createFromFormat('Y-m-d H:i', $delivery['delivery_date'].' '.$from);
        $end = Carbon::createFromFormat('Y-m-d H:i', $delivery['delivery_date'].' '.$to);
        $minInterval = (int) config('shop.min_interval_minutes');

        if (($end->getTimestamp() - $start->getTimestamp()) / 60 < $minInterval) {
            throw new BusinessException("Интервал доставки должен быть не короче {$minInterval} минут.", 422);
        }

        $earliest = now()->addHours((int) config('shop.min_lead_hours'));
        if ($start->lt($earliest)) {
            throw new BusinessException(
                'Букету нужно время на сборку: ближайшая доставка — не раньше '.$earliest->format('d.m.Y H:i').'.',
                422
            );
        }
    }

    // =====================================================================
    // Жизненный цикл заказа
    // =====================================================================

    /** Отмена покупателем — только из статуса «Новый». */
    public function cancel(Order $order): Order
    {
        DB::transaction(function () use ($order) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== OrderStatus::New) {
                throw new BusinessException(
                    'Отменить можно только новый заказ. Текущий статус: «'.$locked->status->label().'». Свяжитесь с магазином.'
                );
            }

            $this->markCancelled($locked);
        });

        Log::info('Заказ отменён покупателем', ['order_id' => $order->id]);

        return $this->load($order->refresh());
    }

    /** Смена статуса флористом, курьером или администратором. */
    public function changeStatus(User $actor, Order $order, OrderStatus $to, ?string $comment = null): Order
    {
        DB::transaction(function () use ($actor, $order, $to, $comment) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->with('delivery')->firstOrFail();
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                $allowed = array_map(fn (OrderStatus $s) => '«'.$s->label().'»', $from->allowedTransitions());

                throw new BusinessException(
                    'Нельзя перевести заказ из статуса «'.$from->label().'» в «'.$to->label().'». '
                    .($allowed ? 'Допустимо: '.implode(', ', $allowed).'.' : 'Заказ завершён.')
                );
            }

            $delivery = $locked->delivery;

            if ($to === OrderStatus::Delivering) {
                if ($actor->role === UserRole::Courier) {
                    // курьер забирает заказ себе
                    $delivery->courier_id ??= $actor->id;
                } elseif ($delivery->courier_id === null) {
                    throw new BusinessException('Сначала назначьте курьера.');
                }
                $delivery->save();
            }

            if ($to === OrderStatus::Delivered) {
                $delivery->delivered_at = now();
                $delivery->courier_comment = $comment;
                $delivery->save();
            }

            if ($to === OrderStatus::Cancelled) {
                $this->markCancelled($locked);
            } else {
                $locked->update(['status' => $to]);
            }

            Log::info('Изменён статус заказа', [
                'order_id' => $locked->id,
                'from' => $from->value,
                'to' => $to->value,
                'user_id' => $actor->id,
            ]);
        });

        return $this->load($order->refresh());
    }

    /** Назначение курьера администратором. */
    public function assignCourier(Order $order, int $courierId): Order
    {
        if (! $order->status->isActive()) {
            throw new BusinessException('Заказ уже завершён — назначить курьера нельзя.');
        }

        $order->delivery()->update(['courier_id' => $courierId]);

        Log::info('Назначен курьер', ['order_id' => $order->id, 'courier_id' => $courierId]);

        return $this->load($order->refresh());
    }

    /** Изменение (или добавление) открытки — только пока заказ новый. */
    public function updatePostcard(Order $order, array $data): Order
    {
        if ($order->status !== OrderStatus::New) {
            throw new BusinessException('Открытку можно изменить только до начала сборки заказа.');
        }

        DB::transaction(function () use ($order, $data) {
            $isNew = ! $order->postcard()->exists();

            $order->postcard()->updateOrCreate([], [
                'text' => $data['text'],
                'design' => $data['design'] ?? PostcardDesign::Classic->value,
                'signature' => $data['signature'] ?? null,
            ]);

            if ($isNew) {
                $price = (float) config('shop.postcard_price');
                $order->update([
                    'postcard_price' => $price,
                    'total_price' => (float) $order->items_total + (float) $order->delivery_price + $price,
                ]);
            }
        });

        return $this->load($order->refresh());
    }

    private function markCancelled(Order $order): void
    {
        $this->returnFlowers($order);

        $order->update([
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
