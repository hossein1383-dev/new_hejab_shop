<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => asset('storage/' . $this->path),
            'alt' => $this->alt,
            'is_thumbnail' => $this->is_thumbnail,
        ];
    }
}
