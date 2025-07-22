<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource; // 1. Импортируем наш новый ресурс
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * Отображает постраничный список ресурсов.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        // 2. Получаем товары с их складами, используя пагинацию
        $products = Product::with('warehouses')->paginate(15);
        
        // 3. Передаем коллекцию в наш ресурс.
        // Laravel сам позаботится о правильном форматировании и добавит мета-данные пагинации.
        return ProductResource::collection($products);
    }
}