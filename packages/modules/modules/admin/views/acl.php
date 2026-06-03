<?php

declare(strict_types=1);

use Velm\Views\Authoring\DetailView;
use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\FormView;
use Velm\Views\Authoring\ListRowAction;
use Velm\Views\Authoring\ListView;
use Velm\Views\Authoring\Menus;
use Velm\Views\Data\ViewsData;

$m = new Menus('admin');

return ViewsData::make()
    ->views(
        ListView::make('group.list')
            ->model('res.groups')
            ->title('Groups')
            ->formView('group.form')
            ->detailView('group.detail')
            ->rowActions([ListRowAction::open(), ListRowAction::edit()])
            ->columns(['name']),
        DetailView::make('group.detail')
            ->model('res.groups')
            ->title('Group')
            ->section('main', 'Group', ['name'])
            ->section('members', 'Members', ['user_ids']),
        FormView::make('group.form')
            ->model('res.groups')
            ->section('main', 'Group', ['name'])
            ->section('members', 'Members', ['user_ids']),
        ListView::make('user.list')
            ->model('res.users')
            ->title('Users')
            ->formView('user.form')
            ->detailView('user.detail')
            ->rowActions([ListRowAction::open(), ListRowAction::edit()])
            ->columns([
                'name',
                'email',
                Field::make('active')->toggle(),
            ]),
        DetailView::make('user.detail')
            ->model('res.users')
            ->title('User')
            ->section('identity', 'Identity', [
                'name',
                'email',
                Field::make('active')->toggle(),
                'company_id',
            ])
            ->section('groups', 'Groups', ['group_ids']),
        FormView::make('user.form')
            ->model('res.users')
            ->section('identity', 'Identity', [
                'name',
                'email',
                'password',
                Field::make('active')->toggle(),
                'company_id',
            ])
            ->section('groups', 'Groups', ['group_ids']),
        ListView::make('access.list')
            ->model('ir.model.access')
            ->title('Model Access')
            ->formView('access.form')
            ->detailView('access.detail')
            ->rowActions([ListRowAction::open(), ListRowAction::edit()])
            ->columns([
                'name',
                'model',
                'group_id',
                Field::make('perm_read')->toggle(),
                Field::make('perm_write')->toggle(),
                Field::make('perm_create')->toggle(),
                Field::make('perm_unlink')->toggle(),
            ]),
        DetailView::make('access.detail')
            ->model('ir.model.access')
            ->title('Access rule')
            ->section('main', 'Access rule', ['name', 'model', 'group_id'])
            ->section('permissions', 'Permissions', [
                Field::make('perm_read')->toggle(),
                Field::make('perm_write')->toggle(),
                Field::make('perm_create')->toggle(),
                Field::make('perm_unlink')->toggle(),
            ]),
        FormView::make('access.form')
            ->model('ir.model.access')
            ->section('main', 'Access rule', ['name', 'model', 'group_id'])
            ->section('permissions', 'Permissions', [
                Field::make('perm_read')->toggle(),
                Field::make('perm_write')->toggle(),
                Field::make('perm_create')->toggle(),
                Field::make('perm_unlink')->toggle(),
            ]),
        ListView::make('rule.list')
            ->model('ir.rule')
            ->title('Record rules')
            ->formView('rule.form')
            ->detailView('rule.detail')
            ->rowActions([ListRowAction::open(), ListRowAction::edit()])
            ->columns([
                'name',
                'model',
                'group_id',
                Field::make('perm_read')->toggle(),
                Field::make('perm_write')->toggle(),
            ]),
        DetailView::make('rule.detail')
            ->model('ir.rule')
            ->title('Record rule')
            ->section('main', 'Record rule', ['name', 'model', 'group_id', 'domain'])
            ->section('permissions', 'Applies on', [
                Field::make('perm_read')->toggle(),
                Field::make('perm_write')->toggle(),
                Field::make('perm_create')->toggle(),
                Field::make('perm_unlink')->toggle(),
            ]),
        FormView::make('rule.form')
            ->model('ir.rule')
            ->section('main', 'Record rule', ['name', 'model', 'group_id', 'domain'])
            ->section('permissions', 'Applies on', [
                Field::make('perm_read')->toggle(),
                Field::make('perm_write')->toggle(),
                Field::make('perm_create')->toggle(),
                Field::make('perm_unlink')->toggle(),
            ]),
    )
    ->menus(
        $m->group('settings', 'Settings')
            ->icon('heroicon-o-building-office')
            ->sequence(80)
            ->children(
                $m->group('settings.organization', 'Organization')
                    ->sequence(10)
                    ->children(
                        $m->item('settings.companies', 'Companies')
                            ->view('company.list', 'base')
                            ->sequence(10),
                    ),
                $m->group('settings.access', 'Users & access')
                    ->sequence(20)
                    ->children(
                        $m->item('settings.users', 'Users')
                            ->view('user.list')
                            ->sequence(10),
                        $m->item('settings.groups', 'Groups')
                            ->view('group.list')
                            ->sequence(20),
                    ),
            ),
        $m->group('security', 'Security')
            ->icon('heroicon-o-clipboard-document-list')
            ->sequence(90)
            ->children(
                $m->group('security.permissions', 'Permissions')
                    ->sequence(10)
                    ->children(
                        $m->item('security.access', 'Model access')
                            ->view('access.list')
                            ->sequence(10),
                        $m->item('security.rules', 'Record rules')
                            ->view('rule.list')
                            ->sequence(20),
                    ),
            ),
    );
