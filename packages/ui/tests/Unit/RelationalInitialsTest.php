<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;
use Velm\Ui\Support\RelationalInitials;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $roots = [dirname(__DIR__, 3).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);

    $this->env = $installer->environment($roots);
});

test('attachment chips include download metadata for many2one file widget', function (): void {
    $attachment = $this->env->model('ir.attachment')->create([
        'name' => 'Spec.pdf',
        'mimetype' => 'application/pdf',
        'type' => 'binary',
    ]);

    $chips = RelationalInitials::attachmentChips(
        $this->env,
        $attachment->ids()[0],
        false,
    );

    expect($chips)->toHaveCount(1)
        ->and($chips[0]['name'])->toBe('Spec.pdf')
        ->and($chips[0]['download_url'])->toContain('/api/attachment/');
});

test('attachment chips preserve order for many2many files widget', function (): void {
    $first = $this->env->model('ir.attachment')->create([
        'name' => 'First.png',
        'mimetype' => 'image/png',
        'type' => 'binary',
    ]);
    $second = $this->env->model('ir.attachment')->create([
        'name' => 'Second.png',
        'mimetype' => 'image/png',
        'type' => 'binary',
    ]);

    $chips = RelationalInitials::attachmentChips(
        $this->env,
        [$second->ids()[0], $first->ids()[0]],
        true,
    );

    expect(array_column($chips, 'name'))->toBe(['Second.png', 'First.png'])
        ->and($chips[0]['thumbnail_url'])->toContain('/api/attachment/');
});
