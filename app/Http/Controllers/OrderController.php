<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\ListRequest;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\PaymentRequest;
use App\Http\Requests\StatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function payment(PaymentRequest $request, Order $order, OrderService $orders): OrderResource
    {
        Gate::authorize('recordPayment', $order);

        return new OrderResource($orders->recordCashPayment($order->id, $request->user()->id));
    }

    public function index(ListRequest $request)
    {
        Gate::authorize('viewAny', Order::class);
        $query = Order::query()->with('customer');
        if ($q = $request->validated('q')) {
            $query->where(fn ($query) => $query->whereRaw('CAST(number AS TEXT) ILIKE ?', ['%'.$q.'%'])->orWhereHas('customer', fn ($customer) => $customer->where('name', 'ilike', '%'.$q.'%')));
        }
        if ($request->has('status')) {
            $query->where('status', $request->validated('status'));
        }
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }
        $sort = $request->validated('sort', 'created_at');
        if (! in_array($sort, ['created_at', 'total_minor'], true)) {
            $sort = 'created_at';
        }

        return OrderResource::collection($query->orderBy($sort, $request->validated('direction', 'desc'))->orderByDesc('id')->paginate($request->integer('per_page', 15)));
    }

    public function store(OrderRequest $request, OrderService $orders): OrderResource
    {
        Gate::authorize('create', Order::class);

        return new OrderResource($orders->create($request->validated(), $request->user()->id));
    }

    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return new OrderResource($order->load(['customer', 'items']));
    }

    public function status(StatusRequest $request, Order $order, OrderService $orders): OrderResource
    {
        Gate::authorize('updateStatus', $order);

        return new OrderResource($orders->transition($order->id, OrderStatus::from($request->validated('status')), $request->user()->id));
    }

    public function cancel(Request $request, Order $order, OrderService $orders): OrderResource
    {
        Gate::authorize('cancel', $order);

        return new OrderResource($orders->transition($order->id, OrderStatus::Cancelled, $request->user()->id));
    }
}
