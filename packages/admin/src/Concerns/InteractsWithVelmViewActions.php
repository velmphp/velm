<?php

declare(strict_types=1);

namespace Velm\Admin\Concerns;

use Velm\Admin\Support\StoredViewRoutes;
use Velm\Environment;
use Velm\Views\Arch\ActionResolver;
use Velm\Views\Arch\ViewActionKey;

trait InteractsWithVelmViewActions
{
    /**
     * @return array<string, mixed>
     */
    abstract protected function arch(): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function velmPageActions(): array
    {
        return $this->resolveViewActions($this->arch()['page_actions'] ?? [], 0, 'page');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function velmHeaderActions(): array
    {
        return $this->resolveViewActions(
            $this->arch()['header_actions'] ?? [],
            $this->velmViewActionsRecordId(),
            'header',
        );
    }

    /**
     * @param  list<mixed>  $raw
     * @return list<array<string, mixed>>
     */
    private function resolveViewActions(array $raw, int $recordId, string $slot): array
    {
        if (! is_array($raw)) {
            return [];
        }

        /** @var list<array<string, mixed>> $actions */
        $actions = app(ActionResolver::class)->resolve(
            $raw,
            app(Environment::class),
            (string) ($this->arch()['model'] ?? ''),
            $recordId,
            $this->velmViewActionsModule(),
            $this->velmViewActionsName(),
        );

        return $this->enrichViewActions($actions, $recordId, $slot);
    }

    /**
     * @param  list<array<string, mixed>>  $actions
     * @return list<array<string, mixed>>
     */
    private function enrichViewActions(array $actions, int $recordId, string $slot): array
    {
        $defaultModule = $this->velmViewActionsModule();

        foreach ($actions as &$action) {
            $inlineForm = is_array($action['form'] ?? null) ? $action['form'] : null;
            $hasInlineForm = is_array($inlineForm) && ($inlineForm['sections'] ?? []) !== [];
            $formView = (string) ($action['form_view'] ?? '');
            $actionKey = (string) ($action['action_key'] ?? ViewActionKey::fromLabel((string) ($action['label'] ?? '')));

            if ($hasInlineForm && $defaultModule !== null && $this->velmViewActionsName() !== null) {
                $query = $recordId > 0 ? '?record='.$recordId : '';
                $action['form_url'] = '/web/view-actions/'.$defaultModule.'/'.$this->velmViewActionsName().'/'.$slot.'/'.$actionKey.'/form'.$query;
                $action['kind'] = 'inline_form';
            } elseif ($formView !== '') {
                $module = (string) ($action['form_module'] ?? $defaultModule ?? '');

                if ($module !== '') {
                    $action['form_url'] = $recordId > 0
                        ? StoredViewRoutes::editPageUrl($module, $formView, $recordId)
                        : StoredViewRoutes::createPageUrl($module, $formView);
                    $action['kind'] = 'form';
                }
            } elseif (strtoupper((string) ($action['method'] ?? 'POST')) === 'GET' && ($action['url'] ?? '') !== '') {
                $action['kind'] = 'get';
            } else {
                $action['kind'] = 'post';
            }
        }

        unset($action);

        return $actions;
    }

    protected function velmViewActionsRecordId(): int
    {
        if (property_exists($this, 'record')) {
            return (int) $this->record;
        }

        return 0;
    }

    protected function velmViewActionsModule(): ?string
    {
        if (property_exists($this, 'module') && is_string($this->module) && $this->module !== '') {
            return $this->module;
        }

        if (method_exists($this, 'velmViewModule')) {
            $module = $this->velmViewModule();

            return is_string($module) && $module !== '' ? $module : null;
        }

        return null;
    }

    protected function velmViewActionsName(): ?string
    {
        if (property_exists($this, 'viewName') && is_string($this->viewName) && $this->viewName !== '') {
            return $this->viewName;
        }

        if (method_exists($this, 'velmViewName')) {
            $viewName = $this->velmViewName();

            return is_string($viewName) && $viewName !== '' ? $viewName : null;
        }

        return null;
    }
}
