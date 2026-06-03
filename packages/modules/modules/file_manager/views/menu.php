<?php

declare(strict_types=1);

use Velm\Views\Authoring\Menus;
use Velm\Views\Data\ViewsData;

$m = new Menus('file_manager');

return ViewsData::make()
    ->menus(
        $m->group('files', 'Files')
            ->icon('heroicon-o-folder')
            ->sequence(80)
            ->href('/web/files/library')
            ->children(
                $m->item('files.library', 'Library')
                    ->href('/web/files/library')
                    ->sequence(1),
                $m->item('files.list', 'All files')
                    ->view('file.list')
                    ->sequence(2),
                $m->item('files.folders', 'Folders')
                    ->view('folder.list')
                    ->sequence(3),
            ),
    );
