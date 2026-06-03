<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Illuminate\Contracts\Support\Htmlable;
use Velm\Environment;
use Velm\Modules\FileManager\FileManagerService;
final class FilePropertiesPage extends VelmShellPage
{
    public int $attId = 0;

    /** @var array<string, mixed> */
    public array $properties = [];

    public function mount(int $attId, Environment $env): void
    {
        $this->attId = $attId;
        $service = new FileManagerService($env);
        $service->ensureInstalled();
        $this->properties = $service->propertiesViewData($attId);
    }

    public function getTitle(): string|Htmlable
    {
        return (string) ($this->properties['att']['name'] ?? __('Properties'));
    }

    public function render()
    {
        return view('velm-ui::file-manager.properties-page', $this->properties);
    }
}
