<?php

use App\Models\Shop\Category;
use SleepingOwl\Admin\Model\ModelConfiguration;
use Illuminate\Support\Str;

AdminSection::registerModel(Category::class, function (ModelConfiguration $model) {
    $model->onDisplay(function () {
        $display = AdminDisplay::datatablesAsync()->setHtmlAttribute('class', 'table-primary table-hover');
        $display->setColumns(
            AdminColumn::link('title')->setLabel('Назва'),
            AdminColumn::image('image')->setLabel('Зображення'),
            AdminColumnEditable::checkbox('enable', 'Так', 'Ні')->setLabel('Активна'),
        )->paginate(10);
        return $display;
    });
    $model->created(function(ModelConfiguration $model, Category $category) {
        $category->slug = Str::slug($category->title, '-');
        $category->save();
    });
    $model->updating(function(ModelConfiguration $model, Category $category) {
        $category->slug = Str::slug($category->title, '-');
        $category->save();
    });
    $model->onCreateAndEdit(function ($id = null) {
        $panel = AdminForm::panel();
            $panel->addHeader(AdminFormElement::columns()
                ->addColumn([
                    AdminFormElement::text('title', 'Назва')->required(),
                ], 5)
                ->addColumn([
                    AdminFormElement::text('meta_title', 'Мета Заголовок'),
                ], 5)
                ->addColumn([
                    AdminFormElement::text('meta_description', 'Мета Опис'),
                ], 5)
                ->addColumn([
                    AdminFormElement::image('image', 'Зображення'),
                ], 5)
                ->addColumn([
                    AdminFormElement::ckeditor('description', 'Опис Категорії'),
                ], 12)
            );
            $panel->getButtons()->hideDeleteButton();
        return $panel;
    });
});
