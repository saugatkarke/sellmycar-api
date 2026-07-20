<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'order_id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'created_at' => $this->created_at,
            'order_items' => $this->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_title' => $item->product_title,
                    'product_make' => $item->product_make,
                    'product_model' => $item->product_model,
                    'product_year' => $item->product_year,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                ];
            }),

        ];
    }
}
