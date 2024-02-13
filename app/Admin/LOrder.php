<?php

use App\Models\Landing\Order as LOrder;
use SleepingOwl\Admin\Model\ModelConfiguration;

AdminSection::registerModel(LOrder::class, function (ModelConfiguration $model) {
    $model->setAlias('landing-orders');
    $model->onDisplay(function () {
        $display = AdminDisplay::datatablesAsync()->setHtmlAttribute('class', 'table-primary table-hover');
        $display->setColumns(
            AdminColumn::link('name')->setLabel('Імʼя'),
            AdminColumn::text('phone')->setLabel('Телефон'),
            AdminColumn::text('sum')->setLabel('Сумма замовлення (грн)'),
            AdminColumn::datetime('time', 'Дата замовленння')->setFormat('d.m.Y H:i')
        )->paginate(10);
        return $display;
    });

    $model->onCreateAndEdit(function ($id = null) {
        $panel = AdminForm::panel();
            $panel->addHeader(AdminFormElement::columns()
                ->addColumn([
                    AdminFormElement::text('name', 'Імʼя')->required(),
                ], 4)
                ->addColumn([
                    AdminFormElement::text('phone', 'Телефон')->required(),
                ], 4)
                ->addColumn([
                    AdminFormElement::text('address', 'Адреса')->required(),
                ], 4)
                ->addColumn([
                    AdminFormElement::datetime('time', 'Час')->required(),
                ], 4)
                ->addColumn([
                    AdminFormElement::number('sum', 'Сумма замовлення (грн)')->required(),
                ], 2)
                ->addColumn([
                    AdminFormElement::manyToMany('products', [
                        AdminFormElement::number('shop_product_quantity', 'Кількість'),
                    ])->setRelatedElementDisplayName('title')->setLabel('Товар')
                ], 12)



            );
            $panel->getButtons()->hideDeleteButton();
        return $panel;
    });
});
