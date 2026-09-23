<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'slug' => $item->product->slug,
                    'image' => $item->product->thumbnail()?->path
                        ? asset('storage/' . $item->product->thumbnail()->path)
                        : asset('images/placeholder-product.svg'),
                ],
                'variant' => $item->variant ? [
                    'id' => $item->variant->id,
                    'label' => $item->variant->attributeValues
                        ->map(fn ($value) => $value->value)
                        ->join('، '),
                ] : null,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->lineTotal(),
            ]),
            'items_count' => $this->itemsCount(),
            'total' => $this->total(),
        ];
    }
}
