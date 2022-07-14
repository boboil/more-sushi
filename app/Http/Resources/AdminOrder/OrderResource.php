<?php

namespace App\Http\Resources\AdminOrder;

use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $products = $this->products;
        return [
            'id' => $this->id,
            'sum_product' => $this->sum_product,
            'order_time' => $this->order_time ? (new Carbon($this->order_time))->format('F d, Y h:i') : null,
            'address' => $this->address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            # conditional relations
            'products' => new ProductCollection($products),
        ];
    }
}
