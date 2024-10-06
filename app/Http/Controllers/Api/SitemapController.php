<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SitemapController extends Controller
{
    public function index()
    {
        $date = Carbon::now()->setTimezone('Europe/Kiev')->toIso8601String();
        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $sitemap .= '<url>
                        <loc>https://moresushi.in.ua/</loc>
                        <lastmod>'.$date.'</lastmod>
                        <priority>0.8</priority>
                    </url>
                    <url>
                        <loc>https://moresushi.in.ua/catalog</loc>
                        <lastmod>'.$date.'</lastmod>
                        <priority>1.0</priority>
                    </url>
                    <url>
                        <loc>https://moresushi.in.ua/stock</loc>
                        <lastmod>'.$date.'</lastmod>
                        <priority>1.0</priority>
                    </url>
                    <url>
                        <loc>https://moresushi.in.ua/delivery</loc>
                        <lastmod>'.$date.'</lastmod>
                        <priority>1.0</priority>
                    </url>
                    <url>
                        <loc>https://moresushi.in.ua/contacts</loc>
                        <lastmod>'.$date.'</lastmod>
                        <priority>1.0</priority>
                    </url>
                    <url>
                        <loc>https://moresushi.in.ua/checkout</loc>
                        <lastmod>'.$date.'</lastmod>
                        <priority>1.0</priority>
                    </url>';
        $products = Product::pluck('slug');
        foreach ($products as $product) {
            $sitemap .= '<url>
                            <loc>https://moresushi.in.ua/product/' . $product . '</loc>
                            <lastmod>'.$date.'</lastmod>
                            <priority>1.00</priority>
                        </url>';
        }
        $sitemap .= '</urlset>';
        $sitemapContent = file_get_contents(public_path('sitemap.xml'));
        return response($sitemapContent, 200, ['Content-Type' => 'text/xml']);
        return response()->json($sitemap);
    }
}
