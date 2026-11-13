<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bouquet\BouquetIndexRequest;
use App\Http\Requests\Bouquet\StoreBouquetRequest;
use App\Http\Requests\Bouquet\UpdateBouquetRequest;
use App\Http\Requests\Bouquet\UploadBouquetImageRequest;
use App\Http\Resources\BouquetCollection;
use App\Http\Resources\BouquetResource;
use App\Models\Bouquet;
use App\Services\BouquetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * @group Каталог готовых букетов
 */
class BouquetController extends Controller
{
    public function __construct(private readonly BouquetService $bouquets)
    {
    }

    /**
     * Каталог с фильтрами, поиском, сортировкой и пагинацией. Доступ: все.
     *
     * GET /api/bouquets?occasion_id=&flower_ids[]=&color=&price_min=&price_max=&search=&sort=price|name|created_at&order=asc|desc&page=&per_page=
     */
    public function index(BouquetIndexRequest $request): BouquetCollection
    {
        $isAdmin = (bool) $request->user('sanctum')?->isAdmin();

        return new BouquetCollection($this->bouquets->paginate($request->validated(), $isAdmin));
    }

    /** GET /api/bouquets/{bouquet} → 200 | 404. Доступ: все. */
    public function show(Bouquet $bouquet): BouquetResource
    {
        return new BouquetResource($this->bouquets->load($bouquet));
    }

    /** POST /api/bouquets → 201 | 401 | 403 | 422. Доступ: admin. */
    public function store(StoreBouquetRequest $request): JsonResponse
    {
        $bouquet = $this->bouquets->create($request->validated());

        return (new BouquetResource($bouquet))->response()->setStatusCode(201);
    }

    /** PUT/PATCH /api/bouquets/{bouquet} → 200 | 404 | 422. Доступ: admin. */
    public function update(UpdateBouquetRequest $request, Bouquet $bouquet): BouquetResource
    {
        return new BouquetResource($this->bouquets->update($bouquet, $request->validated()));
    }

    /** DELETE /api/bouquets/{bouquet} → 204 | 404 | 409 (букет есть в заказах). Доступ: admin. */
    public function destroy(Bouquet $bouquet): Response
    {
        $this->bouquets->delete($bouquet);

        return response()->noContent();
    }

    /** POST /api/bouquets/{bouquet}/image (multipart, поле image) → 200 | 422. Доступ: admin. */
    public function uploadImage(UploadBouquetImageRequest $request, Bouquet $bouquet): BouquetResource
    {
        return new BouquetResource($this->bouquets->uploadImage($bouquet, $request->file('image')));
    }
}
