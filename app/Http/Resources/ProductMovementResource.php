<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductMovementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'change' => $this->change,
            'created_at' => $this->created_at->toDateTimeString(),
            
            // Включаем информацию о товаре, если он был загружен
            'product' => new ProductResource($this->whenLoaded('product')),
            
            // Включаем информацию о складе
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            
            // Включаем ID заказа, если он есть
            'order_id' => $this->when($this->order_id !== null, $this->order_id),
        ];
    }
}