<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AppController;

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

//Route::get('/orders', [\App\Http\Controllers\Shop\OrderController::class, 'index']);
if (!strpos(url()->current(),"admin")) {
    Route::get('/{any}', [AppController::class, 'index'])->where('any', '.*');
}

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
