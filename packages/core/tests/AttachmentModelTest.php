<?php

declare(strict_types=1);

use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Modules\Base\Models\Attachment;
use Velm\Modules\ModuleModelLoader;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;
use Velm\Storage\AttachmentStorage;
use Velm\Storage\LocalStorageBackend;

test('attachment model fetchContentFromRow covers url inline and storage branches', function (): void {
    ModuleModelLoader::ensureModelClassLoaded(
        Attachment::class,
        dirname(__DIR__, 2).'/modules/modules/base',
    );

    expect(class_exists(Attachment::class))->toBeTrue();

    $root = sys_get_temp_dir().'/velm-module-att-'.uniqid('', true);
    mkdir($root, 0775, true);
    AttachmentStorage::resolveUsing(fn (): LocalStorageBackend => new LocalStorageBackend($root));
    $key = AttachmentStorage::backend()->save('doc.txt', 'module-bytes');

    expect(Attachment::fetchContentFromRow(['type' => 'url']))->toBe('')
        ->and(Attachment::fetchContentFromRow(['type' => 'binary', 'datas' => base64_encode('inline')]))->toBe('inline')
        ->and(Attachment::fetchContentFromRow(['type' => 'binary', 'storage_key' => $key]))->toBe('module-bytes')
        ->and(Attachment::fetchContentFromRow(['type' => 'binary', 'storage_key' => 'missing/key']))->toBe('');

    AttachmentStorage::resolveUsing(null);
    AttachmentStorage::resetBackendCache();
});

test('attachment model fetchContent and unlink remove rows and blobs', function (): void {
    AttachmentStorage::resolveUsing(null);
    AttachmentStorage::resetBackendCache();

    ModuleModelLoader::ensureModelClassLoaded(
        Attachment::class,
        dirname(__DIR__, 2).'/modules/modules/base',
    );

    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(Attachment::class);
        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry);
    });

    $key = AttachmentStorage::backend()->save('note.txt', 'payload');
    $att = $env->model('ir.attachment')->create([
        'name' => 'note.txt',
        'storage_key' => $key,
    ]);

    expect($att->fetchContent($att))->toBe('payload');

    $att->write(['datas' => base64_encode('inline')]);
    expect($att->fetchContent($att))->toBe('inline');

    $empty = $env->model('ir.attachment')->search([['id', '=', -1]]);
    $empty->unlink();

    $att->unlink();
    expect($env->model('ir.attachment')->search([['id', '=', $att->ids()[0]]])->count())->toBe(0);
});
