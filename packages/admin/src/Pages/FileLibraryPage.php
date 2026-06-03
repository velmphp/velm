<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Illuminate\Contracts\Support\Htmlable;
use Velm\Admin\Concerns\ReconcilesVelmModuleUi;
use Velm\Environment;
use Velm\Modules\FileManager\FileManagerService;

final class FileLibraryPage extends VelmShellPage
{
    use ReconcilesVelmModuleUi;
    /** @var array<string, mixed> */
    public array $libraryConfig = [];

    public string $searchQuery = '';

    public function mount(Environment $env): void
    {
        $this->reconcileVelmModuleUi('file_manager');

        $service = new FileManagerService($env);
        $service->ensureInstalled();
        $env->checkAccess('res.attachment.folder', 'read');

        $folderParam = request()->query('folder_id');
        $this->searchQuery = trim((string) request()->query('q', ''));

        if ($folderParam === '0') {
            $active = 0;
            $target = null;
        } elseif ($folderParam !== null && $folderParam !== '') {
            $active = (int) $folderParam;
            $target = $active;
        } else {
            $active = null;
            $target = null;
        }

        $browse = $service->browse($target, '', $this->searchQuery);
        $tree = $service->folderTree();

        $this->libraryConfig = [
            'activeFolderId' => $active,
            'folders' => $tree['folders'],
            'unfiledCount' => $tree['unfiled_count'],
            'files' => $browse['rows'],
            'visibleIds' => array_map(static fn (array $r): int => (int) $r['id'], $browse['rows']),
            'searching' => (bool) ($browse['searching'] ?? false),
            'canWrite' => $env->hasAccess('ir.attachment', 'write'),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return __('File library');
    }

    public function render()
    {
        return view('velm-ui::file-manager.library-page', [
            'searchQuery' => $this->searchQuery,
            'libraryConfig' => $this->libraryConfig,
        ]);
    }
}
