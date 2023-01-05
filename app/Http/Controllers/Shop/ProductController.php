<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\Shop\ProductCollection;
use App\Models\Shop\Product;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductController extends Controller
{
    public function show($slug)
    {
        $product = Product::getProductsBySlug($slug);
        $product->main_image = asset($product->main_image);
        return new JsonResponse([
            'data' => $product
        ]);
    }
    public function getRelatedProducts(): ProductCollection
    {
        $products = Product::getRelatedProducts();
        return new ProductCollection($products);
    }
}
