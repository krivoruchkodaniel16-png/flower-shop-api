<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Flower\FlowerIndexRequest;
use App\Http\Requests\Flower\StoreFlowerRequest;
use App\Http\Requests\Flower\UpdateFlowerRequest;
use App\Http\Requests\Flower\UpdateStockRequest;
use App\Http\Resources\FlowerResource;
use App\Models\Flower;
use App\Services\FlowerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @group Цветы и склад
 */
class FlowerController extends Controller
{
    public function __construct(private readonly FlowerService $flowers)
    {
    }

    /** GET /api/flowers?search=&color=&in_stock=1&sort=&order=&per_page= Доступ: все. */
    public function index(FlowerIndexRequest $request): AnonymousResourceCollection
    {
        $isStaff = (bool) $request->user('sanctum')?->hasRole(UserRole::Admin, UserRole::Florist);

        return FlowerResource::collection($this->flowers->paginate($request->validated(), $isStaff));
    }

    /** GET /api/flowers/{flower} Доступ: все. */
    public function show(Flower $flower): FlowerResource
    {
        return new FlowerResource($flower->loadCount('bouquets'));
    }

    /** POST /api/flowers → 201. Доступ: admin. */
    public function store(StoreFlowerRequest $request): JsonResponse
    {
        return (new FlowerResource($this->flowers->create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    /** PUT/PATCH /api/flowers/{flower}. Доступ: admin. */
    public function update(UpdateFlowerRequest $request, Flower $flower): FlowerResource
    {
        return new FlowerResource($this->flowers->update($flower, $request->validated()));
    }

    /** DELETE /api/flowers/{flower} → 204 | 409 (цветок входит в букеты). Доступ: admin. */
    public function destroy(Flower $flower): Response
    {
        $this->flowers->delete($flower);

        return response()->noContent();
    }

    /** PATCH /api/flowers/{flower}/stock {delta, reason} → 200 | 409 (остаток < 0). Доступ: admin, florist. */
    public function updateStock(UpdateStockRequest $request, Flower $flower): FlowerResource
    {
        $flower = $this->flowers->changeStock(
            $flower,
            (int) $request->validated('delta'),
            $request->validated('reason'),
            $request->user()
        );

        return new FlowerResource($flower);
    }
}
