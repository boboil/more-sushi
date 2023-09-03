<?php

namespace App\Http\Controllers\Utils;

use App\Http\Controllers\Controller;
use App\Models\Shop\Category;
use App\Models\Shop\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use Intervention\Image\Facades\Image;
use mysql_xdevapi\Exception;

class PosterAuthController extends Controller
{
//    protected $token = "712773:9192826f326dccc5d5e9b2e818b72c5b"; //my token
    protected $token = "726501:18290431aced2defb79f3e103ae5730a"; //Platon token


    public function posterAuth()
    {
        $account = 'dmlushpi132';
        $code = 'd85b9a4090de3e00c472271689373619';

        $url = "https://$account.joinposter.com/api/v2/auth/access_token";

        $auth = [
            'application_id' => 3028,
            'application_secret' => 'b328dfb03c98097817d00c3d9c671762',
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'https://bot.moresushi.in.ua/admin/auth',
            'code' => $code
        ];

        $data = $this->sendRequest($url, $auth, 'post');
        return $data['access_token'];
    }

    public function getMenu()
    {
        $url = 'https://joinposter.com/api/menu.getCategories'
            . '?token=' . $this->token
            . '&fiscal=1';

        $data = $this->sendRequest($url);
        dd($data);
        $categories_id = [3, 5, 7, 15, 18];

        foreach ($data['response'] as $item) {
            if (in_array(intval($item['category_id']), $categories_id)) {
                $selected_categories[] = $item;
            }
        }

        dd($selected_categories);

    }

    public function saveImage($url)
    {
        $fullUrl = "https://dmlushpi13.joinposter.com/" . $url;
        $filename = basename($fullUrl);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $availableExt = ['jpg', 'png', 'gif', 'bmp', 'webp', 'jpeg'];
        $client = new Client();
        $response = $client->get($fullUrl);
        Storage::disk('public')->put('products/' . $filename, $response->getBody());
        if (in_array($extension, $availableExt)) {
            $img = Image::make('images/products/' . $filename);
            $path = 'images/products/converted/' . $img->filename . '.webp';
            $width = $img->width();
            if ($width > 600) {
                $img->resize(600, 600, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            $img->encode('webp', 100)->save($path);
            return $path;
        }

//        Storage::disk('public')->delete('products/' . $filename, $response->getBody());
        return $filename;
    }

    public function getProducts(Request $request)
    {
        $categories_id = [3 => 5, 5 => 1, 7 => 3, 15 => 4, 18 => 6];

        foreach ($categories_id as $key => $category_id) {
            $category_site_id = $category_id;
            $url = 'https://joinposter.com/api/menu.getProducts';
            $data = http_build_query([
                'token' => $this->token,
                'category_id' => $key,
                'type' => 'batchtickets'
//            'type' => 'product'
            ]);
            $fullUrl = $url . '?' . $data;
            $data = $this->sendRequest($fullUrl);
            foreach ($data['response'] as $item) {
                $product = Product::getProductsByPosterId($item['product_id']);
                if ($product) {
//                    $product->poster_id = $item['product_id'];
                    $product->price = $this->transformPrice($item['price']['4']);
                    $product->main_image = $this->saveImage($item['photo_origin']);
                    $product->save();
                } else {
                    $product = new Product();
                    $product->title = $item['product_name'];
                    $product->price = $this->transformPrice($item['price']['4']);
                    $product->count = 1;
                    $product->weight = $item['out'];
//                        $product->consist = $this->trasformIngridients($item['ingredients']);
                    $product->consist = '';
                    $product->stock = 0;
                    $product->latest = 0;
                    $product->main_image = $this->saveImage($item['photo_origin']);
                    $product->description = $item['product_production_description'];
                    $product->slug = Str::slug($item['product_name']);
                    $product->isRelated = 0;
                    $product->poster_id = $item['product_id'];
                    $product->save();
                    $product->category()->attach($category_site_id);

                }
            }
        }
        dd($data);
    }

    public function trasformIngridients($data)
    {
        $ingredients = "";

        foreach ($data as $item) {
            if ($ingredients != "") {
                $ingredients .= ", ";
            }
            $ingredients .= $item["ingredient_name"];
        }

        return $ingredients;
    }

    public function transformPrice($price)
    {
        return (int)$price / 100;
    }

    public function createIncomingOrder($products, $phone, $delivery, $comment)
    {
        $url = 'https://joinposter.com/api/incomingOrders.createIncomingOrder'
            . '?token=' . $this->token;
        $incoming_order = [
            'spot_id' => 4,
            'phone' => $phone,
            'service_mode'=> $delivery['service_mode'],
            'client_address'=> $delivery['client_address'],
            'products' => $products,
            'comment' => $comment
        ];
        return $this->sendRequest($url, $incoming_order, 'post');
    }

    function sendRequest($url, $data = [], $method = 'get')
    {
        $ch = curl_init();

        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        // If you want to send a GET request instead,
        // you could add elseif condition here (not necessary in your current code)

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $headers = [
            'Content-Type: application/x-www-form-urlencoded'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $output = curl_exec($ch);

        if ($output === FALSE) {
            return 'CURL Error:' . curl_error($ch);
        }

        curl_close($ch);

        return json_decode($output, true);
    }

}
