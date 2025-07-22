<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Просто получаем все записи из таблицы складов
        $warehouses = Warehouse::all();
        
        // Laravel автоматически преобразует коллекцию в JSON-ответ
        return response()->json($warehouses);
    }
}