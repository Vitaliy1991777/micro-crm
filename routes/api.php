<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\ProductMovementController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Роут для получения списка складов
Route::get('/warehouses', [WarehouseController::class, 'index']);

// Роут для получения списка товаров с их остатками
Route::get('/products', [ProductController::class, 'index']);

Route::post('/orders', [OrderController::class, 'store']);

// Роут для получения списка заказов
Route::get('/orders', [OrderController::class, 'index']);
// Роут для создания заказа
Route::post('/orders', [OrderController::class, 'store']);

// Роут для завершения заказа
Route::post('/orders/{order}/complete', [OrderController::class, 'complete']);

// Роут для отмены заказа
Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

// Роут для возобновления заказа
Route::post('/orders/{order}/resume', [OrderController::class, 'resume']);

// Роут для обновления заказа
Route::put('/orders/{order}', [OrderController::class, 'update']);

// Роут для просмотра истории движений
Route::get('/product-movements', [ProductMovementController::class, 'index']);