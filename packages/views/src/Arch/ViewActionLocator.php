<?php

declare(strict_types=1);

namespace Velm\Views\Arch;

use Velm\Environment;
use Velm\Views\ViewRegistry;

final class ViewActionLocator
{
    public function __construct(
        private readonly ViewRegistry $views = new ViewRegistry,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function find(
        Environment $env,
        string $module,
        string $viewName,
        string $slot,
        string $actionKey,
    ): ?array {
        $arch = $this->views->arch($env, $module, $viewName);
        $list = match ($slot) {
            'page', 'page_actions' => $arch['page_actions'] ?? [],
            'header', 'header_actions' => $arch['header_actions'] ?? [],
            default => [],
        };

        if (! is_array($list)) {
            return null;
        }

        foreach ($list as $action) {
            if (! is_array($action)) {
                continue;
            }

            $label = (string) ($action['label'] ?? '');

            if ($label === '') {
                continue;
            }

            if (ViewActionKey::fromLabel($label) === $actionKey) {
                return $action;
            }
        }

        return null;
    }
}
