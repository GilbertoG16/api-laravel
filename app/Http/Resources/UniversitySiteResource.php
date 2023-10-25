<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UniversitySiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $firstImage = $this->images->first();
        
        return [
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category->name,
            'image' => $firstImage ? $firstImage->image_url : null,
        ];
    }
}
