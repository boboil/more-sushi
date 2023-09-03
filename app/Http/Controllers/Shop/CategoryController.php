<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\Shop\CategoryCollection;
use App\Models\Shop\Category;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class CategoryController extends Controller
{
    public function index(): CategoryCollection
    {
        $categories = Category::getEnableCategories();
        return new CategoryCollection($categories);
    }

    public function stock()
    {
        $categories = Category::getStockCategories();
        return new JsonResponse($categories);
    }
}
