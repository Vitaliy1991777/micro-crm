<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request) // <-- Убрали строгие типы
    {
        return [
            'product_id' => $this->product_id,
            // Используем whenLoaded, чтобы избежать ошибок, если продукт не был загружен
            'product_name' => $this->whenLoaded('product', function() {
                return $this->product->name;
            }),
            'count' => $this->count,
        ];
    }
}