<?php

declare(strict_types=1);

use Velm\Admin\Concerns\InteractsWithVelmViewActions;
use Velm\Admin\Tests\TestCase;
use Velm\Modules\ModuleInstaller;
use Velm\Views\Authoring\ActionVariant;

final class ViewActionsProbe
{
    use InteractsWithVelmViewActions;

    public string $module = 'partners';

    public string $viewName = 'partner.list';

    public int $record = 9;

    /**
     * @param  array<string, mixed>  $arch
     */
    public function __construct(private array $arch) {}

    protected function arch(): array
    {
        return $this->arch;
    }
}

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('view actions probe builds inline form urls', function (): void {
    $roots = [dirname(__DIR__, 3).'/modules/modules'];
    (new ModuleInstaller)->installBootstrap($roots, ['base', 'admin', 'partners']);

    $listProbe = new ViewActionsProbe([
        'model' => 'res.partner',
        'page_actions' => [
            [
                'label' => 'Quick add',
                'model' => 'res.partner',
                'variant' => ActionVariant::Primary->value,
                'perm' => 'create',
                'form' => [
                    'sections' => [
                        ['name' => 'identity', 'title' => 'Quick', 'fields' => [['name' => 'name']]],
                    ],
                ],
            ],
        ],
    ]);
    $listProbe->module = 'partners';
    $listProbe->viewName = 'partner.list';

    $detailProbe = new ViewActionsProbe([
        'model' => 'res.partner',
        'header_actions' => [
            [
                'label' => 'Quick edit',
                'model' => 'res.partner',
                'variant' => ActionVariant::Primary->value,
                'perm' => 'write',
                'form' => [
                    'sections' => [
                        ['name' => 'identity', 'title' => 'Quick', 'fields' => [['name' => 'name']]],
                    ],
                ],
            ],
        ],
    ]);
    $detailProbe->module = 'partners';
    $detailProbe->viewName = 'partner.detail';
    $detailProbe->record = 42;

    $createAction = $listProbe->velmPageActions()[0];
    $editAction = $detailProbe->velmHeaderActions()[0];

    expect($createAction['kind'])->toBe('inline_form')
        ->and($createAction['form_url'])->toBe('/web/view-actions/partners/partner.list/page/quick-add/form')
        ->and($createAction['variant'])->toBe('primary')
        ->and($editAction['kind'])->toBe('inline_form')
        ->and($editAction['form_url'])->toBe('/web/view-actions/partners/partner.detail/header/quick-edit/form?record=42');
});
