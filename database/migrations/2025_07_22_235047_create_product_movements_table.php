<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_movements', function (Blueprint $table) {
            // Уникальный идентификатор движения
            $table->id();

            // Внешний ключ на товар, который перемещается
            $table->foreignId('product_id')->constrained('products');

            // Внешний ключ на склад, где происходит движение
            $table->foreignId('warehouse_id')->constrained('warehouses');

            // Внешний ключ на заказ, который стал причиной движения.
            // Может быть NULL, если движение не связано с заказом (например, ручная инвентаризация).
            $table->foreignId('order_id')->nullable()->constrained('orders');

            // Изменение количества. Положительное число - приход, отрицательное - расход.
            $table->integer('change');

            // Дата и время создания записи
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_movements');
    }
};