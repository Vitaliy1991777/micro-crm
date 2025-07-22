<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Пока у нас нет системы прав и ролей, разрешаем всем
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Имя покупателя: обязательно, строка, макс. 255 символов
            'customer' => 'required|string|max:255',
            // ID склада: обязательно, целое число, должен существовать в таблице 'warehouses'
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            // Товары: обязательно, должен быть массивом
            'items' => 'required|array',
            // Каждый элемент в массиве 'items' должен содержать:
            // ID продукта: обязательно, целое число, должен существовать в таблице 'products'
            'items.*.product_id' => 'required|integer|exists:products,id',
            // Количество: обязательно, целое число, минимум 1
            'items.*.count' => 'required|integer|min:1',
        ];
    }
}