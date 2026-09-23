<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'thumbnail' => $this->product->images->first()?->path
                    ? asset('storage/' . $this->product->images->first()->path)
                    : null,
            ],
            'variant' => $this->variant ? [
                'id' => $this->variant->id,
                'sku' => $this->variant->sku,
            ] : null,
            'quantity' => $this->quantity,
            'unit_price' => $this->currentUnitPrice(),
            'line_total' => $this->currentUnitPrice() * $this->quantity,
            'price_changed' => $this->priceHasChanged(),
        ];
    }
}
