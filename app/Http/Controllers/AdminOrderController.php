<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index()
    {
        $products = Product::all();
    }
}
