<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderPageController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Отображает страницу со списком заказов и формой создания.
     */
    public function index()
    {
        // Получаем все данные, необходимые для страницы
        $orders = Order::with(['items.product', 'warehouse'])->latest()->paginate(10);
        $warehouses = Warehouse::all();
        $products = Product::query()->take(5)->get();

        // Возвращаем HTML-представление и передаем в него данные
        return view('orders.index', [
            'orders' => $orders,
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }

    /**
     * Обрабатывает создание нового заказа из формы.
     */
    public function store(Request $request)
    {
        // Валидация (упрощенная)
        $validatedData = $request->validate([
            'customer' => 'required|string|max:255',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'products' => 'required|array',
            'products.*.id' => 'required|integer|exists:products,id',
            'products.*.count' => 'nullable|integer|min:1',
        ]);
        
        // Преобразуем массив продуктов для удобства
        $items = [];
        foreach ($validatedData['products'] as $product) {
            if (!empty($product['count'])) {
                $items[] = ['product_id' => $product['id'], 'count' => $product['count']];
            }
        }
        
        if (empty($items)) {
            return back()->withErrors(['message' => 'Добавьте хотя бы один товар в заказ.']);
        }
        
        // Логика создания заказа (почти такая же, как в API)
        try {
            DB::transaction(function () use ($validatedData, $items) {
                foreach ($items as $item) {
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

                foreach ($items as $item) {
                    $order->items()->create($item);
                    $this->stockService->debit($item['product_id'], $order->warehouse_id, $item['count'], $order->id);
                }
            });
        } catch (\Exception $e) {
            // Если ошибка, возвращаемся назад и показываем ее
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        // Если все успешно, перенаправляем на главную страницу
        return redirect()->route('orders.index');
    }

    // Методы для кнопок управления (они просто вызывают API-логику и делают редирект)
    public function complete(Order $order)
    {
        if ($order->status === 'active') {
            $order->status = 'completed';
            $order->completed_at = Carbon::now();
            $order->save();
        }
        return redirect()->route('orders.index');
    }

    public function cancel(Order $order)
    {
        if ($order->status === 'active') {
            DB::transaction(function () use ($order) {
                $order->status = 'canceled';
                $order->save();
                foreach ($order->items as $item) {
                    $this->stockService->credit($item->product_id, $order->warehouse_id, $item->count, $order->id);
                }
            });
        }
        return redirect()->route('orders.index');
    }
    
    public function resume(Order $order)
    {
        if ($order->status === 'canceled') {
            try {
                DB::transaction(function () use ($order) {
                    foreach ($order->items as $item) {
                        $stock = Stock::where('warehouse_id', $order->warehouse_id)
                                      ->where('product_id', $item->product_id)
                                      ->first();
                        if (!$stock || $stock->stock < $item->count) {
                            throw new \Exception('Недостаточно товара');
                        }
                    }
                    foreach ($order->items as $item) {
                        $this->stockService->debit($item->product_id, $order->warehouse_id, $item->count, $order->id);
                    }
                    $order->status = 'active';
                    $order->save();
                });
            } catch (\Exception $e) {
                // В веб-версии просто ничего не делаем в случае ошибки
            }
        }
        return redirect()->route('orders.index');
    }
}