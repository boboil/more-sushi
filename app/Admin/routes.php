<?php

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
