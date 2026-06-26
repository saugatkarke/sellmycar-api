<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductService
{
    public function create(array $data, $image = null)
    {

        $data['slug'] = Str::slug($data['title']);

        //checks if the same slug exists and return that existed product id

        if (Product::where('slug', $data['slug'])->first()) {
            return null;
        }

        if ($image) {
            $data['image'] = $image->store('products', 'public');
        }
        return Product::create($data);
    }
}
