<?php

declare(strict_types=1);

use Velm\Ui\Concerns\InteractsWithVelmArchForm;
use Velm\Ui\Forms\FormMode;
use Velm\Ui\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('velm arch form trait exposes title sections and mutates data', function (): void {
    $handler = new class
    {
        use InteractsWithVelmArchForm;

        public string $module = 'partners';

        public string $viewName = 'partner.form';

        public int $record = 0;

        protected function arch(): array
        {
            return [
                'title' => 'Partner',
                'model' => 'res.partner',
                'sections' => [[
                    'name' => 'main',
                    'fields' => [['name' => 'name'], ['name' => 'active']],
                ]],
            ];
        }

        protected function listPageUrl(): string
        {
            return '/velm/views/partners/partner.list';
        }

        protected function velmFormMode(): FormMode
        {
            return FormMode::New;
        }

        public function exposeMutate(array $data): array
        {
            return $this->mutateVelmFormData($data);
        }
    };

    expect($handler->velmFormTitle())->toBe('Partner')
        ->and($handler->velmFormSections())->not->toBeEmpty()
        ->and($handler->exposeMutate(['name' => 'Acme', 'active' => true, 'ignored' => '']))
        ->toMatchArray(['name' => 'Acme', 'active' => true, 'ignored' => null]);
});

test('velm arch form trait fills and reads partner records', function (): void {
    $env = app(\Velm\Environment::class);
    $id = $env->model('res.partner')->create(['name' => 'Filled Partner', 'active' => true])->ids()[0];

    $handler = new class($id)
    {
        use InteractsWithVelmArchForm;

        public function __construct(private int $recordId) {}

        protected function arch(): array
        {
            return [
                'title' => 'Partner',
                'model' => 'res.partner',
                'sections' => [[
                    'name' => 'main',
                    'fields' => [['name' => 'name'], ['name' => 'active']],
                ]],
            ];
        }

        protected function listPageUrl(): string
        {
            return '/velm/views/partners/partner.list';
        }

        protected function velmFormMode(): FormMode
        {
            return FormMode::Edit;
        }

        protected function velmFormRecordId(): ?int
        {
            return $this->recordId;
        }

        public function exposeFill(): void
        {
            $this->fillVelmFormFromRecord($this->recordId);
        }
    };

    $handler->exposeFill();

    expect($handler->data)->toMatchArray(['name' => 'Filled Partner', 'active' => true])
        ->and($handler->velmRecordDisplayName())->toBe('Filled Partner')
        ->and($handler->velmFormCanDelete())->toBeTrue();
});

test('velm arch form trait prefill reads query string on new records', function (): void {
    $handler = new class
    {
        use InteractsWithVelmArchForm;

        protected function arch(): array
        {
            return ['title' => 'Partner', 'model' => 'res.partner', 'sections' => []];
        }

        protected function listPageUrl(): string
        {
            return '/velm/views/partners/partner.list';
        }

        protected function velmFormMode(): FormMode
        {
            return FormMode::New;
        }

        public function exposeReset(): void
        {
            request()->merge(['country_id' => '42']);
            $this->resetVelmForm();
        }
    };

    $handler->exposeReset();

    expect($handler->data)->toMatchArray(['country_id' => 42]);
});

test('velm arch form trait embed helpers append query flag', function (): void {
    $handler = new class
    {
        use InteractsWithVelmArchForm;

        protected function arch(): array
        {
            return ['title' => 'Partner', 'model' => 'res.partner', 'sections' => []];
        }

        protected function listPageUrl(): string
        {
            return '/velm/views/partners/partner.list';
        }

        protected function velmFormMode(): FormMode
        {
            return FormMode::New;
        }

        public function exposeEmbed(string $url): string
        {
            request()->merge(['embed' => '1']);

            return $this->velmFormEmbedUrl($url);
        }
    };

    expect($handler->exposeEmbed('/velm/views/partners/partner.form/1'))
        ->toBe('/velm/views/partners/partner.form/1?embed=1');
});

test('velm arch form trait create and save persist partner rows', function (): void {
    $creator = new class
    {
        use InteractsWithVelmArchForm;

        protected function arch(): array
        {
            return ['title' => 'Partner', 'model' => 'res.partner', 'sections' => []];
        }

        protected function listPageUrl(): string
        {
            return '/velm/views/partners/partner.list';
        }

        protected function velmFormMode(): FormMode
        {
            return FormMode::New;
        }

        protected function redirectAfterVelmFormSubmit(?int $recordId = null): void {}
    };

    $creator->data = ['name' => 'Trait Created'];
    $creator->createVelmForm();

    $env = app(\Velm\Environment::class);
    expect($env->model('res.partner')->search([['name', '=', 'Trait Created']])->count())->toBe(1);
});
