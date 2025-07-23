<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Stock;
use App\Services\StockService; 
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * @var StockService
     */
    protected StockService $stockService; // ИЗМЕНЕНИЕ: Свойство для хранения сервиса

    /**
     * OrderController constructor.
     * @param StockService $stockService
     */
    public function __construct(StockService $stockService) // ИЗМЕНЕНИЕ: Внедряем сервис через конструктор
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Order::query();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        $query->with(['items.product', 'warehouse']);
        $query->latest();
        $orders = $query->paginate($request->input('per_page', 15));

        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        try {
            $order = DB::transaction(function () use ($validatedData) {
                foreach ($validatedData['items'] as $item) {
                    $stock = Stock::where('warehouse_id', $validatedData['warehouse_id'])
                                  ->where('product_id', $item['product_id'])
                                  ->first();
                    if (!$stock || $stock->stock < $item['count']) {
                        throw new \Exception('Недостаточно товара на складе для продукта ID: ' . $item['product_id']);
                    }
                }

                $order = Order::create([
                    'customer' => $validatedData['customer'],
                    'warehouse_id' => $validatedData['warehouse_id'],
                    'status' => 'active',
                ]);

                foreach ($validatedData['items'] as $item) {
                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'count' => $item['count'],
                    ]);
                    // ИЗМЕНЕНИЕ: Используем сервис для списания и логирования
                    $this->stockService->debit($item['product_id'], $order->warehouse_id, $item['count'], $order->id);
                }
                return $order;
            });
            $order->load('items.product', 'warehouse');
            return response()->json(['message' => 'Заказ успешно создан', 'order' => $order], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }
    }

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        if ($order->status !== 'active') {
            return response()->json(['error' => 'Можно обновить только активный заказ. Текущий статус: ' . $order->status], 409);
        }
        $validatedData = $request->validated();
        try {
            DB::transaction(function () use ($order, $validatedData) {
                if (isset($validatedData['customer'])) {
                    $order->customer = $validatedData['customer'];
                    $order->save();
                }
                if (isset($validatedData['items'])) {
                    foreach ($order->items as $item) {
                        // ИЗМЕНЕНИЕ: Используем сервис для возврата и логирования
                        $this->stockService->credit($item->product_id, $order->warehouse_id, $item->count, $order->id);
                    }
                    foreach ($validatedData['items'] as $newItem) {
                        $stock = Stock::where('warehouse_id', $order->warehouse_id)
                                      ->where('product_id', $newItem['product_id'])
                                      ->first();
                        if (!$stock || $stock->stock < $newItem['count']) {
                            throw new \Exception('Недостаточно товара на складе для продукта ID: ' . $newItem['product_id']);
                        }
                    }
                    $order->items()->delete();
                    foreach ($validatedData['items'] as $newItem) {
                        $order->items()->create(['product_id' => $newItem['product_id'], 'count' => $newItem['count']]);
                        // ИЗМЕНЕНИЕ: Используем сервис для списания и логирования
                        $this->stockService->debit($newItem['product_id'], $order->warehouse_id, $newItem['count'], $order->id);
                    }
                }
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }
        $order->load(['items.product', 'warehouse']);
        return response()->json(new OrderResource($order));
    }

    public function complete(Order $order): JsonResponse
    {
        if ($order->status !== 'active') {
            return response()->json(['error' => 'Невозможно завершить заказ со статусом: ' . $order->status], 409);
        }
        $order->status = 'completed';
        $order->completed_at = Carbon::now();
        $order->save();
        return response()->json(new OrderResource($order));
    }

    public function cancel(Order $order): JsonResponse
    {
        if ($order->status !== 'active') {
            return response()->json(['error' => 'Невозможно отменить заказ со статусом: ' . $order->status], 409);
        }
        try {
            DB::transaction(function () use ($order) {
                $order->status = 'canceled';
                $order->save();
                foreach ($order->items as $item) {
                    // ИЗМЕНЕНИЕ: Используем сервис для возврата и логирования
                    $this->stockService->credit($item->product_id, $order->warehouse_id, $item->count, $order->id);
                }
            });
        } catch (\Exception $e) {
            return response()->json(['error' => 'Произошла ошибка при отмене заказа.'], 500);
        }
        return response()->json(new OrderResource($order));
    }

    public function resume(Order $order): JsonResponse
    {
        if ($order->status !== 'canceled') {
            return response()->json(['error' => 'Можно возобновить только отмененный заказ. Текущий статус: ' . $order->status], 409);
        }
        try {
            DB::transaction(function () use ($order) {
                foreach ($order->items as $item) {
                    $stock = Stock::where('warehouse_id', $order->warehouse_id)
                                  ->where('product_id', $item->product_id)
                                  ->first();
                    if (!$stock || $stock->stock < $item->count) {
                        throw new \Exception('Недостаточно товара на складе для возобновления заказа. Продукт ID: ' . $item->product_id);
                    }
                }
                foreach ($order->items as $item) {
                    // ИЗМЕНЕНИЕ: Используем сервис для списания и логирования
                    $this->stockService->debit($item->product_id, $order->warehouse_id, $item->count, $order->id);
                }
                $order->status = 'active';
                $order->save();
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }
        return response()->json(new OrderResource($order));
    }
}