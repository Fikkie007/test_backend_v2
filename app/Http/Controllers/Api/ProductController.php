<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\File;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(ProductIndexRequest $request): JsonResponse
    {
        $query = Product::with(['files', 'createdBy', 'updatedBy']);
        $query->when($request->search, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"));
        $query->when($request->category, fn ($q, $category) => $q->where('category', $category));
        $products = $query->latest('id')->paginate($request->integer('limit', 10));

        return response()->json(['success' => true, 'code' => 200, 'message' => 'Products retrieved successfully', 'data' => ProductResource::collection($products->items()), 'meta' => ['current_page' => $products->currentPage(), 'last_page' => $products->lastPage(), 'per_page' => $products->perPage(), 'total' => $products->total()]]);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = DB::transaction(function () use ($request) {
            $product = Product::create($request->safe()->only(['title', 'price', 'description', 'category']));
            $this->replaceImages($product, $request->validated('images'));
            return $product->load(['files', 'createdBy', 'updatedBy']);
        });

        return response()->json(['success' => true, 'code' => 201, 'message' => 'Product created successfully', 'data' => new ProductResource($product)], 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['files', 'createdBy', 'updatedBy']);
        return response()->json(['success' => true, 'code' => 200, 'message' => 'Product retrieved successfully', 'data' => new ProductResource($product)]);
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $product = DB::transaction(function () use ($request, $product) {
            $product->update($request->safe()->only(['title', 'price', 'description', 'category']));
            if ($request->has('images')) $this->replaceImages($product, $request->validated('images'));
            return $product->load(['files', 'createdBy', 'updatedBy']);
        });

        return response()->json(['success' => true, 'code' => 200, 'message' => 'Product updated successfully', 'data' => new ProductResource($product)]);
    }

    public function destroy(Product $product): JsonResponse
    {
        DB::transaction(function () use ($product) {
            File::where('parent_table', 'products')->where('parent_id', $product->id)->delete();
            $product->delete();
        });
        return response()->json(null, 204);
    }

    private function replaceImages(Product $product, array $images): void
    {
        File::where('parent_table', 'products')->where('parent_id', $product->id)->delete();
        foreach ($images as $path) File::create(['parent_id' => $product->id, 'parent_table' => 'products', 'path' => $path]);
    }
}
