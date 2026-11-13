<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Occasion\StoreOccasionRequest;
use App\Http\Requests\Occasion\UpdateOccasionRequest;
use App\Http\Resources\OccasionResource;
use App\Models\Occasion;
use App\Services\OccasionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @group Поводы
 */
class OccasionController extends Controller
{
    public function __construct(private readonly OccasionService $occasions)
    {
    }

    /** GET /api/occasions. Доступ: все. */
    public function index(): AnonymousResourceCollection
    {
        return OccasionResource::collection($this->occasions->list());
    }

    public function show(Occasion $occasion): OccasionResource
    {
        return new OccasionResource($occasion->loadCount('bouquets'));
    }

    /** Доступ: admin. */
    public function store(StoreOccasionRequest $request): JsonResponse
    {
        return (new OccasionResource($this->occasions->create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    /** Доступ: admin. */
    public function update(UpdateOccasionRequest $request, Occasion $occasion): OccasionResource
    {
        return new OccasionResource($this->occasions->update($occasion, $request->validated()));
    }

    /** Доступ: admin. */
    public function destroy(Occasion $occasion): Response
    {
        $this->occasions->delete($occasion);

        return response()->noContent();
    }
}
