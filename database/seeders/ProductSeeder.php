<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    private static $products = [
        ['title' => 'Футомакі з лососем', 'price' => '100'],
        ['title' => 'Футомакі з вугром', 'price' => '110'],
        ['title' => 'Каліфорнія з вугром ', 'price' => '110'],
        ['title' => 'Сирний зі сніжним крабом', 'price' => '90'],
        ['title' => 'Каліфорнія з лососем', 'price' => '100'],
        ['title' => 'Каліфорнія з креветкою', 'price' => '110'],
        ['title' => 'Філадельфія з лососем', 'price' => '120'],
        ['title' => 'Рол сирний з лососем', 'price' => '110']

    ];
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('products')->insert(self::$products);
    }
}
