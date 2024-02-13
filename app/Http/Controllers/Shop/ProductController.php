<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\Shop\ProductCollection;
use App\Models\Shop\Product;
use Illuminate\Http\Request;

use Intervention\Image\Facades\Image;
use JetBrains\PhpStorm\NoReturn;
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

    public function deleteSelected(Request $request): void
    {
        $pr_ids = $request->input('_id');
        Product::whereIn('id', $pr_ids)->delete();
    }

    public function fixImagesInProduct()
    {
        $products = Product::all();
        foreach ($products as $product) {
            $this->convertToToWebpFromAdmin($product);
        }
        return 'done';
    }

    public function convertToToWebpFromAdmin($product): void
    {
        $originImage = Image::make($product->main_image);
        if ($originImage->mime !== 'image/webp') {
            $webpImage = Image::make($product->main_image)
                ->encode('webp', 90);
            $webpPath = 'images/products/converted/' . basename($webpImage->filename) . '.webp';
            $webpImage->resize(600, 600, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->save(public_path($webpPath));
            $product->main_image = $webpPath;
            $product->save();
        }
    }

    public function landingProducts(): ProductCollection
    {
        $products = Product::getProductsForLanding();
        return new ProductCollection($products);
    }
}
