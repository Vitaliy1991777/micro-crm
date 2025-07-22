<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // Эта логика будет применяться к ОДНОМУ продукту
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            // 'whenLoaded' - это хорошая практика: включать данные
            // только если они были предварительно загружены (как мы сделали через with('warehouses'))
            'stocks' => $this->whenLoaded('warehouses', function () {
                return $this->warehouses->map(function ($warehouse) {
                    return [
                        'warehouse_id' => $warehouse->id,
                        'warehouse_name' => $warehouse->name,
                        'stock' => $warehouse->pivot->stock,
                    ];
                });
            }),
        ];
    }
}