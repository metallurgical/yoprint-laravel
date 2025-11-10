<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'unique_key' => $this->unique_key,
            'title' => $this->product_title,
            'description' => $this->product_description,
            'style' => $this->style,
            'mainframe_color' => $this->sanmar_mainframe_color,
            'size' => $this->size,
            'color' => $this->color_name,
            'price' => $this->piece_price,
        ];
    }
}

