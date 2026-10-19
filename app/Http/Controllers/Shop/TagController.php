<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop\Tag;
use Symfony\Component\HttpFoundation\JsonResponse;

class TagController extends Controller
{
    /**
     * Активні фільтри для меню, у порядку з адмінки
     */
    public function index(): JsonResponse
    {
        $tags = Tag::getEnabledTags()->map(fn (Tag $tag) => [
            'slug' => $tag->slug,
            'title' => $tag->title,
        ]);
        return new JsonResponse(['data' => $tags]);
    }
}
