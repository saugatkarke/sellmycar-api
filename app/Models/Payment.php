<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Payment extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'provider',
        'provider_payment_id',
        'total_amount',
        'currency',
        'status',

    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
