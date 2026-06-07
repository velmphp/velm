<?php

declare(strict_types=1);

use Velm\Admin\Concerns\InteractsWithStoredViewEmbedForm;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

test('stored view embed form trait builds record page url', function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $handler = new class
    {
        use InteractsWithStoredViewEmbedForm;

        public string $module = 'partners';

        public string $viewName = 'partner.form';

        public function expose(int $recordId): ?string
        {
            return $this->velmFormEmbedRecordUrl($recordId);
        }
    };

    $url = $handler->expose(42);

    expect($url)->toContain('partners')
        ->and($url)->toContain('42');
});
