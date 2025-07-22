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
        Schema::create('stocks', function (Blueprint $table) {
            // Внешний ключ, ссылающийся на id в таблице products.
            // При удалении продукта, связанные записи остатков также удалятся.
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            // Внешний ключ, ссылающийся на id в таблице warehouses.
            // При удалении склада, связанные записи остатков также удалятся.
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            
            // Количество товара на складе.
            $table->integer('stock');

            // Указываем, что пара (product_id, warehouse_id) является составным первичным ключом.
            // Это гарантирует, что для одного товара на одном складе будет только одна запись об остатке.
            $table->primary(['product_id', 'warehouse_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};