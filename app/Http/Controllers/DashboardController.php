<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductResource;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $counts = Order::query()->selectRaw('status, COUNT(*) AS count')->groupBy('status')->pluck('count', 'status');
        $trends = Order::query()->where('status', 'completed')->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(created_at) AS date, SUM(total_minor) AS revenue_minor')->groupByRaw('DATE(created_at)')->orderBy('date')->get();

        return response()->json(['data' => [
            'total_orders' => (int) $counts->sum(), 'revenue_minor' => (int) Order::where('status', 'completed')->sum('total_minor'),
            'pending_orders' => (int) ($counts['pending'] ?? 0), 'completed_orders' => (int) ($counts['completed'] ?? 0),
            'low_stock_count' => Product::whereColumn('stock', '<=', 'low_stock_threshold')->count(),
            'status_distribution' => $counts, 'revenue_trends' => $trends,
            'recent_orders' => OrderResource::collection(Order::with('customer')->latest('id')->limit(6)->get()),
            'low_stock_products' => ProductResource::collection(Product::with('category')->whereColumn('stock', '<=', 'low_stock_threshold')->orderBy('stock')->limit(6)->get()),
        ]]);
    }
}
