<?php

declare(strict_types=1);

use Velm\Views\Authoring\FormView;
use Velm\Views\Authoring\ListView;
use Velm\Views\Data\ViewsData;

return ViewsData::make()
    ->views(
        ListView::make('tag.list')
            ->model('demo.tag')
            ->title('Tags')
            ->formView('tag.form')
            ->columns(['name']),
        FormView::make('tag.form')
            ->model('demo.tag')
            ->section('main', 'Tag', ['name']),
    );
