<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); // unsigned big integer, AI, pk
            $table->string('customer');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            // Внешний ключ, ссылающийся на id в таблице warehouses
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};