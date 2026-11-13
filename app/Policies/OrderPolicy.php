<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Доступ к заказам:
 *  - покупатель — только к своим заказам;
 *  - флорист — к заказам в очереди на сборку (new, assembling, ready);
 *  - курьер — к готовым неназначенным заказам и к своим доставкам;
 *  - администратор — ко всем.
 */
class OrderPolicy
{
    /** Администратор может всё — проверка выполняется до остальных методов. */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Order $order): Response
    {
        $order->loadMissing('delivery');

        $allowed = match ($user->role) {
            UserRole::Customer => $order->user_id === $user->id,
            UserRole::Florist => in_array($order->status, [OrderStatus::New, OrderStatus::Assembling, OrderStatus::Ready], true),
            UserRole::Courier => $order->delivery?->courier_id === $user->id
                || ($order->status === OrderStatus::Ready && $order->delivery?->courier_id === null),
            default => false,
        };

        return $allowed ? Response::allow() : Response::deny('Нет доступа к этому заказу');
    }

    /** Отменить заказ может только сам покупатель (или администратор). */
    public function cancel(User $user, Order $order): Response
    {
        return $order->user_id === $user->id
            ? Response::allow()
            : Response::deny('Отменить можно только свой заказ');
    }

    /** Изменить открытку может только покупатель (или администратор). */
    public function updatePostcard(User $user, Order $order): Response
    {
        return $order->user_id === $user->id
            ? Response::allow()
            : Response::deny('Изменить открытку можно только в своём заказе');
    }

    /** Смена статуса: роль должна иметь право на целевой статус. */
    public function updateStatus(User $user, Order $order, OrderStatus $to): Response
    {
        if (! in_array($user->role, $to->rolesAllowedToSet(), true)) {
            return Response::deny('Роль «'.$user->role->label().'» не может переводить заказ в статус «'.$to->label().'»');
        }

        if ($user->role === UserRole::Courier) {
            $order->loadMissing('delivery');
            $courierId = $order->delivery?->courier_id;

            // Курьер берёт свободный заказ или работает только со своим
            if ($courierId !== null && $courierId !== $user->id) {
                return Response::deny('Заказ назначен другому курьеру');
            }
            if ($to === OrderStatus::Delivered && $courierId !== $user->id) {
                return Response::deny('Завершить можно только свою доставку');
            }
        }

        return Response::allow();
    }

    /** Назначение курьера — только администратор (обрабатывается в before). */
    public function assignCourier(User $user, Order $order): bool
    {
        return false;
    }
}
