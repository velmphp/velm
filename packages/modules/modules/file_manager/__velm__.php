<?php

declare(strict_types=1);

use Velm\Modules\FileManager\FileManagerInstallHooks;
use Velm\Modules\FileManager\FileManagerSyncHooks;
use Velm\Modules\FileManager\Models\AttachmentFolder;
use Velm\Modules\FileManager\Models\IrAttachmentExtension;
use Velm\Modules\Manifest;

return Manifest::make('file_manager')
    ->version(0, 1, 0)
    ->depends('base', 'admin')
    ->models(AttachmentFolder::class, IrAttachmentExtension::class)
    ->installHook(FileManagerInstallHooks::class)
    ->syncHook(FileManagerSyncHooks::class)
    ->data(
        'views/file.php',
        'views/folder.php',
        'views/menu.php',
    )
    ->summary('Drive-style file library, folders, and file-picker widgets over ir.attachment.')
    ->category('System')
    ->icon('heroicon-o-folder');
