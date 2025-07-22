<?php

namespace App\Services;

use App\Models\ProductMovement;
use App\Models\Stock;

class StockService
{
    /**
     * Записывает приход товара на склад и логирует движение.
     *
     * @param int $productId ID товара
     * @param int $warehouseId ID склада
     * @param int $quantity Количество для добавления
     * @param int|null $orderId ID заказа, вызвавшего движение
     * @return void
     */
    public function credit(int $productId, int $warehouseId, int $quantity, ?int $orderId = null): void
    {
        // 1. Возвращаем товар на склад
        Stock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->increment('stock', $quantity);

        // 2. Логируем движение (приход)
        ProductMovement::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'order_id' => $orderId,
            'change' => $quantity, // Положительное значение для прихода
        ]);
    }

    /**
     * Списывает товар со склада и логирует движение.
     *
     * @param int $productId ID товара
     * @param int $warehouseId ID склада
     * @param int $quantity Количество для списания
     * @param int|null $orderId ID заказа, вызвавшего движение
     * @return void
     */
    public function debit(int $productId, int $warehouseId, int $quantity, ?int $orderId = null): void
    {
        // 1. Списываем товар со склада
        Stock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->decrement('stock', $quantity);

        // 2. Логируем движение (расход)
        ProductMovement::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'order_id' => $orderId,
            'change' => -$quantity, // Отрицательное значение для расхода
        ]);
    }
}