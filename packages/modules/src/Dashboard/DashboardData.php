<?php

declare(strict_types=1);

namespace Velm\Modules\Dashboard;

/**
 * Fluent builder for module {@see dashboard.php} manifests.
 *
 * @example
 * return DashboardData::make('workflow')
 *     ->widget(
 *         id: 'workflow_pending_approvals',
 *         title: 'Pending approvals',
 *         model: 'workflow.approval',
 *         view: 'velm-ui::dashboard.list-card',
 *         resolver: PendingApprovalsWidget::class.'::resolve',
 *     );
 */
final class DashboardData
{
    /** @var list<DashboardWidgetSpec> */
    private array $widgets = [];

    private function __construct(
        private readonly string $module,
    ) {}

    public static function make(string $module): self
    {
        if ($module === '') {
            throw new \InvalidArgumentException('Dashboard module name must not be empty.');
        }

        return new self($module);
    }

    /**
     * @param  'read'|'write'|'create'|'unlink'  $perm
     * @param  'half'|'full'|'third'  $size
     */
    public function widget(
        string $id,
        string $title,
        string $model,
        string $view,
        string $resolver,
        int $sequence = 10,
        string $size = 'half',
        ?string $icon = null,
        string $perm = 'read',
    ): self {
        if ($id === '' || $title === '' || $model === '' || $view === '' || $resolver === '') {
            throw new \InvalidArgumentException('Dashboard widget id, title, model, view, and resolver are required.');
        }

        if (! in_array($size, ['half', 'full', 'third'], true)) {
            throw new \InvalidArgumentException("Invalid dashboard widget size: {$size}");
        }

        if (! in_array($perm, ['read', 'write', 'create', 'unlink'], true)) {
            throw new \InvalidArgumentException("Invalid dashboard widget perm: {$perm}");
        }

        $this->widgets[] = new DashboardWidgetSpec(
            id: $id,
            module: $this->module,
            title: $title,
            model: $model,
            view: $view,
            resolver: $resolver,
            sequence: $sequence,
            size: $size,
            icon: $icon,
            perm: $perm,
        );

        return $this;
    }

    /**
     * @return list<DashboardWidgetSpec>
     */
    public function widgets(): array
    {
        return $this->widgets;
    }
}
