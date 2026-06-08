<?php

declare(strict_types=1);

use Velm\Modules\FileManager\FileManagerInstallHooks;
use Velm\Modules\FileManager\FileManagerSyncHooks;
use Velm\Modules\FileManager\Models\AttachmentFolder;
use Velm\Modules\FileManager\Models\IrAttachmentExtension;
use Velm\Modules\Manifest;

return Manifest::make('file_manager')
    ->version(0, 4, 0)
    ->depends('base', 'admin')
    ->models(AttachmentFolder::class, IrAttachmentExtension::class)
    ->installHook(FileManagerInstallHooks::class)
    ->syncHook(FileManagerSyncHooks::class)
    ->data(
        'views/file.php',
        'views/folder.php',
        'views/menu.php',
    )
    ->summary('Core file library, folders, and attachment pickers over ir.attachment.')
    ->category('Core')
    ->icon('heroicon-o-folder');
