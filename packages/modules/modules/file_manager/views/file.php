<?php

declare(strict_types=1);

use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\FormView;
use Velm\Views\Authoring\ListView;
use Velm\Views\Data\ViewsData;

return ViewsData::make()
    ->views(
        ListView::make('file.list')
            ->model('ir.attachment')
            ->title('File library')
            ->formView('file.form')
            ->columns([
                'name',
                'mimetype',
                'file_size',
                'res_model',
                'res_id',
                Field::make('public')->toggle(),
                'created_at',
            ]),
        FormView::make('file.form')
            ->model('ir.attachment')
            ->section('identity', 'File', [
                'name',
                'datas_fname',
                'mimetype',
                'file_size',
                'type',
                Field::make('public')->toggle(),
            ])
            ->section('owner', 'Linked record', [
                'res_model',
                'res_id',
            ])
            ->section('storage', 'Storage', [
                'url',
                'storage_key',
            ])
            ->section('metadata', 'Metadata', [
                'created_at',
                'updated_at',
            ]),
    );
