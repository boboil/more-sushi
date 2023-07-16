<?php

use SleepingOwl\Admin\Navigation\Page;

// Default check access logic
// AdminNavigation::setAccessLogic(function(Page $page) {
// 	   return auth()->user()->isSuperAdmin();
// });
//
// AdminNavigation::addPage(\App\User::class)->setTitle('test')->setPages(function(Page $page) {
// 	  $page
//		  ->addPage()
//	  	  ->setTitle('Dashboard')
//		  ->setUrl(route('admin.dashboard'))
//		  ->setPriority(100);
//
//	  $page->addPage(\App\User::class);
// });
//
// // or
//
// AdminSection::addMenuPage(\App\User::class)

return [
    [
        'title' => 'Dashboard',
        'icon' => 'fas fa-tachometer-alt',
        'url' => route('admin.dashboard'),
    ],

    [
        'title' => 'Магазин',
        'pages' => [
            (new Page(\App\Models\Shop\Product::class))
                ->setPriority(1)
                ->setIcon('fa fa-cube')
                ->setUrl('admin/products')
                ->setTitle('Товари'),
            (new Page(\App\Models\Shop\Category::class))
                ->setPriority(2)
                ->setIcon('fa fa-cubes')
                ->setUrl('admin/categories')
                ->setTitle('Категорії'),
            (new Page(\App\Models\Shop\Order::class))
                ->setPriority(3)
                ->setIcon('fa fa-gift')
                ->setUrl('admin/orders')
                ->setTitle('Замовлення'),
            (new Page(\App\Models\Shop\Question::class))
                ->setPriority(4)
                ->setIcon('fa fa-question-circle')
                ->setUrl('admin/questions')
                ->setTitle('Зворотній звʼязок')
        ]
    ],

    // Examples
    // [
    //    'title' => 'Content',
    //    'pages' => [
    //
    //        \App\User::class,
    //
    //        // or
    //
    //        (new Page(\App\User::class))
    //            ->setPriority(100)
    //            ->setIcon('fas fa-users')
    //            ->setUrl('users')
    //            ->setAccessLogic(function (Page $page) {
    //                return auth()->user()->isSuperAdmin();
    //            }),
    //
    //        // or
    //
    //        new Page([
    //            'title'    => 'News',
    //            'priority' => 200,
    //            'model'    => \App\News::class
    //        ]),
    //
    //        // or
    //        (new Page(/* ... */))->setPages(function (Page $page) {
    //            $page->addPage([
    //                'title'    => 'Blog',
    //                'priority' => 100,
    //                'model'    => \App\Blog::class
    //		      ));
    //
    //		      $page->addPage(\App\Blog::class);
    //	      }),
    //
    //        // or
    //
    //        [
    //            'title'       => 'News',
    //            'priority'    => 300,
    //            'accessLogic' => function ($page) {
    //                return $page->isActive();
    //		      },
    //            'pages'       => [
    //
    //                // ...
    //
    //            ]
    //        ]
    //    ]
    // ]
];
