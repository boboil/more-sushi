<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AppController;
use App\Http\Controllers\Utils\PosterAuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Route::get('/', function () {
//    return view('welcome');
//});
Route::get('/admin/status-orders', function (PosterAuthController $poster) {
    abort_unless(auth()->user()?->isSuperAdmin(), 403);

    $data = $poster->getOrders()['response'] ?? [];

    return view('orders', compact('data'));
})->middleware('auth')->name('admin.orders.status');
Route::get('/{any}', [AppController::class, 'index'])
    ->where('any', '^(?!admin(?:/|$)).*');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
