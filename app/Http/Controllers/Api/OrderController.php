<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AssignCourierRequest;
use App\Http\Requests\Order\OrderIndexRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Requests\Order\UpdatePostcardRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * @group Заказы
 */
class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    /**
     * Список заказов (зависит от роли): покупатель — свои, флорист — очередь сборки,
     * курьер — свободные и свои доставки, администратор — все.
     *
     * GET /api/orders?status=&date_from=&date_to=&search=&sort=created_at|total_price|delivery_date|id&order=&page=&per_page=
     */
    public function index(OrderIndexRequest $request): AnonymousResourceCollection
    {
        return OrderResource::collection($this->orders->paginate($request->user(), $request->validated()));
    }

    /**
     * Оформление заказа (ключевой сценарий). Доступ: авторизованные.
     *
     * POST /api/orders → 201 | 403 (чужой индивидуальный букет) | 409 (нет цветов на складе,
     * букет снят с продажи) | 422 (валидация, время доставки)
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orders->create($request->user(), $request->validated());

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    /** GET /api/orders/{order} → 200 | 403 | 404. */
    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return new OrderResource($this->orders->load($order));
    }

    /** POST /api/orders/{order}/cancel → 200 | 403 (чужой) | 409 (уже собирается). Доступ: владелец. */
    public function cancel(Order $order): OrderResource
    {
        Gate::authorize('cancel', $order);

        return new OrderResource($this->orders->cancel($order));
    }

    /**
     * PATCH /api/orders/{order}/status {status, comment?} → 200 | 403 | 409 (недопустимый переход).
     * Доступ: florist (assembling, ready), courier (delivering, delivered), admin (любой допустимый).
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): OrderResource
    {
        $status = OrderStatus::from($request->validated('status'));

        Gate::authorize('view', $order);
        Gate::authorize('updateStatus', [$order, $status]);

        return new OrderResource(
            $this->orders->changeStatus($request->user(), $order, $status, $request->validated('comment'))
        );
    }

    /** PATCH /api/orders/{order}/courier {courier_id} → 200 | 409. Доступ: admin. */
    public function assignCourier(AssignCourierRequest $request, Order $order): OrderResource
    {
        Gate::authorize('assignCourier', $order);

        return new OrderResource($this->orders->assignCourier($order, (int) $request->validated('courier_id')));
    }

    /** PUT /api/orders/{order}/postcard {text, design?, signature?} → 200 | 403 | 409. Доступ: владелец. */
    public function updatePostcard(UpdatePostcardRequest $request, Order $order): OrderResource
    {
        Gate::authorize('updatePostcard', $order);

        return new OrderResource($this->orders->updatePostcard($order, $request->validated()));
    }
}
