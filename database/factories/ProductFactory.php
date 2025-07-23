<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $adjectives = [
            'Деревянный', 'Металлический', 'Пластиковый', 'Стеклянный', 'Большой', 
            'Маленький', 'Красный', 'Синий', 'Зеленый', 'Современный', 'Классический'
        ];
        
        $nouns = [
            'стол', 'стул', 'шкаф', 'комод', 'диван', 
            'стеллаж', 'ящик', 'кронштейн', 'светильник', 'крючок'
        ];

        return [
            // Выбираем случайное прилагательное и случайное существительное
            'name' => $this->faker->randomElement($adjectives) . ' ' . $this->faker->randomElement($nouns),
            
            'price' => $this->faker->randomFloat(2, 100, 5000),
        ];
    }
}