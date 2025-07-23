<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление Заказами</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; margin: 20px; color: #333; background-color: #f4f4f9; }
        h1, h2 { color: #444; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; background-color: white; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; }
        
        .form-section {
            border: 1px solid #ccc;
            padding: 20px;
            margin-bottom: 30px;
            background-color: #ffffff;
            max-width: 300px; 
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-section h2 { margin-top: 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input[type="text"] {
            display: block;
            margin-bottom: 15px;
            width: 100%; /* <--- Оставляем 100%, но теперь это 100% от 700px */
            box-sizing: border-box;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[type="number"] { width: 120px; padding: 8px; } /* Делаем поля для количества чуть шире */

        button { padding: 10px 15px; cursor: pointer; border: none; color: white; border-radius: 4px; font-size: 14px; }
        button.create { background-color: #28a745; }
        button.complete { background-color: #007bff; }
        button.cancel { background-color: #dc3545; }
        button.resume { background-color: #ffc107; color: #333; }
        button.edit { background-color: #17a2b8; }
        button.delete { background-color: #6c757d; }
        .actions form, .actions a { display: inline-block; margin-right: 5px; }
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        ul { padding-left: 20px; margin-top: 0; }
    </style>
</head>
<body>

    <h1>Микро-CRM</h1>

    <!-- Форма создания нового заказа -->
    <div class="form-section">
        <h2>Создать новый заказ</h2>
        
        @if ($errors->any())
            <div class="error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('orders.store') }}" method="POST">
            @csrf
            
            <label for="customer">Имя клиента:</label>
            <input type="text" id="customer" name="customer" value="{{ old('customer') }}" required>

            <label for="warehouse_id">Склад:</label>
            <select id="warehouse_id" name="warehouse_id" required>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
            
            <hr>
            <h3>Товары в заказе:</h3>
            @foreach($products as $product)
                <div>
                    <label for="product_{{ $product->id }}">
                        {{ $product->name }} (Цена: {{ $product->price }})
                    </label>
                    <input type="hidden" name="products[{{ $loop->index }}][id]" value="{{ $product->id }}">
                    <input type="number" id="product_{{ $product->id }}" name="products[{{ $loop->index }}][count]" min="0" placeholder="Количество">
                </div>
            @endforeach
            
            <br>
            <button type="submit" class="create">Создать заказ</button>
        </form>
    </div>

    <!-- Таблица с существующими заказами -->
    <h2>Список заказов</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Клиент</th>
                <th>Склад</th>
                <th>Статус</th>
                <th>Товары</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->customer }}</td>
                    <td>{{ $order->warehouse->name }}</td>
                    <td>{{ $order->status }}</td>
                    <td>
                        <ul>
                        @foreach($order->items as $item)
                            <li>{{ $item->product->name }} - {{ $item->count }} шт.</li>
                        @endforeach
                        </ul>
                    </td>
                    <td class="actions">
                        {{-- Кнопки для АКТИВНЫХ заказов --}}
                        @if($order->status === 'active')
                            <form action="{{ route('orders.complete', $order) }}" method="POST">
                                @csrf
                                <button type="submit" class="complete">Завершить</button>
                            </form>
                            <form action="{{ route('orders.cancel', $order) }}" method="POST">
                                @csrf
                                <button type="submit" class="cancel">Отменить</button>
                            </form>
                        @endif

                        {{-- Кнопка для ОТМЕНЕННЫХ заказов --}}
                        @if($order->status === 'canceled')
                            <form action="{{ route('orders.resume', $order) }}" method="POST">
                                @csrf
                                <button type="submit" class="resume">Возобновить</button>
                            </form>
                        @endif
                        
                        {{-- Для завершенных заказов кнопок нет --}}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Заказов пока нет.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Пагинация -->
    {{ $orders->links() }}

</body>
</html>