<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
    * Константа для поля updated_at. Устанавливаем в null,
    * чтобы Laravel не пытался автоматически обновлять это поле,
    * т.к. его нет в нашей таблице.
    */
    const UPDATED_AT = null;

    /**
    * Атрибуты, которые можно массово присваивать.
    *
    * @var array<int, string>
    */
    protected $fillable = [
    'customer',
    'warehouse_id',
    'status',
    'completed_at',
    ];

    /**
    * Отношение "один ко многим" с позициями заказа.
    * Один заказ состоит из многих позиций.
    */
    public function items(): HasMany
    {
    return $this->hasMany(OrderItem::class);
    }

    /**
    * Отношение "принадлежит к" со складом.
    * Каждый заказ принадлежит одному складу.
    */
    public function warehouse(): BelongsTo
    {
    return $this->belongsTo(Warehouse::class);
    }
}