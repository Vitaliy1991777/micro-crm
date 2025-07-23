<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductMovementResource;
use App\Models\ProductMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductMovementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Валидация входных данных для фильтров
        $request->validate([
            'warehouse_id' => 'sometimes|integer|exists:warehouses,id',
            'product_id' => 'sometimes|integer|exists:products,id',
            'date_from' => 'sometimes|date_format:Y-m-d',
            'date_to' => 'sometimes|date_format:Y-m-d|after_or_equal:date_from',
        ]);

        // Начинаем строить запрос
        $query = ProductMovement::query();

        // Динамически добавляем фильтры, если они были переданы в запросе
        $query->when($request->warehouse_id, function ($q, $warehouseId) {
            return $q->where('warehouse_id', $warehouseId);
        });

        $query->when($request->product_id, function ($q, $productId) {
            return $q->where('product_id', $productId);
        });

        $query->when($request->date_from, function ($q, $dateFrom) {
            return $q->whereDate('created_at', '>=', $dateFrom);
        });
        
        $query->when($request->date_to, function ($q, $dateTo) {
            return $q->whereDate('created_at', '<=', $dateTo);
        });

        // Подгружаем связанные модели для отображения в ресурсе
        $query->with(['product', 'warehouse']);

        // Сортируем по дате, чтобы последние движения были сверху
        $query->latest();

        // Выполняем пагинацию
        $movements = $query->paginate($request->input('per_page', 15));

        return ProductMovementResource::collection($movements);
    }
}