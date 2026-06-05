<?php

declare(strict_types=1);

namespace Velm\Modules\Dashboard;

final readonly class DashboardWidgetSpec
{
    /**
     * @param  'read'|'write'|'create'|'unlink'  $perm
     */
    public function __construct(
        public string $id,
        public string $module,
        public string $title,
        public string $model,
        public string $view,
        public string $resolver,
        public int $sequence = 10,
        public string $size = 'half',
        public ?string $icon = null,
        public string $perm = 'read',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'module' => $this->module,
            'title' => $this->title,
            'model' => $this->model,
            'view' => $this->view,
            'resolver' => $this->resolver,
            'sequence' => $this->sequence,
            'size' => $this->size,
            'icon' => $this->icon,
            'perm' => $this->perm,
        ];
    }
}
