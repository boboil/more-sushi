<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop\Delivery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class DeliveryController extends Controller
{
    public function getDeliveryCost() {
        return Delivery::first();
    }
    public function setDeliveryCost(Request $request) {
        $delivery = Delivery::first();
        $delivery->cost = $request->input('cost');
        $delivery->save();
        return redirect()->back();
    }

    public function getDeliveryApiCost(): JsonResponse
    {
        $delivery = $this->getDeliveryCost();
        return new JsonResponse($delivery->cost);
    }
}
