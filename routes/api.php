<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminOrderController;

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

Route::get('admin-order', [AdminOrderController::class, 'index']);
Route::get('today-admin-orders', [AdminOrderController::class, 'indexAdminOrders']);
Route::get('today-admin-products', [AdminOrderController::class, 'getAdminOrdersProducts']);
Route::post('admin-create-order', [AdminOrderController::class, 'addOrder']);

