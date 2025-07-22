<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    /**
    * Атрибуты, которые можно массово присваивать.
    *
    * @var array<int, string>
    */
    protected $fillable = [
    'order_id',
    'product_id',
    'count',
    ];

    /**
    * Отключаем использование полей created_at и updated_at.
    *
    * @var bool
    */
    public $timestamps = false;

    /**
    * Отношение "принадлежит к" с заказом.
    */
    public function order(): BelongsTo
    {
    return $this->belongsTo(Order::class);
    }

    /**
    * Отношение "принадлежит к" с продуктом.
    */
    public function product(): BelongsTo
    {
    return $this->belongsTo(Product::class);
    }
}