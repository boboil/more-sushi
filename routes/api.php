<?php

use App\Http\Controllers\Api\SalaryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminOrderController;
use \App\Http\Controllers\Api\UserController;
use \App\Http\Controllers\Api\SitemapController;
use \App\Http\Controllers\Shop\ProductController;
use \App\Http\Controllers\Shop\CategoryController;
use \App\Http\Controllers\Shop\OrderController;
use \App\Http\Controllers\Shop\QuestionController;

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
//header('Access-Control-Allow-Origin: https://moresushi.in.ua');
header('Access-Control-Allow-Headers: origin, x-requested-with, content-type');
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
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
Route::post('admin-update-product', [AdminOrderController::class, 'updateProduct']);
Route::delete('admin-remove-order/{order_id}', [AdminOrderController::class, 'removeOrder']);
Route::delete('admin-remove-product/{product_id}', [AdminOrderController::class, 'removeProduct']);
Route::get('products', [AdminOrderController::class, 'getProducts']);
Route::get('get-users', [SalaryController::class, 'getUsers']);

Route::prefix('shop')->group(function () {
    Route::get('product/{slug}', [ProductController::class, 'show']);
    Route::get('catalog', [CategoryController::class, 'index']);
    Route::get('stock', [CategoryController::class, 'stock']);
    Route::get('related-products', [ProductController::class, 'getRelatedProducts']);
    Route::post('add-order', [OrderController::class, 'addOrder']);
    Route::post('add-question', [QuestionController::class, 'addQuestion']);
    Route::get('sitemap', [SitemapController::class, 'index']);
    Route::get('landing-products', [ProductController::class, 'landingProducts']);
    Route::post('landing-order', [OrderController::class, 'addLandingOrder']);
});
