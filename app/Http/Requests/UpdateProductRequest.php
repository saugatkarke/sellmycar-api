<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
        ];
    }
}
