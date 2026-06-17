<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->get();

        return response()->json([
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'year' => 'required|integer',
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'mileage' => 'required|integer|min:0',
            'condition' => 'required|in:new,used',
            'transmission' => 'required|in:automatic,manual',
            'fuel_type' => 'required|in:petrol,diesel,hybrid,electric,phev',
            'color' => 'nullable|string|max:255',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $validated['slug'] = Str::slug($validated['title']);
        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product has been created successfully',
            'product' => $product,
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

        return response()->json([
            'product' => $product,
        ], 200);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'year' => 'sometimes|integer',
            'make' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:255',
            'mileage' => 'sometimes|integer|min:0',
            'condition' => 'sometimes|in:new,used',
            'transmission' => 'sometimes|in:automatic,manual',
            'fuel_type' => 'sometimes|in:petrol,diesel,hybrid,electric,phev',
            'color' => 'sometimes|string|max:255',
            'stock' => 'sometimes|integer|min:0',
            'image' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $product->update($validated);

        return response()->json([
            'message' => 'Product has been updated successfully!',
            'Product' => $product->fresh('category'),
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
