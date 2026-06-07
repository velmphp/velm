<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Pages\FileLibraryPage;
use Velm\Admin\Pages\FilePropertiesPage;
use Velm\Admin\Tests\TestCase;
use Velm\Framework\VelmManager;
use Velm\Modules\Tests\Support\TestUpload;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    app(VelmManager::class)->install('file_manager');
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));
});

test('file library page browses unfiled attachments when folder id is zero', function (): void {
    Livewire::withQueryParams(['folder_id' => '0'])
        ->test(FileLibraryPage::class)
        ->assertOk()
        ->assertSet('libraryConfig.activeFolderId', 0);
});

test('file library page browses a specific folder when folder id is provided', function (): void {
    $folderId = (int) $this->postJson('/web/files/folders', ['name' => 'Browse Folder'])->json('id');

    Livewire::withQueryParams(['folder_id' => (string) $folderId, 'q' => 'needle'])
        ->test(FileLibraryPage::class)
        ->assertOk()
        ->assertSet('searchQuery', 'needle')
        ->assertSet('libraryConfig.activeFolderId', $folderId);
});

test('file properties page renders attachment title and properties view', function (): void {
    $upload = $this->post('/web/files/picker/upload', [
        'file' => TestUpload::file('props.txt', 'properties-content'),
    ]);
    $attId = (int) $upload->json('id');

    Livewire::test(FilePropertiesPage::class, ['attId' => $attId])
        ->assertOk()
        ->assertSet('attId', $attId);

    $page = Livewire::test(FilePropertiesPage::class, ['attId' => $attId])->instance();

    expect($page->getTitle())->toBe('props.txt')
        ->and($page->render())->toBeInstanceOf(\Illuminate\View\View::class);
});
