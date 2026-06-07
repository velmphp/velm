<?php

declare(strict_types=1);

use Velm\Admin\Arch\ViewFieldCatalog;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

test('view fields controller maps catalog invalid argument exceptions', function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $catalog = new class extends ViewFieldCatalog
    {
        public function forModel(string $model, \Velm\Environment $env): array
        {
            throw new \InvalidArgumentException('Catalog rejected');
        }
    };

    app()->instance(ViewFieldCatalog::class, $catalog);

    $this->getJson('/api/view-fields?model=res.partner')
        ->assertStatus(400)
        ->assertJsonPath('message', 'Catalog rejected');
});
