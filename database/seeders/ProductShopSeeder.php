<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductShopSeeder extends Seeder
{
    private static $products = [
        [
            'title' => 'Сет "Філадельфія Преміум',
            'price' => '100',
            'discount' => '0',
            'count' => '40',
            'weight' => '1205',
            'consist' => ' ',
            'stock' => '0',
            'latest' => '0',
            'main_image' => 'https://tokyo-sushi.com.ua/storage/products/d8/d8acf2078e478c3cdb01d568971bf0525dd608192024.jpeg',
            'images' => '',
            'description' => '',
        ],
    ];
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('product_shop')->insert(self::$products);
    }
}
