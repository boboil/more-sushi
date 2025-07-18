<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Utils\PosterAuthController;
use App\Models\Shop\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function statusOrdersSpots(Request $request): bool|string
    {
        $util = new PosterAuthController();
        $data = $util->getOrders();
        $orders = $data['response'];
        $spots = [
            4 => 'Троицкая',
            5 => 'Проспект Шевченко',
            6 => 'Курская',
        ];

        $establishment = (int)$request->input('establishment');
        if ($establishment || $establishment != 0) {
            $orders = array_filter($orders, function ($order) use ($establishment) {
                return (int)$order['spot_id'] === $establishment;
            });
        }

        $result = array_map(function ($order) use ($spots) {
            $orderData = [
                'Номер заказа'       => $order['transaction_id'],
                'Заведение'          => $spots[$order['spot_id']],
                'transaction_id'     => $order['transaction_id'],
                'Время открытия чека' => Carbon::createFromTimestampMs($order['date_start'])->format('Y-m-d H:i:s'),
                'Клиент'             => $order['client_firstname'].' '.$order['client_lastname'].' '.$order['client_phone'],
            ];
            $orderData['products'] = isset($order['products']) && is_array($order['products'])
                ? array_map(function ($product) {
                    $productDB = Product::getProductsByPosterId($product['product_id']);
                    return [
                        'Продукт'    => $productDB ? $productDB->title : $product['product_id'],
                        'Количество' => $product['num']
                    ];
                }, $order['products'])
                : [];
            return $orderData;
        }, $orders);

        return json_encode($result);
    }
}
