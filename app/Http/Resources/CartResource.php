<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'title' => $item->product->title,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->unit_price * $item->quantity,
                ];
            }),
            'total' => $this->items->sum(
                fn($item) =>
                $item->unit_price * $item->quantity
            ),
        ];
    }
}
