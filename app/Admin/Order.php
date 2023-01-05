<?php

use App\Models\Shop\Order;
use SleepingOwl\Admin\Model\ModelConfiguration;
use Illuminate\Support\Str;

AdminSection::registerModel(Order::class, function (ModelConfiguration $model) {
    $model->onDisplay(function () {
        $display = AdminDisplay::datatablesAsync()->setHtmlAttribute('class', 'table-primary table-hover');
        $display->setColumns(
            AdminColumn::link('customer_name')->setLabel('Імʼя'),
            AdminColumn::text('customer_phone')->setLabel('Телефон'),
            AdminColumn::text('customer_delivery_type')->setLabel('Доставка'),
            AdminColumn::text('online_payment')->setLabel('Оплата')
        )->paginate(10);
        return $display;
    });

    $model->onCreateAndEdit(function ($id = null) {
        $panel = AdminForm::panel();
            $panel->addHeader(AdminFormElement::columns()
                ->addColumn([
                    AdminFormElement::text('customer_name', 'Імʼя')->required(),
                ], 4)
                ->addColumn([
                    AdminFormElement::text('customer_phone', 'Телефон')->required(),
                ], 4)
                ->addColumn([
                    AdminFormElement::text('customer_delivery_type', 'Доставка')->required(),
                ], 4)
                ->addColumn([
                    AdminFormElement::text('online_payment', 'Оплата')->required(),
                ], 4)
                ->addColumn([
                    AdminFormElement::text('customer_street', 'Вулиця')->required(),
                ], 4)
                ->addColumn([
                    AdminFormElement::text('customer_building', 'Будинок')->required(),
                ], 4)
                ->addColumn([
                    AdminFormElement::number('sticks_standard', 'Стандартні палички')->required(),
                ], 2)
                ->addColumn([
                    AdminFormElement::number('sticks_educational', 'Учбові палички')->required(),
                ], 2)
                ->addColumn([
                    AdminFormElement::datetime('time', 'Час')->required(),
                ], 4)
                ->addColumn([
                    AdminFormElement::checkbox('is_as_soon_as_possible', 'Як умога раніше')->required(),
                ], 2)
                ->addColumn([
                    AdminFormElement::manyToMany('products', [
                        AdminFormElement::text('shop_product_quantity', 'Кількість'),
                    ])->setRelatedElementDisplayName('title')->setLabel('Товар')
                ], 12)



            );
            $panel->getButtons()->hideDeleteButton();
        return $panel;
    });
});
