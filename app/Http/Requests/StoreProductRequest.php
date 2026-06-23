<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
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
        ];
    }
}
