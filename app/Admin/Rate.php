<?php

use App\Models\Rate;
use App\Models\Role;
use SleepingOwl\Admin\Model\ModelConfiguration;

AdminSection::registerModel(Rate::class, function (ModelConfiguration $model) {
    $model->setTitle('Рейты ЗП');
    $model->onDisplay(function () {

        $display = AdminDisplay::datatablesAsync()->setHtmlAttribute('class', 'table-primary table-hover');
        $display->with('role');
        $display->setColumns(
            AdminColumn::text('id')->setLabel('ID Юзера')->setWidth('100px')->setHtmlAttribute('class', 'text-right'),
            AdminColumn::link('rate')->setLabel('Сумма для деления на группу')->setWidth('200px'),
            AdminColumn::text('working_date')->setLabel('Рабочий день')->setWidth('200px'),
            AdminColumn::text('role.label', 'Группа')->setWidth('200px')
        )->paginate(10);
        return $display;
    });
    $model->onCreateAndEdit(function ($id = null) {
        $panel = AdminForm::panel()
            ->addBody(
                AdminFormElement::number('rate', 'Сумма для деления на группу')->required(),
                AdminFormElement::date('working_date', 'Рабочий день')->required(),
                AdminFormElement::select('role_id', 'Роли', Role::class)->setDisplay('label'),
            );
        $panel->getButtons()->hideDeleteButton();
        return $panel;
    });
});
