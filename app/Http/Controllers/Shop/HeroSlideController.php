<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\Shop\HeroSlideResource;
use App\Models\Shop\HeroSlide;

class HeroSlideController extends Controller
{
    public function index()
    {
        return HeroSlideResource::collection(HeroSlide::getEnabledSlides());
    }
}
