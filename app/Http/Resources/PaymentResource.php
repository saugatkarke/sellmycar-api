<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'payment_id' => $this->id,
            'order_id' => $this->order_id,
            'provider' => $this->provider,
            'status' => $this->status,
            'currency' => $this->currency,
            'total_amount' => $this->total_amount,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
        ];
    }
}
