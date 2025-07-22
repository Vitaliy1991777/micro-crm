<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
                // Вызываем наши сидеры в правильном порядке
        $this->call([
            WarehouseSeeder::class,
            ProductSeeder::class,
            StockSeeder::class,
        ]);
    }
}
