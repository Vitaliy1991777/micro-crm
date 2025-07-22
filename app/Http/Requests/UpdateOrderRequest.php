<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
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
            // Поле 'customer' не является обязательным, но если оно есть, то должно быть строкой
            'customer' => 'sometimes|string|max:255',
            // 'items' тоже не обязателен, но если есть, должен быть массивом
            'items' => 'sometimes|array',
            // Если 'items' передан, то его дочерние поля обязательны
            'items.*.product_id' => 'required_with:items|integer|exists:products,id',
            'items.*.count' => 'required_with:items|integer|min:1',
        ];
    }
}