<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminOrder\OrderCollection;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\AdminOrder;
use App\Models\Product;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    /**
     * @param Request $request
     *
     * @return ProductCollection
     */
    public function index(Request $request)
    {
        $products = Product::all();

        return new ProductCollection($products);
    }

    /**
     * @param Request $request
     */
    public function addOrder(Request $request)
    {
        $rolls = $request->input('selected');
        $date = Carbon::now();
        $address = $request->input('address');
        $sync_data = [];

        $order = new AdminOrder();
        $order->address = $address;
        foreach ($rolls as $roll) {
            $order->sum_product += (int)$roll['quantity'];
            $sync_data[$roll['id']] = ['quantity' => $roll['quantity']];
        }
        $order->order_time = $date;
        $order->save();

        $order->products()->attach($sync_data);

        return true;
    }

    /**
     * @param Request $request
     *
     * @return OrderCollection
     * */
    public function indexAdminOrders(Request $request)
    {
        $orders = AdminOrder::getTodayOrders();

        return new OrderCollection($orders);

    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * */
    public function getAdminOrdersProducts(Request $request)
    {
        $orders = AdminOrder::getTodayOrders();
        $rolls = [];

        foreach ($orders as $order) {
            foreach ($order->products as $product) {
                $key = array_key_exists($product->id, $rolls);
                if ($key) {
                    $rolls[$product->id]['quantity'] += (int)$product->pivot->quantity;
                } else {
                    $rolls[$product->id] = [
                        'id' => $product->id,
                        'title' => $product->title,
                        'quantity' => (int)$product->pivot->quantity
                    ];
                }

            }
        }
        return new JsonResponse([
            'data' => $rolls
        ]);
    }
}
