<?php

namespace App\Http\Resources\Shop;

use App\Models\Shop\Product;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var $this Product */
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'slug' => $this->slug,
            'price' => $this->price,
            'discount' => $this->discount,
            'count' => $this->count,
            'weight' => $this->weight,
            'consist' => $this->consist,
            'stock' => $this->stock,
            'latest' => $this->latest,
            'main_image' => asset($this->main_image),
            'images' => $this->images,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'category' => $this->category
            ];
    }
}
