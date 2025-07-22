<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    use HasFactory;

    /**
    * Указываем имя таблицы, так как оно не соответствует стандарту
    * (ожидается 'stocks', что у нас и есть, но для ясности лучше указать).
    *
    * @var string
    */
    protected $table = 'stocks';

    /**
    * Указываем, что у модели нет автоинкрементного первичного ключа.
    *
    * @var bool
    */
    public $incrementing = false;

    /**
    * Отключаем использование полей created_at и updated_at.
    *
    * @var bool
    */
    public $timestamps = false;

    /**
    * Атрибуты, которые можно массово присваивать.
    *
    * @var array<int, string>
    */
    protected $fillable = [
    'product_id',
    'warehouse_id',
    'stock',
    ];

    /**
    * Отношение "принадлежит к" с продуктом.
    */
    public function product(): BelongsTo
    {
    return $this->belongsTo(Product::class);
    }

    /**
    * Отношение "принадлежит к" со складом.
    */
    public function warehouse(): BelongsTo
    {
    return $this->belongsTo(Warehouse::class);
    }
}