<?php

use App\Models\WorkingHours;
use App\Models\User;
use SleepingOwl\Admin\Model\ModelConfiguration;

AdminSection::registerModel(WorkingHours::class, function (ModelConfiguration $model) {
    $model->setTitle('Отработаные часы');
    $model->onDisplay(function () {

        $display = AdminDisplay::datatablesAsync()->setHtmlAttribute('class', 'table-primary table-hover');
        $display->with('user');
        $display->setColumns(
            AdminColumn::link('hours')->setLabel('Отработанные часы')->setWidth('200px'),
            AdminColumn::text('working_day')->setLabel('Рабочий день')->setWidth('200px'),
            AdminColumn::text('user.name', 'Имя Сотрудника')->setWidth('200px'),
            AdminColumn::text('user.email', 'Логин Сотрудника')->setWidth('200px')
        )->paginate(10);
        return $display;
    });
    $model->onCreateAndEdit(function ($id = null) {
        $panel = AdminForm::panel()
            ->addBody(
                AdminFormElement::number('hours', 'Отработанные часы')->setStep(0.1)->required(),
                AdminFormElement::date('working_day', 'Рабочий день')->required(),
                AdminFormElement::select('user_id', 'Пользователи', User::class)->setDisplay('name'),
            );
        $panel->getButtons()->hideDeleteButton();
        return $panel;
    });
});
