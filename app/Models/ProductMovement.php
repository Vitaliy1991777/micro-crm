<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMovement extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно массово присваивать.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'order_id',
        'change',
    ];

    /**
     * Движение товара принадлежит одному товару.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Движение товара принадлежит одному складу.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
    
    /**
     * Движение товара может принадлежать одному заказу.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}