<?php

declare(strict_types=1);

namespace Velm\Ui\Concerns;

use Velm\Environment;
use Velm\Fields\Many2manyField;
use Velm\Fields\One2manyField;
use Velm\Ui\Forms\FormMode;
use Velm\Ui\Forms\FormRenderer;
use Velm\Ui\Forms\FormSectionView;

/**
 * Livewire form state + save/create for arch-driven pages (PyVelm form semantics).
 */
trait InteractsWithVelmArchForm
{
    /** @var array<string, mixed> */
    public array $data = [];

    public ?string $formError = null;

    /** @var array<string, string> */
    public array $fieldErrors = [];

    abstract protected function arch(): array;

    abstract protected function listPageUrl(): string;

    abstract protected function velmFormMode(): FormMode;

    /**
     * @return list<FormSectionView>
     */
    public function velmFormSections(): array
    {
        return app(FormRenderer::class)->sections(
            $this->arch(),
            app(Environment::class),
            $this->velmFormMode(),
            $this->data,
            $this->fieldErrors,
            $this->velmFormRecordId(),
            $this->velmFormViewModule(),
            $this->velmFormViewName(),
        );
    }

    public function velmFormTitle(): string
    {
        return (string) ($this->arch()['title'] ?? 'Record');
    }

    public function velmRecordDisplayName(): string
    {
        $recordId = $this->velmFormRecordId();

        if ($recordId === null) {
            return $this->velmFormTitle();
        }

        $model = (string) ($this->arch()['model'] ?? '');

        if ($model === '') {
            return '#'.$recordId;
        }

        $row = app(Environment::class)->browse($model, [$recordId])->read()[0] ?? [];

        return (string) ($row['display_name'] ?? '#'.$recordId);
    }

    protected function velmFormViewModule(): ?string
    {
        if (property_exists($this, 'module') && is_string($this->module) && $this->module !== '') {
            return $this->module;
        }

        return null;
    }

    protected function velmFormViewName(): ?string
    {
        if (property_exists($this, 'viewName') && is_string($this->viewName) && $this->viewName !== '') {
            return $this->viewName;
        }

        return null;
    }

    protected function fillVelmFormFromRecord(int $recordId): void
    {
        $arch = $this->arch();
        $model = (string) $arch['model'];
        $rows = app(Environment::class)->browse($model, [$recordId])->read();
        $row = $rows[0] ?? [];

        unset($row['id'], $row['display_name']);
        $this->data = $this->normalizeVelmFormRow($row, $arch);
    }

    protected function resetVelmForm(): void
    {
        $this->data = [];
        $this->formError = null;
        $this->fieldErrors = [];
        $this->prefillVelmFormFromQuery();
    }

    protected function prefillVelmFormFromQuery(): void
    {
        if ($this->velmFormMode() !== FormMode::New) {
            return;
        }

        foreach (request()->query() as $key => $value) {
            if (! is_string($key) || $key === '' || is_array($value)) {
                continue;
            }

            $this->data[$key] = is_numeric($value) ? (int) $value : $value;
        }
    }

    public function saveVelmForm(): void
    {
        $this->formError = null;
        $this->fieldErrors = [];

        try {
            $state = $this->mutateVelmFormData($this->data);
            $arch = $this->arch();
            $model = (string) $arch['model'];
            $recordId = $this->velmFormRecordId();

            if ($recordId === null) {
                throw new \LogicException('saveVelmForm requires a record id.');
            }

            app(Environment::class)->browse($model, [$recordId])->write($state);

            $this->afterVelmFormSaved();
            $this->redirectAfterVelmFormSubmit($recordId);
        } catch (\Throwable $e) {
            $this->formError = $e->getMessage();
        }
    }

    public function createVelmForm(): void
    {
        $this->formError = null;
        $this->fieldErrors = [];

        try {
            $state = $this->mutateVelmFormData($this->data);
            $arch = $this->arch();
            $model = (string) $arch['model'];

            $created = app(Environment::class)->model($model)->create($state);
            $newId = $created->ids()[0] ?? null;

            $this->afterVelmFormCreated();
            $this->redirectAfterVelmFormSubmit($newId !== null ? (int) $newId : null);
        } catch (\Throwable $e) {
            $this->formError = $e->getMessage();
        }
    }

    protected function velmFormRecordId(): ?int
    {
        if (! property_exists($this, 'record')) {
            return null;
        }

        $id = (int) $this->record;

        return $id > 0 ? $id : null;
    }

    public function velmFormCanDelete(): bool
    {
        $recordId = $this->velmFormRecordId();

        if ($recordId === null) {
            return false;
        }

        $model = (string) ($this->arch()['model'] ?? '');

        return $model !== '' && app(Environment::class)->hasAccess($model, 'unlink');
    }

    public function deleteVelmForm(): void
    {
        $recordId = $this->velmFormRecordId();

        if ($recordId === null) {
            return;
        }

        $model = (string) ($this->arch()['model'] ?? '');

        if ($model === '') {
            return;
        }

        try {
            app(Environment::class)->browse($model, [$recordId])->unlink();

            session()->flash('velm_notify', [
                'type' => 'success',
                'title' => (string) __('Deleted'),
                'body' => null,
            ]);

            if ($this->velmFormEmbedded()) {
                $this->js('window.parent.pvCloseRecordDialog?.()');

                return;
            }

            $this->redirect($this->listPageUrl(), navigate: true);
        } catch (\Throwable $e) {
            $this->formError = $e->getMessage();
        }
    }

    protected function afterVelmFormSaved(): void {}

    protected function afterVelmFormCreated(): void {}

    protected function velmFormEmbedded(): bool
    {
        return request()->boolean('embed');
    }

    protected function velmFormEmbedUrl(string $url): string
    {
        if (str_contains($url, 'embed=1')) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').'embed=1';
    }

    /**
     * Detail page after save/create when defined (stored views with detail arch).
     */
    protected function detailPageUrl(?int $recordId): ?string
    {
        return null;
    }

    protected function redirectAfterVelmFormSubmit(?int $recordId = null): void
    {
        if ($this->velmFormEmbedded()) {
            if ($recordId !== null) {
                $this->notifyEmbedDialogParent($recordId);

                $recordUrl = method_exists($this, 'velmFormEmbedRecordUrl')
                    ? $this->velmFormEmbedRecordUrl($recordId)
                    : null;

                if (is_string($recordUrl) && $recordUrl !== '') {
                    $this->redirect($this->velmFormEmbedUrl($recordUrl));

                    return;
                }
            }

            $this->js('window.parent.pvCloseRecordDialog?.()');

            return;
        }

        $target = $recordId !== null
            ? ($this->detailPageUrl($recordId) ?? $this->listPageUrl())
            : $this->listPageUrl();

        $this->redirect($target);
    }

    protected function notifyEmbedDialogParent(int $recordId): void
    {
        $arch = $this->arch();
        $model = (string) ($arch['model'] ?? '');

        if ($model === '') {
            return;
        }

        $rows = app(Environment::class)->browse($model, [$recordId])->read(['display_name']);
        $label = (string) ($rows[0]['display_name'] ?? $recordId);

        $payload = json_encode([
            'type' => 'velm-dialog-saved',
            'id' => $recordId,
            'label' => $label,
            'model' => $model,
        ], JSON_THROW_ON_ERROR);

        $this->js('window.parent.postMessage('.$payload.', window.location.origin)');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $arch
     * @return array<string, mixed>
     */
    protected function normalizeVelmFormRow(array $row, array $arch): array
    {
        $model = (string) ($arch['model'] ?? '');
        $fields = $model !== '' ? app(Environment::class)->registry->modelClass($model)::fields() : [];

        foreach ($fields as $name => $field) {
            if ($field instanceof Many2manyField || $field instanceof One2manyField) {
                $row[$name] = \Velm\Ui\Support\RelationalInitials::normalizeIds($row[$name] ?? null);
            }
        }

        return $row;
    }

    protected function mutateVelmFormData(array $data): array
    {
        unset($data['id'], $data['display_name']);

        $arch = $this->arch();
        $model = (string) ($arch['model'] ?? '');
        $fields = $model !== '' ? app(Environment::class)->registry->modelClass($model)::fields() : [];

        foreach ($data as $key => $value) {
            $field = $fields[$key] ?? null;

            if ($field instanceof Many2manyField || $field instanceof One2manyField) {
                $data[$key] = is_array($value)
                    ? array_values(array_map(intval(...), $value))
                    : [];

                continue;
            }

            if ($value === '') {
                $data[$key] = null;
            }
        }

        return $data;
    }
}
