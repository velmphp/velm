<?php

declare(strict_types=1);

use Velm\Framework\VelmManager;
use Velm\Modules\FileManager\FileManagerService;
use Velm\Modules\Tests\Support\TestUpload;
use Velm\Web\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    app(VelmManager::class)->install('file_manager');

    $this->env = app(\Velm\Environment::class);
    $this->service = new FileManagerService($this->env);
    $this->service->ensureInstalled();
});

test('nested folders appear in browse breadcrumb chain', function (): void {
    $root = $this->service->createFolder(['name' => 'Root']);
    $child = $this->service->createFolder(['name' => 'Child', 'parent_id' => $root['id']]);

    $browse = $this->service->browse($child['id']);

    expect(collect($browse['breadcrumb'])->pluck('name')->values()->all())->toEqual(['Root', 'Child']);
});

test('update folder rejects parent cycle', function (): void {
    $parent = $this->service->createFolder(['name' => 'Parent']);
    $child = $this->service->createFolder(['name' => 'Child', 'parent_id' => $parent['id']]);

    expect(fn () => $this->service->updateFolder($parent['id'], ['parent_id' => $child['id']]))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('browse filters attachments by exact mime type accept token', function (): void {
    $folder = $this->service->createFolder(['name' => 'MimeFolder']);
    $pdf = $this->service->storeUpload(TestUpload::file('a.pdf', '%PDF', 'application/pdf'), false, $folder['id']);
    $this->service->storeUpload(TestUpload::file('b.txt', 'text', 'text/plain'), false, $folder['id']);

    expect($pdf['mimetype'] ?? null)->toBe('application/pdf');

    $browse = $this->service->browse($folder['id'], 'application/pdf', '');

    expect(collect($browse['rows'])->pluck('name')->all())->toContain('a.pdf')
        ->and(collect($browse['rows'])->pluck('name')->all())->not->toContain('b.txt');
});

test('delete folder rejects non empty folder with subfolders', function (): void {
    $parent = $this->service->createFolder(['name' => 'Parent']);
    $this->service->createFolder(['name' => 'Child', 'parent_id' => $parent['id']]);

    expect(fn () => $this->service->deleteFolder($parent['id']))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('human size labels appear in properties view data', function (): void {
    $row = $this->service->storeUpload(TestUpload::file('sized.txt', str_repeat('x', 2048)));
    $view = $this->service->propertiesViewData((int) $row['id']);

    expect($view['fileSizeLabel'])->toContain('KB');
});
