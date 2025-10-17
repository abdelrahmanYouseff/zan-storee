<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Product');
})->name('home');

Route::get('/product/{id}', function ($id) {
    return Inertia::render('Product', [
        'productId' => $id
    ]);
})->name('product');

Route::get('shipping-info', function () {
    return Inertia::render('ShippingInfo');
})->name('shipping-info');

Route::get('returns', function () {
    return Inertia::render('Returns');
})->name('returns');

Route::get('warranty', function () {
    return Inertia::render('Warranty');
})->name('warranty');

Route::get('faq', function () {
    return Inertia::render('FAQ');
})->name('faq');

Route::get('privacy-policy', function () {
    return Inertia::render('PrivacyPolicy');
})->name('privacy-policy');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('orders', [OrderController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('orders');

Route::post('orders', [OrderController::class, 'store'])
    ->name('orders.store');

Route::post('api/orders/search', [OrderController::class, 'searchByEmail'])
    ->name('orders.search');

// Chat routesss
Route::get('chat', [\App\Http\Controllers\ChatController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('chat');

Route::get('api/chat/sessions', [\App\Http\Controllers\ChatController::class, 'getSessions'])
    ->middleware(['auth', 'verified'])
    ->name('chat.sessions');

Route::get('api/chat/{sessionId}/messages', [\App\Http\Controllers\ChatController::class, 'getMessages'])
    ->middleware(['auth', 'verified']);

Route::post('api/chat/send', [\App\Http\Controllers\ChatController::class, 'sendMessage'])
    ->middleware(['auth', 'verified']);

Route::post('api/chat/customer/send', [\App\Http\Controllers\ChatController::class, 'storeCustomerMessage']);

Route::get('api/chat/{sessionId}/admin-responses', [\App\Http\Controllers\ChatController::class, 'getAdminResponses']);

Route::get('api/chat/unread-count', [\App\Http\Controllers\ChatController::class, 'getUnreadCount'])
    ->middleware(['auth', 'verified']);

Route::delete('api/chat/{sessionId}', [\App\Http\Controllers\ChatController::class, 'deleteSession'])
    ->middleware(['auth', 'verified']);

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
