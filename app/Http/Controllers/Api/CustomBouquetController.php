<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomBouquet\CalculateCustomBouquetRequest;
use App\Http\Requests\CustomBouquet\StoreCustomBouquetRequest;
use App\Http\Requests\CustomBouquet\UpdateCustomBouquetRequest;
use App\Http\Resources\CustomBouquetResource;
use App\Models\CustomBouquet;
use App\Services\CustomBouquetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * @group Конструктор букетов
 */
class CustomBouquetController extends Controller
{
    public function __construct(private readonly CustomBouquetService $customBouquets)
    {
    }

    /**
     * Расчёт цены букета без сохранения. Доступ: все.
     *
     * POST /api/custom-bouquets/calculate {packaging_id, items:[{flower_id, quantity}]}
     * → 200 {data: {items, flowers_count, flowers_total, packaging, assembly_fee, total, is_valid, warnings}}
     */
    public function calculate(CalculateCustomBouquetRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->customBouquets->calculate($request->validated())]);
    }

    /** GET /api/custom-bouquets — мои букеты. Доступ: авторизованные. */
    public function index(Request $request): AnonymousResourceCollection
    {
        return CustomBouquetResource::collection($this->customBouquets->paginate($request->user()));
    }

    /** POST /api/custom-bouquets → 201 | 409 (нарушены правила сборки) | 422. */
    public function store(StoreCustomBouquetRequest $request): JsonResponse
    {
        $bouquet = $this->customBouquets->create($request->user(), $request->validated());

        return (new CustomBouquetResource($bouquet))->response()->setStatusCode(201);
    }

    /** GET /api/custom-bouquets/{id} → 200 | 403 (чужой) | 404. */
    public function show(CustomBouquet $customBouquet): CustomBouquetResource
    {
        Gate::authorize('view', $customBouquet);

        return new CustomBouquetResource($this->customBouquets->load($customBouquet));
    }

    /** PUT/PATCH /api/custom-bouquets/{id} → 200 | 403 | 409 (уже заказан / правила сборки). */
    public function update(UpdateCustomBouquetRequest $request, CustomBouquet $customBouquet): CustomBouquetResource
    {
        Gate::authorize('update', $customBouquet);

        return new CustomBouquetResource($this->customBouquets->update($customBouquet, $request->validated()));
    }

    /** DELETE /api/custom-bouquets/{id} → 204 | 403 | 409 (уже заказан). */
    public function destroy(CustomBouquet $customBouquet): Response
    {
        Gate::authorize('delete', $customBouquet);

        $this->customBouquets->delete($customBouquet);

        return response()->noContent();
    }
}
