<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

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


        $products = $query->paginate(10);

        return response()->json([
            'message' => 'Products fetched successfully!',
            'data' => $products,
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
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'is_active' => 'boolean',
        ]);

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
            'image' => 'sometimes|image|mimes:jpeg,jpg,png,webp|max:2048',
            'is_active' => 'sometimes|boolean',
        ]);

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
