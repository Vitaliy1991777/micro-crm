<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest; 
use App\Models\Order;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB; // <-- 2. Импортируем фасад для работы с БД
use App\Http\Resources\OrderResource; // <-- Добавляем use
use Illuminate\Http\Request; 
use Illuminate\Http\Resources\Json\AnonymousResourceCollection; // <-- Добавляем use
use Carbon\Carbon; // <-- Добавляем use для работы с датой и временем
use App\Http\Requests\UpdateOrderRequest; // <-- Добавляем наш новый request

class OrderController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param StoreOrderRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        // 3. Получаем проверенные данные из запроса
        $validatedData = $request->validated();

        try {
            // 4. Оборачиваем всю логику в транзакцию
            $order = DB::transaction(function () use ($validatedData) {
                // ШАГ 1: Проверка остатков
                foreach ($validatedData['items'] as $item) {
                    $stock = Stock::where('warehouse_id', $validatedData['warehouse_id'])
                                  ->where('product_id', $item['product_id'])
                                  ->first();

                    // Если товара нет на складе или его не хватает
                    if (!$stock || $stock->stock < $item['count']) {
                        // Выбрасываем исключение, которое откатит транзакцию
                        throw new \Exception('Недостаточно товара на складе для продукта ID: ' . $item['product_id']);
                    }
                }

                // ШАГ 2: Создание заказа
                $order = Order::create([
                    'customer' => $validatedData['customer'],
                    'warehouse_id' => $validatedData['warehouse_id'],
                    'status' => 'active', // Новый заказ всегда "в работе"
                ]);

                // ШАГ 3: Создание позиций заказа и списание остатков
                foreach ($validatedData['items'] as $item) {
                    // Добавляем позицию в заказ
                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'count' => $item['count'],
                    ]);

                    // Списываем товар со склада
                    Stock::where('warehouse_id', $validatedData['warehouse_id'])
                         ->where('product_id', $item['product_id'])
                         ->decrement('stock', $item['count']);
                }

                return $order;
            });
            
            // Загружаем связанные данные для красивого ответа
            $order->load('items.product', 'warehouse');

            // 5. Если все прошло успешно, возвращаем ответ
            return response()->json([
                'message' => 'Заказ успешно создан',
                'order' => $order
            ], 201); // 201 Created - стандартный код для успешного создания

        } catch (\Exception $e) {
            // 6. Если что-то пошло не так (например, не хватило товара)
            return response()->json(['error' => $e->getMessage()], 409); // 409 Conflict
        }
    }

        /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Начинаем строить запрос к базе
        $query = Order::query();

        // Добавляем фильтр по статусу, если он есть в запросе
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        // Жадная загрузка связанных моделей для предотвращения проблемы N+1
        $query->with(['items.product', 'warehouse']);

        // Сортировка по дате создания, чтобы новые заказы были сверху
        $query->latest();

        // Получаем пагинированные данные. Количество на странице можно настраивать параметром per_page
        $orders = $query->paginate($request->input('per_page', 15));

        // Передаем коллекцию в наш ресурс для форматирования
        return OrderResource::collection($orders);
    }

        /**
     * Завершает указанный заказ.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function complete(Order $order): JsonResponse
    {
        // ШАГ 1: Проверяем, можно ли завершить заказ
        // Завершить можно только заказ в статусе "active"
        if ($order->status !== 'active') {
            return response()->json([
                'error' => 'Невозможно завершить заказ со статусом: ' . $order->status
            ], 409); // 409 Conflict - стандартный код для конфликта состояний
        }

        // ШАГ 2: Обновляем статус и дату завершения
        $order->status = 'completed';
        $order->completed_at = Carbon::now(); // Устанавливаем текущее время
        $order->save(); // Сохраняем изменения в базе данных

        // ШАГ 3: Возвращаем успешный ответ с обновленными данными заказа
        // Мы используем наш готовый OrderResource для красивого вывода
        return response()->json(new OrderResource($order));
    }

        /**
     * Отменяет указанный заказ.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Order $order): JsonResponse
    {
        // ШАГ 1: Проверяем, можно ли отменить заказ.
        // Отменить можно только активный заказ.
        if ($order->status !== 'active') {
            return response()->json([
                'error' => 'Невозможно отменить заказ со статусом: ' . $order->status
            ], 409); // 409 Conflict
        }

        try {
            // ШАГ 2: Используем транзакцию, так как меняем и заказ, и остатки.
            DB::transaction(function () use ($order) {
                // Изменяем статус заказа
                $order->status = 'canceled';
                $order->save();

                // Проходим по всем позициям в заказе, чтобы вернуть их на склад
                foreach ($order->items as $item) {
                    Stock::where('warehouse_id', $order->warehouse_id)
                         ->where('product_id', $item->product_id)
                         ->increment('stock', $item->count); // Возвращаем товары
                }
            });

        } catch (\Exception $e) {
            // Если что-то пошло не так во время транзакции
            return response()->json(['error' => 'Произошла ошибка при отмене заказа.'], 500);
        }

        // ШАГ 3: Возвращаем успешный ответ с обновленными данными
        return response()->json(new OrderResource($order));
    }

        /**
     * Возобновляет отмененный заказ.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function resume(Order $order): JsonResponse
    {
        // ШАГ 1: Проверяем, что заказ был отменен
        if ($order->status !== 'canceled') {
            return response()->json([
                'error' => 'Можно возобновить только отмененный заказ. Текущий статус: ' . $order->status
            ], 409);
        }
        
        try {
            // ШАГ 2: Используем транзакцию для безопасности
            DB::transaction(function () use ($order) {
                
                // ШАГ 2.1: Проверяем наличие КАЖДОГО товара из заказа на складе
                foreach ($order->items as $item) {
                    $stock = Stock::where('warehouse_id', $order->warehouse_id)
                                  ->where('product_id', $item->product_id)
                                  ->first();

                    // Если товара нет на складе или его не хватает
                    if (!$stock || $stock->stock < $item->count) {
                        // Выбрасываем исключение, чтобы откатить транзакцию
                        throw new \Exception('Недостаточно товара на складе для возобновления заказа. Продукт ID: ' . $item->product_id);
                    }
                }

                // ШАГ 2.2: Если все проверки пройдены, списываем товары
                foreach ($order->items as $item) {
                    Stock::where('warehouse_id', $order->warehouse_id)
                         ->where('product_id', $item->product_id)
                         ->decrement('stock', $item->count);
                }

                // ШАГ 2.3: Меняем статус заказа обратно на "active"
                $order->status = 'active';
                $order->save();
            });

        } catch (\Exception $e) {
            // Если была ошибка (не хватило товара), возвращаем ее текст
            return response()->json(['error' => $e->getMessage()], 409); // 409 Conflict
        }
        
        // ШАГ 3: Возвращаем успешный ответ
        return response()->json(new OrderResource($order));
    }

        /**
     * Обновляет указанный заказ.
     *
     * @param  \App\Http\Requests\UpdateOrderRequest  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        // ШАГ 1: Проверяем, можно ли обновить заказ.
        if ($order->status !== 'active') {
            return response()->json([
                'error' => 'Можно обновить только активный заказ. Текущий статус: ' . $order->status
            ], 409);
        }

        $validatedData = $request->validated();

        try {
            DB::transaction(function () use ($order, $validatedData) {
                // Обновляем имя клиента, если оно было передано
                if (isset($validatedData['customer'])) {
                    $order->customer = $validatedData['customer'];
                    $order->save();
                }

                // Обновляем товары, если они были переданы
                if (isset($validatedData['items'])) {
                    // 1. Возвращаем старые товары на склад
                    foreach ($order->items as $item) {
                        Stock::where('warehouse_id', $order->warehouse_id)
                             ->where('product_id', $item->product_id)
                             ->increment('stock', $item->count);
                    }

                    // 2. Проверяем наличие новых товаров на складе
                    foreach ($validatedData['items'] as $newItem) {
                        $stock = Stock::where('warehouse_id', $order->warehouse_id)
                                      ->where('product_id', $newItem['product_id'])
                                      ->first();
                        if (!$stock || $stock->stock < $newItem['count']) {
                            throw new \Exception('Недостаточно товара на складе для продукта ID: ' . $newItem['product_id']);
                        }
                    }

                    // 3. Удаляем старые позиции заказа
                    $order->items()->delete();

                    // 4. Создаем новые позиции заказа и списываем товары со склада
                    foreach ($validatedData['items'] as $newItem) {
                        $order->items()->create([
                            'product_id' => $newItem['product_id'],
                            'count' => $newItem['count'],
                        ]);
                        Stock::where('warehouse_id', $order->warehouse_id)
                             ->where('product_id', $newItem['product_id'])
                             ->decrement('stock', $newItem['count']);
                    }
                }
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }

        // Загружаем обновленные данные для ответа
        $order->load(['items.product', 'warehouse']);

        return response()->json(new OrderResource($order));
    }
}