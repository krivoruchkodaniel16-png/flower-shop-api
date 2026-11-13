<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatisticsRequest;
use App\Services\StatisticsService;
use Illuminate\Http\JsonResponse;

/**
 * @group Отчёты (администратор)
 */
class StatisticsController extends Controller
{
    public function __construct(private readonly StatisticsService $statistics)
    {
    }

    /**
     * Сводка за период: заказы по статусам, выручка, средний чек, выручка по дням,
     * топ букетов, популярные цветы конструктора, заканчивающиеся цветы.
     *
     * GET /api/stats?date_from=2026-11-01&date_to=2026-11-30. Доступ: admin.
     */
    public function __invoke(StatisticsRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->statistics->summary($request->validated('date_from'), $request->validated('date_to')),
        ]);
    }
}
