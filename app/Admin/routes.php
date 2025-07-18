<?php

use App\Http\Controllers\Utils\PosterAuthController;
use App\Http\Controllers\Shop\DeliveryController;
use \App\Models\Shop\Order;

Route::get('', ['as' => 'admin.dashboard', function () {
    $orders = Order::getTodayOrders();
    $content = 'Сьогодні ' . count($orders) . ' замовлень. <br/>';
    $content .= 'На сумму ' . $orders->sum('sum') . ' грн.';
	return AdminSection::view($content, 'Панель управління');
}]);

Route::get('information', ['as' => 'admin.information', function () {
    $content = 'Define your information here.';
    return AdminSection::view($content, 'Information');
}]);
Route::get('status-orders', ['as' => 'admin.orders.status', function () {

    $util = new PosterAuthController();
    $data = $util->getOrders();
    $data= $data['response'];
    return view('orders', compact('data'));
}]);
Route::get('delivery', ['as' => 'admin.delivery', function () {
    $util = new DeliveryController();
    $delivery = $util->getDeliveryCost();
    $content = view('delivery', compact('delivery'));
    return AdminSection::view($content, 'Вартість доставки');
}]);
//Route::get('admin/orders', [\App\Http\Controllers\Api\OrderController::class, 'index'])->name('orders');
