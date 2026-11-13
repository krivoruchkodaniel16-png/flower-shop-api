<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Flower;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Агрегированные данные для администратора (отчёт за период).
 */
class StatisticsService
{
    public function summary(?string $dateFrom, ?string $dateTo): array
    {
        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : now()->subDays(30)->startOfDay();
        $to = $dateTo ? Carbon::parse($dateTo)->endOfDay() : now()->endOfDay();
        $period = [$from, $to];

        $cancelled = OrderStatus::Cancelled->value;
        $delivered = OrderStatus::Delivered->value;

        // Количество и сумма заказов по статусам (groupBy + selectRaw)
        $byStatus = Order::query()
            ->whereBetween('created_at', $period)
            ->select('status', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(total_price) as amount'))
            ->groupBy('status')
            ->get()
            ->map(fn (Order $row) => [
                'status' => $row->status->value,
                'label' => $row->status->label(),
                'orders_count' => (int) $row->orders_count,
                'amount' => round((float) $row->amount, 2),
            ])
            ->values();

        // Выручка — по доставленным заказам
        $revenue = (float) Order::where('status', $delivered)->whereBetween('created_at', $period)->sum('total_price');
        $activeOrders = Order::where('status', '!=', $cancelled)->whereBetween('created_at', $period);
        $ordersCount = (clone $activeOrders)->count();
        $averageCheck = (float) (clone $activeOrders)->avg('total_price');

        // Выручка по дням
        $revenueByDay = DB::table('orders')
            ->where('status', $delivered)
            ->whereBetween('created_at', $period)
            ->selectRaw('DATE(created_at) as day, SUM(total_price) as amount, COUNT(*) as orders_count')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($r) => ['day' => (string) $r->day, 'amount' => round((float) $r->amount, 2), 'orders_count' => (int) $r->orders_count]);

        // Самые популярные готовые букеты
        $topBouquets = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('bouquets', 'bouquets.id', '=', 'order_items.bouquet_id')
            ->where('orders.status', '!=', $cancelled)
            ->whereBetween('orders.created_at', $period)
            ->groupBy('bouquets.id', 'bouquets.name')
            ->selectRaw('bouquets.id, bouquets.name, SUM(order_items.quantity) as sold, SUM(order_items.quantity * order_items.unit_price) as revenue')
            ->orderByDesc('sold')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['id' => (int) $r->id, 'name' => $r->name, 'sold' => (int) $r->sold, 'revenue' => round((float) $r->revenue, 2)]);

        // Цветы, которые чаще всего выбирают в конструкторе
        $popularFlowers = DB::table('custom_bouquet_items')
            ->join('flowers', 'flowers.id', '=', 'custom_bouquet_items.flower_id')
            ->groupBy('flowers.id', 'flowers.name')
            ->selectRaw('flowers.id, flowers.name, SUM(custom_bouquet_items.quantity) as used')
            ->orderByDesc('used')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['id' => (int) $r->id, 'name' => $r->name, 'used' => (int) $r->used]);

        // Заканчивающиеся цветы
        $lowStock = Flower::where('stock_quantity', '<', (int) config('shop.low_stock_threshold'))
            ->orderBy('stock_quantity')
            ->get(['id', 'name', 'stock_quantity'])
            ->map(fn (Flower $f) => ['id' => $f->id, 'name' => $f->name, 'stock_quantity' => $f->stock_quantity]);

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'orders_count' => $ordersCount,
            'revenue' => round($revenue, 2),
            'average_check' => round($averageCheck, 2),
            'by_status' => $byStatus,
            'revenue_by_day' => $revenueByDay,
            'top_bouquets' => $topBouquets,
            'popular_constructor_flowers' => $popularFlowers,
            'low_stock' => $lowStock,
        ];
    }
}
