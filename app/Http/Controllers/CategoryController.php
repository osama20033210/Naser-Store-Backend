<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Requests\ListRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function index(ListRequest $request)
    {
        $query = Category::query()->withCount('products');
        if ($q = $request->validated('q')) {
            $query->where('name', 'ilike', '%'.$q.'%');
        }

        return CategoryResource::collection($query->orderBy('name')->paginate($request->integer('per_page', 15)));
    }

    public function store(CategoryRequest $request): CategoryResource
    {
        return new CategoryResource(Category::create($request->validated()));
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->loadCount('products'));
    }

    public function update(CategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return new CategoryResource($category);
    }

    public function destroy(Category $category): Response
    {
        abort_if($category->products()->exists(), 409, 'Category contains products.');
        $category->delete();

        return response()->noContent();
    }
}
