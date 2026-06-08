<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

/**
 * Declares a toolbar action on list (page_actions) or detail (header_actions) views.
 *
 * @phpstan-type ViewActionArray array{
 *     label: string,
 *     url?: string,
 *     method?: string,
 *     confirm?: string,
 *     perm?: string,
 *     model?: string,
 *     policy?: string,
 *     full_page?: bool,
 *     variant?: string,
 *     form_view?: string,
 *     form_module?: string,
 *     form?: array{sections: list<array<string, mixed>>, cols?: int, model?: string}
 * }
 */
final class Action
{
    private string $label = '';

    private ?string $url = null;

    private ?string $method = null;

    private ?string $confirm = null;

    private ?string $perm = null;

    private ?string $model = null;

    private ?string $policy = null;

    private ?bool $fullPage = null;

    private ?ActionVariant $variant = null;

    private ?string $formView = null;

    private ?string $formModule = null;

    /** @var array{sections: list<array<string, mixed>>, cols?: int, model?: string}|null */
    private ?array $form = null;

    private function __construct(string $label)
    {
        $this->label = $label;
    }

    public static function make(string $label): self
    {
        return new self($label);
    }

    public function url(string $url): self
    {
        $clone = clone $this;
        $clone->url = $url;

        return $clone;
    }

    public function method(string $method): self
    {
        $clone = clone $this;
        $clone->method = strtoupper($method);

        return $clone;
    }

    public function confirm(string $confirm): self
    {
        $clone = clone $this;
        $clone->confirm = $confirm;

        return $clone;
    }

    public function perm(string $perm): self
    {
        $clone = clone $this;
        $clone->perm = $perm;

        return $clone;
    }

    public function model(string $model): self
    {
        $clone = clone $this;
        $clone->model = $model;

        return $clone;
    }

    public function policy(string $policy): self
    {
        $clone = clone $this;
        $clone->policy = $policy;

        return $clone;
    }

    public function fullPage(bool $fullPage = true): self
    {
        $clone = clone $this;
        $clone->fullPage = $fullPage;

        return $clone;
    }

    public function variant(ActionVariant $variant): self
    {
        $clone = clone $this;
        $clone->variant = $variant;

        return $clone;
    }

    /**
     * Open the given stored form view in the record dialog (create on lists, edit on detail).
     */
    public function formView(string $formView, ?string $module = null): self
    {
        $clone = clone $this;
        $clone->formView = $formView;

        if ($module !== null && $module !== '') {
            $clone->formModule = $module;
        }

        return $clone;
    }

    /**
     * Inline form schema rendered in the action dialog (no module view required).
     *
     * @param  callable(ActionForm): ActionForm|ActionForm  $form
     */
    public function form(callable|ActionForm $form): self
    {
        $schema = $form instanceof ActionForm ? $form : $form(ActionForm::make());
        $arch = $schema->toArray();

        if (($arch['sections'] ?? []) === []) {
            throw new \InvalidArgumentException("Action {$this->label} inline form requires at least one section.");
        }

        $clone = clone $this;
        $clone->form = $arch;

        if ($clone->model === null && isset($arch['model']) && is_string($arch['model']) && $arch['model'] !== '') {
            $clone->model = $arch['model'];
        }

        return $clone;
    }

    /**
     * @return ViewActionArray
     */
    public function toArray(): array
    {
        if ($this->label === '') {
            throw new \LogicException('View action requires a label.');
        }

        $hasInlineForm = $this->form !== null && ($this->form['sections'] ?? []) !== [];
        $hasStoredForm = $this->formView !== null && $this->formView !== '';
        $hasUrl = $this->url !== null && $this->url !== '';

        if (! $hasInlineForm && ! $hasStoredForm && ! $hasUrl) {
            throw new \LogicException("Action {$this->label} requires url(), formView(), or form().");
        }

        if ($hasInlineForm && ($this->model === null || $this->model === '')) {
            throw new \LogicException("Action {$this->label} inline form requires model() on the action or ActionForm.");
        }

        $action = ['label' => $this->label];

        if ($hasUrl) {
            $action['url'] = $this->url;
        }

        if ($this->method !== null && $this->method !== '') {
            $action['method'] = $this->method;
        }

        if ($this->confirm !== null && $this->confirm !== '') {
            $action['confirm'] = $this->confirm;
        }

        if ($this->perm !== null && $this->perm !== '') {
            $action['perm'] = $this->perm;
        }

        if ($this->model !== null && $this->model !== '') {
            $action['model'] = $this->model;
        }

        if ($this->policy !== null && $this->policy !== '') {
            $action['policy'] = $this->policy;
        }

        if ($this->fullPage !== null) {
            $action['full_page'] = $this->fullPage;
        }

        if ($this->variant !== null) {
            $action['variant'] = $this->variant->value;
        }

        if ($hasStoredForm) {
            $action['form_view'] = $this->formView;
        }

        if ($this->formModule !== null && $this->formModule !== '') {
            $action['form_module'] = $this->formModule;
        }

        if ($hasInlineForm) {
            $action['form'] = $this->form;
        }

        return $action;
    }
}
