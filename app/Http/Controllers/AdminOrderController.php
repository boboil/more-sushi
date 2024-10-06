<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminOrder\OrderCollection;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\AdminOrder;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Response;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    /**
     * @param Request $request
     *
     * @return ProductCollection
     */
    public function index(Request $request): ProductCollection
    {
        $products = Product::getProducts();
        return new ProductCollection($products);
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function addOrder(Request $request): bool
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
     * @return Response
     * @throws \Exception
     */
    public function removeOrder($orderId)
    {
        $adminOrder = AdminOrder::find($orderId);
        $adminOrder->products()->detach();
        if ($adminOrder->delete()) {
            return new Response(null, Response::HTTP_OK);
        }
    }

    /**
     * @param Request $request
     *
     * @return OrderCollection
     * */
    public function indexAdminOrders(Request $request): OrderCollection
    {
        $orders = AdminOrder::getTodayOrders();

        return new OrderCollection($orders);
    }

    /**
     * @param Request $request
     *
     * @return OrderCollection
     * */
    public function indexAdminYesterdayOrders(Request $request): OrderCollection
    {
        $orders = AdminOrder::getYesterdayOrders();

        return new OrderCollection($orders);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * */
    public function getAdminOrdersProducts(Request $request): JsonResponse
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

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * */
    public function indexAdminYesterdayProducts(Request $request): JsonResponse
    {
        $orders = AdminOrder::getYesterdayOrders();
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

    /**
     * @param Request $request
     * @return ProductCollection
     */
    public function getProducts(Request $request): ProductCollection
    {
        $products = Product::getProducts();

        return new ProductCollection($products);
    }
    /**
     * @param Request $request
     *
     * @return bool
     */
    public function addProduct(Request $request): bool
    {
        $product = new Product();
        $product->title = $request->input('title');
        $product->sort_order = $request->input('sort_order');
        $product->price = 0;
        $product->save();

        return true;
    }

    public function updateProduct(Request $request): bool
    {
        $product_id =  $request->input('id');
        $product = Product::where('id', $product_id)->first();
        /** @var Product $product */
        $product->title = $request->input('title');
        $product->sort_order = $request->input('sort_order');
        $product->price = 0;
        $product->save();

        return true;
    }

    /**
     * @param $productId
     *
     * @return Response
     * @throws \Exception
     */
    public function removeProduct($productId)
    {
        $product = Product::find($productId);
        $product->orders()->detach();
        if ($product->delete()) {
            return new Response(null, Response::HTTP_OK);
        }
    }
}
