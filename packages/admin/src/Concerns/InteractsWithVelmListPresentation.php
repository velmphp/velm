<?php

declare(strict_types=1);

namespace Velm\Admin\Concerns;

use Velm\Environment;
use Velm\Views\Authoring\ListRowAction;

trait InteractsWithVelmListPresentation
{
    /**
     * @return array<string, mixed>
     */
    abstract protected function arch(): array;

    abstract protected function openRecordUrl(int $recordId): ?string;

    abstract protected function editRecordUrl(int $recordId): ?string;

    public function listClickToOpen(): bool
    {
        $arch = $this->arch();

        if (array_key_exists('click_to_open', $arch)) {
            return filter_var($arch['click_to_open'], FILTER_VALIDATE_BOOLEAN);
        }

        return $this->listHasOpenTarget();
    }

    protected function listHasOpenTarget(): bool
    {
        return false;
    }

    protected function listHasEditTarget(): bool
    {
        return false;
    }

    /**
     * @return list<array{action: string, label: string, icon: string, href?: string|null}>
     */
    public function listRowActions(): array
    {
        $raw = $this->arch()['row_actions'] ?? [];

        if (! is_array($raw)) {
            $raw = [];
        }

        $actions = [];

        foreach ($raw as $action) {
            if (! is_array($action)) {
                continue;
            }

            $type = (string) ($action['action'] ?? '');
            $label = (string) ($action['label'] ?? '');

            if ($type === '' || $label === '') {
                continue;
            }

            $icon = (string) ($action['icon'] ?? $this->defaultListRowActionIcon($type));

            $actions[] = [
                'action' => $type,
                'label' => $label,
                'icon' => $icon,
                'href' => isset($action['href']) ? (string) $action['href'] : null,
            ];
        }

        $actions = $this->appendDefaultListRowActions($actions);

        return array_values(array_filter(
            $actions,
            fn (array $action): bool => $this->canPerformListRowAction($action),
        ));
    }

    /**
     * @param  list<array{action: string, label: string, icon: string, href?: string|null}>  $actions
     * @return list<array{action: string, label: string, icon: string, href?: string|null}>
     */
    private function appendDefaultListRowActions(array $actions): array
    {
        $types = array_column($actions, 'action');

        if (! in_array('delete', $types, true) && $this->listModelCanUnlink()) {
            $actions[] = ListRowAction::delete()->toArray();
        }

        return $actions;
    }

    private function listModelName(): string
    {
        return (string) ($this->arch()['model'] ?? '');
    }

    private function listModelCanUnlink(): bool
    {
        $model = $this->listModelName();

        return $model !== '' && app(Environment::class)->hasAccess($model, 'unlink');
    }

    /**
     * @param  array{action: string, label: string, icon: string, href?: string|null}  $action
     */
    private function canPerformListRowAction(array $action): bool
    {
        $model = $this->listModelName();

        if ($model === '') {
            return true;
        }

        $env = app(Environment::class);

        return match ($action['action']) {
            'open' => $env->hasAccess($model, 'read') && $this->listHasOpenTarget(),
            'edit' => $env->hasAccess($model, 'write') && $this->listHasEditTarget(),
            'delete' => $env->hasAccess($model, 'unlink'),
            'link' => true,
            default => false,
        };
    }

    private function defaultListRowActionIcon(string $action): string
    {
        return match ($action) {
            'open' => 'heroicon-o-eye',
            'edit' => 'heroicon-o-pencil-square',
            'delete' => 'heroicon-o-trash',
            'link' => 'heroicon-o-arrow-top-right-on-square',
            default => 'heroicon-o-chevron-right',
        };
    }

    public function hasListRowActions(): bool
    {
        return $this->listRowActions() !== [];
    }

    public function listOpenUrl(int $recordId): ?string
    {
        if (! $this->listClickToOpen()) {
            return null;
        }

        return $this->openRecordUrl($recordId);
    }

    /**
     * @param  array{action: string, label: string, icon: string, href?: string|null}  $action
     */
    public function listRowActionUrl(array $action, int $recordId): ?string
    {
        return match ($action['action']) {
            'open' => $this->openRecordUrl($recordId),
            'edit' => $this->editRecordUrl($recordId),
            'link' => $this->resolveListRowLinkHref($action['href'] ?? null, $recordId),
            default => null,
        };
    }

    /**
     * @param  array{action: string, label: string, icon: string, href?: string|null}  $action
     */
    public function listRowActionUsesWire(array $action): bool
    {
        return $action['action'] === 'delete';
    }

    private function resolveListRowLinkHref(?string $href, int $recordId): ?string
    {
        if ($href === null || $href === '') {
            return null;
        }

        return str_contains($href, '{id}')
            ? str_replace('{id}', (string) $recordId, $href)
            : $href;
    }
}
