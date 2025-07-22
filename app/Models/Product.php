<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно массово присваивать.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'price',
    ];

    /**
     * Отключаем использование полей created_at и updated_at,
     * так как их нет в таблице 'products'.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Отношение "один ко многим" с позициями в заказах.
     * Один продукт может быть во многих позициях заказов.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Отношение "многие ко многим" со складами через таблицу остатков 'stocks'.
     * Позволяет получить все склады, на которых есть этот товар, и их остатки.
     */
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'stocks')
            ->withPivot('stock'); // Также загружаем количество остатков
    }
}