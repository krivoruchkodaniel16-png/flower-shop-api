<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Packaging\StorePackagingRequest;
use App\Http\Requests\Packaging\UpdatePackagingRequest;
use App\Http\Resources\PackagingResource;
use App\Models\Packaging;
use App\Services\PackagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @group Упаковка
 */
class PackagingController extends Controller
{
    public function __construct(private readonly PackagingService $packagings)
    {
    }

    /** GET /api/packagings[?include_inactive=1 — только admin]. Доступ: все. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $withInactive = $request->boolean('include_inactive') && (bool) $request->user('sanctum')?->isAdmin();

        return PackagingResource::collection($this->packagings->list($withInactive));
    }

    public function show(Packaging $packaging): PackagingResource
    {
        return new PackagingResource($packaging);
    }

    /** Доступ: admin. */
    public function store(StorePackagingRequest $request): JsonResponse
    {
        return (new PackagingResource($this->packagings->create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    /** Доступ: admin. */
    public function update(UpdatePackagingRequest $request, Packaging $packaging): PackagingResource
    {
        return new PackagingResource($this->packagings->update($packaging, $request->validated()));
    }

    /** 204 | 409 (упаковка используется в букетах). Доступ: admin. */
    public function destroy(Packaging $packaging): Response
    {
        $this->packagings->delete($packaging);

        return response()->noContent();
    }
}
