<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('make')) {
            $query->where('make', $request->make);
        }
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('sort')) {
            if ($request->sort == 'price_asc') {
                $query->orderBy('price', 'asc');
            }
            if ($request->sort == 'price_desc') {
                $query->orderBy('price', 'desc');
            }
            if ($request->sort == 'newest') {
                $query->orderBy('created_at', 'desc');
            }
        }

        $products = $query->paginate(5);

        return  ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        $validated['slug'] = Str::slug($validated['title']);

        //checks if the same slug exists and return that existed product id
        $duplicateProduct = Product::where('slug', $validated['slug'])->first();

        if ($duplicateProduct) {
            return response()->json([
                'message' => 'Another similar product exists!',
                'product' => $duplicateProduct->id,
            ], 422);
        }

        if (($request->hasFile('image'))) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }
        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product has been created successfully',
            'product' => new ProductResource($product),
        ], 201);
    }

    public function show(int $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found/exist',
            ], 404);
        }

        return new ProductResource($product->load('category'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return response()->json([
            'message' => 'Product has been updated successfully!',
            'product' => $product->fresh('category'),
        ]);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Product has been deleted successfully!',
        ], 200);
    }
}
