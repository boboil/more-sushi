<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class OrderController extends Controller
{
    public function addOrder(Request $request)
    {
        $customer = collect($request->input('customer'));
        $products = collect($request->input('products'));
        $time = Carbon::parse($customer['time']['day']);
        $order = new Order();
        $order->customer_name = $customer['name'];
        $order->customer_phone = $customer['phone'];
        $order->customer_delivery_type = $customer['delivery'];
        $order->customer_street = $customer['street'];
        $order->customer_building = $customer['building'];
        $order->online_payment = $customer['payment'];
        $order->sticks_educational = $customer['sticks']['educational'];
        $order->sticks_standard = $customer['sticks']['standard'];
        $order->is_as_soon_as_possible = $customer['isAsSoonAsPossible'] == '1' ? 1 : 0;
        $order->time = $time;
        $order->save();
        foreach ($products as $product) {
            $order->products()->attach($product['id'], ['shop_product_quantity'=>$product['quantity']]);
        }
        return new JsonResponse([
            'data' => $order
        ]);
    }
    public function index()
    {
        $orders = Order::all();
        foreach ($orders as $key => $order) {
            if ($key == 7){
                dd($order->products);
            }
        }

    }
}
