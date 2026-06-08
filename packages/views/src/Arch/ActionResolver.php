<?php

declare(strict_types=1);

namespace Velm\Views\Arch;

use Velm\Environment;
use Velm\Views\Authoring\ActionVariant;

/**
 * Resolves arch-declared toolbar actions with ACL gating and URL substitution.
 */
final class ActionResolver
{
    /**
     * @param  list<array<string, mixed>>  $actions
     * @return list<array<string, mixed>>
     */
    public function resolve(
        array $actions,
        Environment $env,
        string $model,
        int $recordId = 0,
        ?string $viewModule = null,
        ?string $viewName = null,
    ): array {
        $resolved = [];

        foreach ($actions as $action) {
            if (! is_array($action)) {
                continue;
            }

            $label = (string) ($action['label'] ?? '');

            if ($label === '') {
                continue;
            }

            $targetModel = (string) ($action['model'] ?? $model);
            $perm = isset($action['perm']) ? (string) $action['perm'] : '';

            if ($perm !== '' && ! $env->hasAccess($targetModel, $perm)) {
                continue;
            }

            $url = str_replace('{id}', (string) $recordId, (string) ($action['url'] ?? ''));
            $formView = (string) ($action['form_view'] ?? '');
            $inlineForm = is_array($action['form'] ?? null) ? $action['form'] : null;
            $hasInlineForm = is_array($inlineForm) && ($inlineForm['sections'] ?? []) !== [];
            $method = strtoupper((string) ($action['method'] ?? ($formView !== '' || $hasInlineForm ? 'GET' : 'POST')));
            $confirm = (string) ($action['confirm'] ?? '');
            $fullPage = filter_var($action['full_page'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $variant = ActionVariant::tryFrom(strtolower((string) ($action['variant'] ?? '')))
                ?? ActionVariant::Secondary;

            if (! $fullPage && $method === 'GET' && ! $hasInlineForm && $formView === '' && $viewModule !== null && $viewName !== null && $url !== '') {
                $fullPage = ! str_starts_with($url, '/velm/views/'.$viewModule.'/'.$viewName)
                    && ! str_starts_with($url, '/web/views/'.$viewModule.'/'.$viewName);
            }

            $resolvedAction = [
                'label' => $label,
                'action_key' => ViewActionKey::fromLabel($label),
                'url' => $url,
                'method' => $method,
                'confirm' => $confirm,
                'full_page' => $fullPage,
                'variant' => $variant->value,
            ];

            if ($targetModel !== '') {
                $resolvedAction['model'] = $targetModel;
            }

            if ($formView !== '') {
                $resolvedAction['form_view'] = $formView;
            }

            $formModule = (string) ($action['form_module'] ?? '');

            if ($formModule !== '') {
                $resolvedAction['form_module'] = $formModule;
            }

            if ($hasInlineForm) {
                $resolvedAction['form'] = $inlineForm;
            }

            $wire = (string) ($action['wire'] ?? '');

            if ($wire !== '') {
                $resolvedAction['wire'] = $wire;
            }

            $resolved[] = $resolvedAction;
        }

        return $resolved;
    }
}
