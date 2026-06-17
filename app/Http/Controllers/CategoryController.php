<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::all();

        return response()->json([
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:categories,name',
            'description' => 'nullable|string',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
        ]);

        return response()->json(
            [
                'message' => 'Category created successfully',
                'category' => $category,
            ],
            201,
        );
    }

    public function show(int $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'message' => 'No category found',
            ], 404);
        }

        return response()->json(
            [
                'category' => $category,
            ],
            200,
        );
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|unique:categories,name',
            'description' => 'sometimes|nullable|string',
        ]);

        $category = Category::find($id);
        if (!$category) {
            return response()->json([
                'message' => 'Category does not exist'
            ], 404);
        }
        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }
        $category->update($validated);

        return response()->json([
            'message' => 'The category has been updated sucessfully',
            'category' => $category
        ], 200);
    }

    public function destroy(int $id)
    {

        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'message' => 'Cannot delete, category not found'
            ], 404);
        }

        $category->delete();

        return response()->json(
            [
                'message' => 'Category deleted successfully!',
            ],
            200
        );
    }
}
