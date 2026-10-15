<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Http\Requests\ListRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    public function index(ListRequest $request)
    {
        $query = Customer::query()->withCount('orders');
        if ($q = $request->validated('q')) {
            $query->where(fn ($query) => $query->where('name', 'ilike', '%'.$q.'%')->orWhere('email', 'ilike', '%'.$q.'%'));
        }

        return CustomerResource::collection($query->orderBy('name')->paginate($request->integer('per_page', 15)));
    }

    public function store(CustomerRequest $request): CustomerResource
    {
        return new CustomerResource(Customer::create($request->validated()));
    }

    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource($customer->loadCount('orders'));
    }

    public function update(CustomerRequest $request, Customer $customer): CustomerResource
    {
        $customer->update($request->validated());

        return new CustomerResource($customer);
    }

    public function destroy(Customer $customer): Response
    {
        abort_if($customer->user_id !== null, 409, 'Customer is linked to a registered account.');
        abort_if($customer->orders()->exists(), 409, 'Customer has order history.');
        $customer->delete();

        return response()->noContent();
    }

    public function orders(ListRequest $request, Customer $customer)
    {
        return OrderResource::collection($customer->orders()->with('customer')->latest()->paginate($request->integer('per_page', 15)));
    }
}
