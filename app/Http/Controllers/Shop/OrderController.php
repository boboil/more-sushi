<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Utils\PosterAuthController;
use App\Models\Shop\Order;
use App\Models\Shop\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class OrderController extends Controller
{
    public function addOrder(Request $request)
    {
        $customer = collect($request->input('customer'));
        $products = collect($request->input('products'));
        $posterOrderProducts = [];
        foreach ($products as $product) {
            $item = Product::find($product['id']);
            $posterOrderProducts[] = ['product_id' => $item->poster_id, 'count' => $product['quantity']];
        }
        $service_type = $customer['delivery'] === 'курʼєр' ? 3 : 1;
        $delivery = [
            'service_mode' => 1,
            'client_address' => null
        ];
        if ($service_type === 3) {
            $delivery = [
                'service_mode' => 3,
                'client_address' => [
                    'address1' => $customer['street'],
                    'address2' => $customer['building']
                ]
            ];
        }
        $comment = $this->commentToPosterOrder($customer);
        $posterUtil = new PosterAuthController();
        $posterOrder = $posterUtil->createIncomingOrder($posterOrderProducts, $customer, $delivery, $comment);

        $time = Carbon::parse($customer['time']['day'] . $customer['time']['time']);
        $sum = $request->input('sum');
        $order = new Order();
        $order->customer_name = $customer['name'];
        $order->customer_phone = $customer['phone'];
        $order->customer_delivery_type = $customer['delivery'];
        $order->customer_street = $customer['street'];
        $order->customer_building = $customer['building'];
        $order->online_payment = $customer['payment'];
        $order->sticks_educational = $customer['sticks']['educational'];
        $order->sticks_standard = $customer['sticks']['standard'];
        $order->is_as_soon_as_possible = $customer['isAsSoonAsPossible'] == 'true' ? 1 : 0;
        $order->time = $time->toDateTime();
        $order->sum = $sum;
        $order->save();
        foreach ($products as $product) {
            $order->products()->attach($product['id'], ['shop_product_quantity' => $product['quantity']]);
        }
        return new JsonResponse([
            'data' => $order,
            'posterOrder' => $posterOrder
        ]);
    }

    public function commentToPosterOrder($customer): string
    {
        $delivery = $customer['delivery'] === 'курʼєр' ? $customer['delivery'].': '.$customer['street'] . ' ' . $customer['building'] : $customer['delivery'];
        $time = Carbon::parse($customer['time']['day'] . $customer['time']['time']);
        $comment = "Заказ с сайта" .PHP_EOL;
        $comment .= "Клиент: " . $customer['name'].PHP_EOL;
        $comment .= $delivery .PHP_EOL;
        $comment .= "Оплата: " . $customer['payment'].PHP_EOL;
        $comment .= "Палочки: " . $customer['sticks']['standard'] .' стандартных.'. $customer['sticks']['educational'].' учебных.'.PHP_EOL;
        $comment .= $customer['isAsSoonAsPossible'] == 'true' ? 'Как можно быстрее' : 'На время: '. $time->format('d-m-Y H:i').PHP_EOL;
        return $comment;
    }

    public function index()
    {
        $orders = Order::getTodayOrders();
        dd($orders);
    }
}
