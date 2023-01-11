<?php

use App\Models\Shop\Question;
use SleepingOwl\Admin\Model\ModelConfiguration;
use Illuminate\Support\Str;

AdminSection::registerModel(Question::class, function (ModelConfiguration $model) {
    $model->onDisplay(function () {
        $display = AdminDisplay::datatablesAsync()->setHtmlAttribute('class', 'table-primary table-hover');
        $display->setColumns(
            AdminColumn::link('name')->setLabel('Імʼя'),
            AdminColumn::text('email')->setLabel('Email'),
            AdminColumn::text('question')->setLabel('Повідомленя')
        )->paginate(10);
        return $display;
    });

    $model->onCreateAndEdit(function ($id = null) {
        $panel = AdminForm::panel();
            $panel->addHeader(AdminFormElement::columns()
                ->addColumn([
                    AdminFormElement::text('name', 'Імʼя')->required(),
                ], 5)
                ->addColumn([
                    AdminFormElement::text('email', 'Email'),
                ], 5)
                ->addColumn([
                    AdminFormElement::ckeditor('question', 'Повідомленя'),
                ], 12)
            );
            $panel->getButtons()->hideDeleteButton();
        return $panel;
    });
});
