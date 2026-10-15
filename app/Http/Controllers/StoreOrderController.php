<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListRequest;
use App\Http\Requests\StoreCheckoutRequest;
use App\Http\Resources\StoreOrderResource;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StoreOrderController extends Controller
{
    public function index(ListRequest $request)
    {
        return StoreOrderResource::collection($request->user()->customer->orders()->latest('id')->paginate($request->integer('per_page', 15)));
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
