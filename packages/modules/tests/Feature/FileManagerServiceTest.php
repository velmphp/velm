<?php

declare(strict_types=1);

use Velm\Modules\FileManager\FileManagerService;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\Support\TestUpload;
use Velm\Web\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $roots = [dirname(__DIR__, 3).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->install('file_manager', $roots);

    $this->env = app(\Velm\Environment::class);
    $this->service = new FileManagerService($this->env);
});

test('ensureInstalled passes when file_manager is installed', function (): void {
    $this->service->ensureInstalled();

    expect(true)->toBeTrue();
});

test('folderTree returns folders and unfiled count', function (): void {
    $this->service->ensureInstalled();

    $folder = $this->service->createFolder(['name' => 'Docs']);
    $file = TestUpload::file('a.txt', 'hello');
    $this->service->storeUpload($file, false, $folder['id']);

    $tree = $this->service->folderTree();

    expect($tree)->toHaveKeys(['folders', 'unfiled_count'])
        ->and(collect($tree['folders'])->pluck('name'))->toContain('Docs')
        ->and($tree['unfiled_count'])->toBeInt();
});

test('browse lists folder contents and supports search mode', function (): void {
    $this->service->ensureInstalled();

    $folder = $this->service->createFolder(['name' => 'SearchMe']);
    $file = TestUpload::file('needle.txt', 'payload');
    $row = $this->service->storeUpload($file, true, $folder['id']);

    $browse = $this->service->browse($folder['id'], '', '');

    expect($browse['searching'])->toBeFalse()
        ->and($browse['folder_id'])->toBe($folder['id'])
        ->and(collect($browse['rows'])->pluck('id'))->toContain($row['id']);

    $search = $this->service->browse(null, '', 'needle');

    expect($search['searching'])->toBeTrue()
        ->and(collect($search['rows'])->pluck('name'))->toContain('needle.txt');
});

test('pickerRows filters by accept mime pattern', function (): void {
    $this->service->ensureInstalled();

    $this->service->storeUpload(TestUpload::file('pic.png', 'png-bytes', 'image/png'));
    $this->service->storeUpload(TestUpload::file('doc.txt', 'text', 'text/plain'));

    $rows = $this->service->pickerRows('image/*', '', 50);

    expect(collect($rows)->every(fn (array $r): bool => str_starts_with((string) ($r['mimetype'] ?? ''), 'image/')))->toBeTrue();
});

test('move copy delete and setPublic on attachments', function (): void {
    $this->service->ensureInstalled();

    $folderA = $this->service->createFolder(['name' => 'A']);
    $folderB = $this->service->createFolder(['name' => 'B']);
    $row = $this->service->storeUpload(TestUpload::file('move.txt', 'data'), false, $folderA['id']);
    $id = (int) $row['id'];

    expect($this->service->moveAttachments([$id], $folderB['id']))->toBe(1);
    expect($this->service->copyAttachments([$id], null))->toBe(1);
    expect($this->service->setPublic([$id], true))->toBe(1);

    $this->service->deleteAttachments([$id]);
});

test('create update and delete folders', function (): void {
    $this->service->ensureInstalled();

    $parent = $this->service->createFolder(['name' => 'Parent']);
    $child = $this->service->createFolder(['name' => 'Child', 'parent_id' => $parent['id']]);

    $updated = $this->service->updateFolder($child['id'], ['name' => 'ChildRenamed']);

    expect($updated['name'])->toBe('ChildRenamed');

    $this->service->deleteFolder($child['id']);
    $this->service->deleteFolder($parent['id']);
});

test('propertiesContext returns metadata for attachment', function (): void {
    $this->service->ensureInstalled();

    $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $row = $this->service->storeUpload(TestUpload::file('tiny.png', $pngBytes, 'image/png'), true);
    $id = (int) $row['id'];

    $view = $this->service->propertiesViewData($id);
    $ctx = $this->service->propertiesContext($id);

    expect($view['isImage'])->toBeTrue()
        ->and($view['fileSizeLabel'])->not->toBe('')
        ->and($ctx['mimetype'])->toBe('image/png')
        ->and($ctx['dimensions'])->toBeArray();
});
