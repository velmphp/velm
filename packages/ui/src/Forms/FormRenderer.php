<?php

declare(strict_types=1);

namespace Velm\Ui\Forms;

use Velm\Environment;
use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\Field;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2manyField;
use Velm\Fields\Many2oneField;
use Velm\Fields\One2manyField;
use Velm\Fields\TextField;
use Velm\Ui\Support\RelationalInitials;
use Velm\Ui\Support\ViewUrlResolver;
use Velm\Ui\Widgets\WidgetContext;
use Velm\Ui\Widgets\WidgetRegistry;
use Velm\Views\Arch\ArchNormalizer;
use Velm\Web\Api\Many2oneQuickCreate;

final class FormRenderer
{
    public function __construct(
        private readonly WidgetRegistry $widgets = new WidgetRegistry,
    ) {}

    /**
     * @param  array<string, mixed>  $arch  Resolved form arch (model, sections, …)
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $fieldErrors
     * @return list<FormSectionView>
     */
    public function sections(
        array $arch,
        Environment $env,
        FormMode $mode,
        array $data,
        array $fieldErrors = [],
        ?int $parentRecordId = null,
        ?string $viewModule = null,
        ?string $viewName = null,
    ): array {
        $arch = self::normalizeArch($arch);
        $model = (string) ($arch['model'] ?? '');
        $formCols = (int) ($arch['cols'] ?? 2);
        $sections = [];

        foreach ($arch['sections'] as $section) {
            if (! is_array($section)) {
                continue;
            }

            $pagesSpec = $section['pages'] ?? null;

            if (is_array($pagesSpec) && $pagesSpec !== []) {
                $pages = [];
                foreach ($pagesSpec as $pageSpec) {
                    if (! is_array($pageSpec)) {
                        continue;
                    }
                    $pageCols = (int) ($pageSpec['cols'] ?? $section['cols'] ?? $formCols);
                    $cells = $this->cellsForFields(
                        $pageSpec['fields'] ?? [],
                        $env,
                        $model,
                        $mode,
                        $data,
                        $fieldErrors,
                        $parentRecordId,
                    );
                    $pages[] = new FormNotebookPage(
                        name: (string) ($pageSpec['name'] ?? 'page'),
                        title: (string) ($pageSpec['title'] ?? $pageSpec['name'] ?? 'Page'),
                        cells: $cells,
                        cols: $pageCols,
                    );
                }

                $nbName = (string) ($section['name'] ?? 'notebook');
                $storageKey = 'pv-nb-'.($viewModule ?? 'velm').'-'.($viewName ?? 'form').'-'.$nbName;
                $sections[] = FormSectionView::notebook(
                    $nbName,
                    (string) ($section['title'] ?? ''),
                    $pages,
                    $storageKey,
                    (int) ($section['cols'] ?? $formCols),
                );

                continue;
            }

            $sectionCols = (int) ($section['cols'] ?? $formCols);
            $cells = $this->cellsForFields(
                $section['fields'] ?? [],
                $env,
                $model,
                $mode,
                $data,
                $fieldErrors,
                $parentRecordId,
            );

            $sections[] = FormSectionView::section(
                (string) ($section['name'] ?? 'section'),
                (string) ($section['title'] ?? ''),
                $cells,
                $sectionCols,
            );
        }

        return $sections;
    }

    /**
     * @param  list<mixed>  $fields
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $fieldErrors
     * @return list<FormCell>
     */
    private function cellsForFields(
        array $fields,
        Environment $env,
        string $model,
        FormMode $mode,
        array $data,
        array $fieldErrors,
        ?int $parentRecordId,
    ): array {
        $cells = [];

        foreach ($fields as $fieldSpec) {
            if (! is_array($fieldSpec) || ! isset($fieldSpec['name'])) {
                continue;
            }

            $cells[] = $this->cell($fieldSpec, $env, $model, $mode, $data, $fieldErrors, $parentRecordId);
        }

        return $cells;
    }

    /**
     * @param  array<string, mixed>  $fieldSpec
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $fieldErrors
     */
    private function cell(
        array $fieldSpec,
        Environment $env,
        string $model,
        FormMode $mode,
        array $data,
        array $fieldErrors,
        ?int $parentRecordId,
    ): FormCell {
        $name = (string) $fieldSpec['name'];
        $velmField = $model !== '' ? $env->registry->field($model, $name) : null;
        $label = is_string($fieldSpec['label'] ?? null) && $fieldSpec['label'] !== ''
            ? (string) $fieldSpec['label']
            : ($velmField?->displayLabel() ?? Field::humanizeFieldName($name));
        $required = $velmField?->required === true && $mode !== FormMode::Display;
        $wide = (bool) ($fieldSpec['wide'] ?? false);
        $colspan = $wide ? 1 : max(1, (int) ($fieldSpec['colspan'] ?? 1));
        $error = $fieldErrors[$name] ?? null;

        $ctx = new WidgetContext($env, $model, $fieldSpec, $mode, $data, $error);
        $widgetView = $this->widgets->resolve($ctx);
        $props = $this->widgetProps($ctx, $velmField, $parentRecordId);

        return new FormCell(
            name: $name,
            label: $label,
            widget: $widgetView,
            widgetProps: $props,
            required: $required,
            error: $error,
            colspan: $colspan,
            wide: $wide,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function widgetProps(WidgetContext $ctx, ?\Velm\Fields\Field $velmField, ?int $parentRecordId): array
    {
        $props = [
            'name' => $ctx->fieldName(),
            'wireKey' => $ctx->wireKey(),
            'readonly' => $velmField?->readonly === true || $ctx->mode === FormMode::Display,
            'required' => $velmField?->required === true,
            'value' => $ctx->value(),
            'mode' => $ctx->mode->value,
        ];

        $widgetHint = isset($ctx->spec['widget']) && is_string($ctx->spec['widget'])
            ? $ctx->spec['widget']
            : null;

        if ($velmField instanceof Many2oneField && $widgetHint === 'file') {
            $props = array_merge($props, $this->attachmentPickerProps($ctx, false));
        } elseif ($velmField instanceof Many2oneField) {
            $props = array_merge($props, $this->many2oneProps($ctx, $velmField));
        }

        if ($velmField instanceof Many2manyField && $widgetHint === 'files') {
            $props = array_merge($props, $this->attachmentPickerProps($ctx, true));
        } elseif ($velmField instanceof Many2manyField) {
            $props = array_merge($props, $this->many2manyProps($ctx, $velmField));
        }

        if ($velmField instanceof One2manyField) {
            $props = array_merge($props, $this->one2manyProps($ctx, $velmField, $parentRecordId));
        }

        $whenEmptyUse = $ctx->spec['when_empty_use'] ?? null;
        if (is_string($whenEmptyUse) && $whenEmptyUse !== '') {
            $props['fallbackWireKey'] = 'data.'.$whenEmptyUse;
        }

        $codeLanguage = $ctx->spec['code_language'] ?? null;
        if (is_string($codeLanguage) && $codeLanguage !== '') {
            $props['codeLanguage'] = $codeLanguage;
        }

        $placeholder = $ctx->spec['placeholder'] ?? null;
        if (is_string($placeholder) && $placeholder !== '') {
            $props['placeholder'] = $placeholder;
        }

        return $props;
    }

    /**
     * @return array<string, mixed>
     */
    private function attachmentPickerProps(WidgetContext $ctx, bool $multi): array
    {
        $accept = is_string($ctx->spec['accept'] ?? null) ? (string) $ctx->spec['accept'] : '';

        return [
            'multi' => $multi,
            'accept' => $accept,
            'initial' => RelationalInitials::attachmentChips($ctx->env, $ctx->value(), $multi),
            'pickerTitle' => $multi ? __('Pick files') : __('Pick a file'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function many2oneProps(WidgetContext $ctx, Many2oneField $field): array
    {
        $comodel = $field->comodel;
        $value = $ctx->value();
        $initialId = $value !== null && $value !== '' ? (int) $value : null;
        $initialLabel = '';

        if ($initialId !== null) {
            $rows = $ctx->env->browse($comodel, [$initialId])->read();
            $initialLabel = (string) ($rows[0]['display_name'] ?? $initialId);
        }

        $canQuickCreate = false;
        if ($ctx->mode === FormMode::Edit || $ctx->mode === FormMode::New) {
            try {
                $canQuickCreate = app(Many2oneQuickCreate::class)->canQuickCreate(
                    $ctx->env->registry->modelClass($comodel),
                );
            } catch (\Throwable) {
                $canQuickCreate = false;
            }
        }

        $formViewUrl = ViewUrlResolver::recordViewUrlForModel($ctx->env, $comodel, $ctx->mode);

        return [
            'comodel' => $comodel,
            'searchUrl' => route('velm.api.m2o.search', ['model' => $comodel]),
            'initialId' => $initialId,
            'initialLabel' => $initialLabel,
            'canQuickCreate' => $canQuickCreate,
            'formViewUrl' => $formViewUrl,
            'createUrl' => $formViewUrl !== null ? ViewUrlResolver::createHref($formViewUrl) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function many2manyProps(WidgetContext $ctx, Many2manyField $field): array
    {
        $hint = $ctx->spec['widget'] ?? null;
        $dialogOnly = $hint === 'dialog' || $ctx->mode === FormMode::Display;

        return [
            'comodel' => $field->comodel,
            'searchUrl' => route('velm.api.m2o.search', ['model' => $field->comodel]),
            'formViewUrl' => ViewUrlResolver::recordViewUrlForModel($ctx->env, $field->comodel, $ctx->mode),
            'initial' => RelationalInitials::many2manyChips($ctx->env, $field, $ctx->value()),
            'dialogOnly' => $dialogOnly,
            'canQuickCreate' => $ctx->mode !== FormMode::Display && $this->canQuickCreate($ctx->env, $field->comodel),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function one2manyProps(WidgetContext $ctx, One2manyField $field, ?int $parentRecordId): array
    {
        $hint = $ctx->spec['widget'] ?? 'dialog';
        $inline = $hint === 'inline' || $hint === 'table';
        $columns = $this->o2mColumns($ctx, $field);

        return [
            'comodel' => $field->comodel,
            'inverseName' => $field->inverseName,
            'searchUrl' => route('velm.api.m2o.search', ['model' => $field->comodel]),
            'formViewUrl' => ViewUrlResolver::recordViewUrlForModel($ctx->env, $field->comodel, $ctx->mode),
            'recordsApiUrl' => url('/api/records'),
            'rows' => RelationalInitials::one2manyRows($ctx->env, $field, $ctx->value(), $columns),
            'parentRecordId' => $parentRecordId,
            'inline' => $inline,
            'columns' => $columns,
        ];
    }

    /**
     * @return list<array{name: string, label: string}>
     */
    private function o2mColumns(WidgetContext $ctx, One2manyField $field): array
    {
        $raw = $ctx->spec['columns'] ?? null;
        if (is_array($raw) && $raw !== []) {
            $columns = [];
            $comodelClass = $ctx->env->registry->modelClass($field->comodel);
            $fields = $comodelClass::fields();

            foreach ($raw as $col) {
                $spec = is_string($col) ? ['name' => $col] : $col;
                if (! is_array($spec) || ! isset($spec['name'])) {
                    continue;
                }
                $fname = (string) $spec['name'];
                $colField = $fields[$fname] ?? null;
                $columns[] = [
                    'name' => $fname,
                    'label' => (string) ($spec['label'] ?? $colField?->displayLabel() ?? Field::humanizeFieldName($fname)),
                    'kind' => $colField !== null ? $this->o2mColumnKind($colField) : 'readonly',
                ];
            }

            return $columns;
        }

        $comodelClass = $ctx->env->registry->modelClass($field->comodel);
        $nameField = $comodelClass::fields()['name'] ?? null;

        return [[
            'name' => 'name',
            'label' => $nameField?->displayLabel() ?? 'Name',
            'kind' => $nameField !== null ? $this->o2mColumnKind($nameField) : 'char',
        ]];
    }

    private function o2mColumnKind(Field $field): string
    {
        return match (true) {
            $field instanceof BooleanField => 'boolean',
            $field instanceof IntegerField => 'integer',
            $field instanceof CharField, $field instanceof TextField => 'char',
            default => 'readonly',
        };
    }

    private function canQuickCreate(Environment $env, string $comodel): bool
    {
        try {
            return app(Many2oneQuickCreate::class)->canQuickCreate(
                $env->registry->modelClass($comodel),
            );
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $arch
     * @return array<string, mixed>
     */
    private static function normalizeArch(array $arch): array
    {
        return match ($arch['view_type'] ?? 'form') {
            'detail' => ArchNormalizer::normalizeDetail($arch),
            default => ArchNormalizer::normalizeForm($arch),
        };
    }
}
