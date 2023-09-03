<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AppController;
use App\Http\Controllers\Utils\PosterAuthController;
use App\Http\Controllers\Shop\ProductController;

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
Route::post('/poster-auth', [PosterAuthController::class, 'getProducts'])->name('import.products');
Route::post('/delete-products', [ProductController::class, 'deleteSelected'])->name('delete.products');
if (!strpos(url()->current(),"admin")) {
    Route::get('/{any}', [AppController::class, 'index'])->where('any', '.*');
}

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
