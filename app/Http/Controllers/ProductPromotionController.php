<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\ProductPromotionRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ProductPromotionController extends Controller
{
    public function update(ProductPromotionRequest $request, Product $product)
    {
        $data = $request->validated();
        DB::transaction(function () use ($data, $product) {
            $record = Product::query()->lockForUpdate()->findOrFail($product->id);
            abort_if($data['sale_price_minor'] !== null && $data['sale_price_minor'] >= $record->price_minor, 422, 'Sale price must be below the regular price.');
            $record->sale_price_minor = $data['sale_price_minor'];
            $record->sale_starts_at = $data['sale_price_minor'] === null ? null : CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['sale_starts_at'], 'Asia/Damascus')->utc();
            $record->sale_ends_at = $data['sale_price_minor'] === null ? null : CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['sale_ends_at'], 'Asia/Damascus')->utc();
            $record->save();
        }, 3);
        return response()->noContent();
    }
}
