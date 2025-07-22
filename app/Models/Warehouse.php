<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно массово присваивать.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Отключаем использование полей created_at и updated_at.
     *
     * @var bool
     */
    public $timestamps = false;
    
    /**
     * Отношение "многие ко многим" с продуктами через таблицу остатков 'stocks'.
     * Позволяет получить все товары на этом складе и их остатки.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'stocks')
            ->withPivot('stock'); // Также загружаем количество остатков
    }

    /**
     * Отношение "один ко многим" с заказами.
     * С одного склада может быть сделано много заказов.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}