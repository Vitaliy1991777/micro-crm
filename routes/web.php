<?php

use App\Http\Controllers\Web\OrderPageController; // <-- Импортируем наш новый контроллер
use Illuminate\Support\Facades\Route;

// Роут для главной страницы со списком заказов
Route::get('/', [OrderPageController::class, 'index'])->name('orders.index');

// Роут для обработки формы создания нового заказа
Route::post('/orders', [OrderPageController::class, 'store'])->name('orders.store');

// Роуты для кнопок управления заказом
Route::post('/orders/{order}/complete', [OrderPageController::class, 'complete'])->name('orders.complete');
Route::post('/orders/{order}/cancel', [OrderPageController::class, 'cancel'])->name('orders.cancel');
Route::post('/orders/{order}/resume', [OrderPageController::class, 'resume'])->name('orders.resume');