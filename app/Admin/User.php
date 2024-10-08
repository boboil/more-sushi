<?php

use App\Models\User;
use App\Models\Role;
use SleepingOwl\Admin\Model\ModelConfiguration;

AdminSection::registerModel(User::class, function (ModelConfiguration $model) {
    $model->setTitle('Юзеры');
    $model->onDisplay(function () {

        $display = AdminDisplay::datatablesAsync()->setHtmlAttribute('class', 'table-primary table-hover');
        $display->with('roles');
        $display->setColumnFilters([
            null,
            AdminColumnFilter::text('name')->setPlaceholder('Имя'),
            null,
            AdminColumnFilter::text('roles')->setPlaceholder('Роли'),
        ]);
        $display->setColumns(
            AdminColumn::text('id')->setLabel('ID Юзера')->setWidth('100px')->setHtmlAttribute('class', 'text-right'),
            AdminColumn::link('name')->setLabel('Имя')->setWidth('200px'),
            AdminColumn::email('email', 'Email')->setWidth('150px'),
            AdminColumn::text('rate', 'Ставка в час UAH')->setWidth('150px'),
            AdminColumn::lists('roles.label', 'Роли')->setWidth('200px')
        )->paginate(10);
        return $display;
    });
    $model->onCreateAndEdit(function ($id = null) {
        $panel = AdminForm::panel()
            ->addBody(
                AdminFormElement::text('name', 'Username')->required(),
                AdminFormElement::password('password', 'Password')->required()->addValidationRule('min:6'),
                AdminFormElement::text('email', 'E-mail')->required()->addValidationRule('email'),
                AdminFormElement::number('rate', 'Ставка в час UAH')->setStep(0.1)->required(),
                AdminFormElement::number('poster_user_id', 'ИД пользователя в Poster')->required(),
                AdminFormElement::multiselect('roles', 'Роли', Role::class)->setDisplay('label'),
            );
        $panel->getButtons()->hideDeleteButton();
        return $panel;
    });
});
