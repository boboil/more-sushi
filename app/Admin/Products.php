<?php

use App\Models\Shop\Product;
use SleepingOwl\Admin\Model\ModelConfiguration;
use Illuminate\Support\Str;

AdminSection::registerModel(Product::class, function (ModelConfiguration $model) {
    $model->onDisplay(function () {
        $display = AdminDisplay::datatablesAsync()->setHtmlAttribute('class', 'table-primary table-hover')
            ->setActions([
                AdminColumn::action('import', 'Синхронизировать с постером')->usePost()->setAction(route('import.products')),
                AdminColumn::action('delete', 'Видалити обрані')->usePost()->setAction(route('delete.products')),
            ]);
        $display->setColumnFilters([
            null,
            AdminColumnFilter::text('title')->setPlaceholder('Назва')->setOperator('contains')
        ]);
        $display->setColumns(
            AdminColumn::checkbox(),
            AdminColumn::link('title')->setLabel('Назва'),
            AdminColumn::text('price')->setLabel('Ціна'),
            AdminColumn::text('discount')->setLabel('Скидка'),
            AdminColumn::image('main_image')->setLabel('Картинка'),
            AdminColumnEditable::checkbox('stock', 'Так', 'Ні')->setLabel('Акція'),
            AdminColumnEditable::checkbox('latest', 'Так', 'Ні')->setLabel('Новинка'),
            AdminColumnEditable::checkbox('isRelated', 'Так', 'Ні')->setLabel('Супутній товар')
        )->paginate(10);
        return $display;
    });
    $model->creating(function(ModelConfiguration $model, Product $product) {
        $product->slug = Str::slug($_POST['title'], '-');
        $product->title = $_POST['title'];
    });
    $model->created(function(ModelConfiguration $model, Product $product) {
        $product->slug = Str::slug($product->title, '-');
        $product->save();
    });
    $model->updating(function(ModelConfiguration $model, Product $product) {
        $product->slug = Str::slug($product->title, '-');
        $product->save();
    });
    $model->onCreateAndEdit(function ($id = null) {
        $panel = AdminForm::panel();
            $panel->addHeader(AdminFormElement::columns()
                ->addColumn([
                    AdminFormElement::text('title', 'Назва')->required(),
                ], 4)
                ->addColumn([
                    AdminFormElement::text('price', 'Ціна')->required(),
                    AdminFormElement::text('discount', 'Скидка'),
                ], 4)
                ->addColumn([
                    AdminFormElement::image('main_image', 'Картинка')->required(),
                ], 4)
                ->addColumn([
                    AdminFormElement::images('images', 'Зображення товару')->setUploadSettings([
                        'orientate' => [],
                        'resize' => [555, null, function ($constraint) {
                            $constraint->aspectRatio();
                        }]
                    ])->storeAsJson(),
                ], 12)
                ->addColumn([
                    AdminFormElement::ckeditor('consist', 'Склад'),
                ], 12)
                ->addColumn([
                    AdminFormElement::ckeditor('description', 'Опис'),
                ], 12)
                ->addColumn([
                    AdminFormElement::text('weight', 'Вага г.')->required(),
                ], 4)
                ->addColumn([
                    AdminFormElement::text('count', 'Кількість шт.')->required(),
                ], 4)
                ->addColumn([
                    AdminFormElement::multiselect('category', 'Категорія', \App\Models\Shop\Category::class),
                ], 4)


            );
            $panel->getButtons()->hideDeleteButton();
        return $panel;
    });
});
