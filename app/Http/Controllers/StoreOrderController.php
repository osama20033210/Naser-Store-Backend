<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderListRequest;
use App\Http\Requests\StoreCheckoutRequest;
use App\Http\Resources\StoreOrderResource;
use App\Services\OrderService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StoreOrderController extends Controller
{
    public function index(StoreOrderListRequest $request)
    {
        $query = $request->user()->customer->orders();
        $search = ltrim(trim((string) $request->validated('q', '')), '#');
        if ($search !== '') {
            $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->whereRaw('CAST(number AS TEXT) ILIKE ?', ['%' . $search . '%']);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->validated('status'));
        }
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', CarbonImmutable::createFromFormat('!Y-m-d', $request->validated('date_from'), 'Asia/Damascus')->utc());
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<', CarbonImmutable::createFromFormat('!Y-m-d', $request->validated('date_to'), 'Asia/Damascus')->addDay()->utc());
        }

        return StoreOrderResource::collection($query->latest('id')->paginate($request->integer('per_page', 15))->withQueryString());
    }

    public function store(StoreCheckoutRequest $request, OrderService $orders)
    {
        return new StoreOrderResource($orders->createForCustomer($request->validated(), $request->user()));
    }

    public function show(Request $request, int $order)
    {
        $record = $request->user()->customer->orders()->with('items')->findOrFail($order);
        Gate::authorize('viewOwn', $record);

        return new StoreOrderResource($record);
    }

    public function cancel(Request $request, int $order, OrderService $orders)
    {
        $record = $request->user()->customer->orders()->findOrFail($order);
        Gate::authorize('cancelOwn', $record);

        return new StoreOrderResource($orders->cancelForCustomer($record->id, $request->user()));
    }
}
