<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ] : null),
            'price' => $this->price,
            'compare_price' => $this->compare_price,
            'has_discount' => $this->hasDiscount(),
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'is_new' => $this->is_new,
            'is_bestseller' => $this->is_bestseller,
            'thumbnail' => $this->whenLoaded('images', fn () => $this->thumbnail()?->path ? asset('storage/' . $this->thumbnail()->path) : null),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')),
            'created_at' => $this->created_at,
        ];
    }
}
