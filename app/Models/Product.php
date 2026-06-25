<?php

namespace App\Models;

use App\Filters\ProductFilter;
use Illuminate\Database\Eloquent\Model;


class Product extends Model
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     */
    public function scopeFilter($query, $request)
    {
        return (new ProductFilter)->apply($query, $request);
    }
    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'description',
        'price',
        'year',
        'make',
        'model',
        'mileage',
        'condition',
        'transmission',
        'fuel_type',
        'color',
        'stock',
        'image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'mileage' => 'integer',
        'stock' => 'integer',
        'year' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
