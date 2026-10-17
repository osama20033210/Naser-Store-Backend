<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryRequest;
use App\Http\Requests\ListRequest;
use App\Http\Resources\InventoryMovementResource;
use App\Http\Resources\ProductResource;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\InventoryService;

class InventoryController extends Controller
{
    public function movements(ListRequest $request)
    {
        $query = InventoryMovement::query()->with(['product', 'user']);
        if ($request->has('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        return InventoryMovementResource::collection($query->latest('id')->paginate($request->integer('per_page', 15)));
    }

    public function adjust(InventoryRequest $request, Product $product, InventoryService $inventory): ProductResource
    {
        if ($key = $request->validated('adjustment_key')) {
            return new ProductResource($inventory->adjustOnce($product->id, (int) $request->validated('quantity_change'), $request->validated('reason'), $request->user()->id, $key));
        }

        return new ProductResource($inventory->adjust($product->id, (int) $request->validated('quantity_change'), $request->validated('reason'), $request->user()->id));
    }
}
