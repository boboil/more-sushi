<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminOrderController;
use \App\Http\Controllers\Api\UserController;
use \App\Http\Controllers\Shop\ProductController;

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
Route::post('login', [UserController::class, 'login']);
Route::post('register', [UserController::class, 'register']);
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('admin-order', [AdminOrderController::class, 'index']);
Route::get('today-admin-orders', [AdminOrderController::class, 'indexAdminOrders']);
Route::get('today-admin-products', [AdminOrderController::class, 'getAdminOrdersProducts']);
Route::get('yesterday-admin-orders', [AdminOrderController::class, 'indexAdminYesterdayOrders']);
Route::get('yesterday-admin-products', [AdminOrderController::class, 'indexAdminYesterdayProducts']);
Route::post('admin-create-order', [AdminOrderController::class, 'addOrder']);
Route::post('admin-create-product', [AdminOrderController::class, 'addProduct']);
Route::delete('admin-remove-order/{order_id}', [AdminOrderController::class, 'removeOrder']);
Route::delete('admin-remove-product/{product_id}', [AdminOrderController::class, 'removeProduct']);
Route::get('products', [AdminOrderController::class, 'getProducts']);

Route::prefix('shop')->group(function () {
    Route::get('product/{slug}', [ProductController::class, 'show']);
});
