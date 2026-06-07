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

test('folderTree counts nested child folders', function (): void {
    $this->service->ensureInstalled();

    $parent = $this->service->createFolder(['name' => 'Parent']);
    $this->service->createFolder(['name' => 'Child', 'parent_id' => $parent['id']]);

    $tree = $this->service->folderTree();
    $parentRow = collect($tree['folders'])->firstWhere('name', 'Parent');

    expect($parentRow['child_count'] ?? 0)->toBe(1);
});

test('browse breadcrumb includes nested folder chain', function (): void {
    $this->service->ensureInstalled();

    $parent = $this->service->createFolder(['name' => 'RootBrowse']);
    $child = $this->service->createFolder(['name' => 'ChildBrowse', 'parent_id' => $parent['id']]);

    $browse = $this->service->browse($child['id'], '', '');

    expect(collect($browse['breadcrumb'])->pluck('name'))->toContain('RootBrowse', 'ChildBrowse');
});

test('storeUpload rejects empty files', function (): void {
    $this->service->ensureInstalled();

    expect(fn () => $this->service->storeUpload(TestUpload::file('empty.txt', ''), false))
        ->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('copyAttachments duplicates url attachments without storage key', function (): void {
    $this->service->ensureInstalled();

    $attId = $this->env->model('ir.attachment')->create([
        'name' => 'link.txt',
        'type' => 'url',
        'url' => 'https://example.com/file',
    ])->ids()[0];

    expect($this->service->copyAttachments([$attId], null))->toBe(1);
});

test('deleteAttachments and setPublic reject empty id lists', function (): void {
    $this->service->ensureInstalled();

    expect(fn () => $this->service->deleteAttachments([]))
        ->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);

    expect(fn () => $this->service->setPublic([], true))
        ->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('createFolder rejects empty name', function (): void {
    $this->service->ensureInstalled();

    expect(fn () => $this->service->createFolder(['name' => '   ']))
        ->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('updateFolder clears parent id', function (): void {
    $this->service->ensureInstalled();

    $parent = $this->service->createFolder(['name' => 'CycleParent']);
    $child = $this->service->createFolder(['name' => 'CycleChild', 'parent_id' => $parent['id']]);

    $updated = $this->service->updateFolder($child['id'], ['parent_id' => null]);

    expect($updated['parent_id'])->toBeNull();
});

test('deleteFolder rejects non-empty folders', function (): void {
    $this->service->ensureInstalled();

    $folder = $this->service->createFolder(['name' => 'HasChild']);
    $this->service->createFolder(['name' => 'Inner', 'parent_id' => $folder['id']]);

    expect(fn () => $this->service->deleteFolder($folder['id']))
        ->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class, 'subfolders');

    $fileFolder = $this->service->createFolder(['name' => 'HasFile']);
    $this->service->storeUpload(TestUpload::file('inside.txt', 'data'), false, $fileFolder['id']);

    expect(fn () => $this->service->deleteFolder($fileFolder['id']))
        ->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class, 'files');
});

test('propertiesContext includes owner url for linked records', function (): void {
    $this->service->ensureInstalled();

    $partnerId = $this->env->model('res.partner')->create(['name' => 'Linked Partner'])->ids()[0];
    $row = $this->service->storeUpload(TestUpload::file('linked.txt', 'linked'), false);
    $this->env->browse('ir.attachment', [(int) $row['id']])->write([
        'res_model' => 'res.partner',
        'res_id' => $partnerId,
    ]);

    $ctx = $this->service->propertiesContext((int) $row['id']);

    expect($ctx['owner_url'])->toContain('res.partner')
        ->and($ctx['owner_url'])->toContain((string) $partnerId);
});

test('moveAttachments to unfiled clears folder id', function (): void {
    $this->service->ensureInstalled();

    $folder = $this->service->createFolder(['name' => 'MoveOut']);
    $row = $this->service->storeUpload(TestUpload::file('move-me.txt', 'data'), false, $folder['id']);

    expect($this->service->moveAttachments([(int) $row['id']], null))->toBe(1);
});

test('updateFolder rejects empty name updates', function (): void {
    $this->service->ensureInstalled();

    $folder = $this->service->createFolder(['name' => 'RenameMe']);

    expect(fn () => $this->service->updateFolder($folder['id'], ['name' => '   ']))
        ->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('updateFolder rejects parent cycles', function (): void {
    $this->service->ensureInstalled();

    $root = $this->service->createFolder(['name' => 'RootCycle']);
    $mid = $this->service->createFolder(['name' => 'MidCycle', 'parent_id' => $root['id']]);
    $leaf = $this->service->createFolder(['name' => 'LeafCycle', 'parent_id' => $mid['id']]);

    expect(fn () => $this->service->updateFolder($root['id'], ['parent_id' => $leaf['id']]))
        ->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('browse at root lists top-level folders', function (): void {
    $this->service->ensureInstalled();

    $this->service->createFolder(['name' => 'TopLevel']);

    $browse = $this->service->browse(null, '', '');

    expect(collect($browse['folders'])->pluck('name'))->toContain('TopLevel');
});

test('pickerRows filters exact mime type', function (): void {
    $this->service->ensureInstalled();

    $this->service->storeUpload(TestUpload::file('exact.pdf', 'pdf', 'application/pdf'));
    $this->service->storeUpload(TestUpload::file('other.txt', 'txt', 'text/plain'));

    $rows = $this->service->pickerRows('application/pdf', '', 50);

    expect(collect($rows)->pluck('name'))->toContain('exact.pdf')
        ->and(collect($rows)->pluck('name'))->not->toContain('other.txt');
});
