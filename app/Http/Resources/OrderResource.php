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
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'customer' => $this->customer,
            'status' => $this->status,
            'created_at' => $this->created_at->toDateTimeString(), // Форматируем дату
            'completed_at' => $this->completed_at ? $this->completed_at->toDateTimeString() : null, // Форматируем, если дата есть
            
            // Включаем информацию о складе, если она была загружена
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            
            // Включаем список позиций заказа, если они были загружены
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}