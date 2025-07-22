<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Получаем все созданные товары и склады
        $products = Product::all();
        $warehouses = Warehouse::all();

        // Проходим в цикле по каждому складу
        foreach ($warehouses as $warehouse) {
            // Проходим в цикле по каждому товару
            foreach ($products as $product) {
                // Создаем запись в таблице остатков
                Stock::create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'stock' => rand(0, 150) // Задаем случайный остаток от 0 до 150
                ]);
            }
        }
    }
}