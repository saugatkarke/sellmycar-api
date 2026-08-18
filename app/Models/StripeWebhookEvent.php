<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Payment;

class StripeWebhookEvent extends Model
{
    protected $fillable = [
        'event_id',
        'event_type',
        'received_at',
        'payment_id',
        'processing_status'
    ];

    protected $casts = [
        'received_at' => 'datetime'
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
