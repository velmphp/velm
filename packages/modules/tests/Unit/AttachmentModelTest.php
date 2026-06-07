<?php

declare(strict_types=1);

use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Modules\Base\Models\Attachment;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;
use Velm\Storage\AttachmentStorage;
use Velm\Storage\LocalStorageBackend;

beforeEach(function (): void {
    AttachmentStorage::resolveUsing(null);
    AttachmentStorage::resetBackendCache();
});

test('attachment fetchContentFromRow handles url type and empty payloads', function (): void {
    expect(Attachment::fetchContentFromRow(['type' => 'url', 'url' => 'https://example.com']))->toBe('')
        ->and(Attachment::fetchContentFromRow(['type' => 'binary']))->toBe('')
        ->and(Attachment::fetchContentFromRow(['type' => 'binary', 'datas' => '!!!not-base64!!!']))->toBe('');
});

test('attachment fetchContentFromRow reads inline base64 and storage backend', function (): void {
    $encoded = base64_encode('inline-bytes');
    expect(Attachment::fetchContentFromRow(['type' => 'binary', 'datas' => $encoded]))->toBe('inline-bytes');

    $backend = new LocalStorageBackend(sys_get_temp_dir().'/velm_att_'.uniqid('', true));
    AttachmentStorage::resolveUsing(fn (): LocalStorageBackend => $backend);
    AttachmentStorage::resetBackendCache();

    $key = $backend->save('stored.txt', 'stored-bytes');

    expect(Attachment::fetchContentFromRow(['type' => 'binary', 'storage_key' => $key]))->toBe('stored-bytes');
});

test('attachment fetchContentFromRow swallows storage backend failures', function (): void {
    AttachmentStorage::resolveUsing(fn (): LocalStorageBackend => new LocalStorageBackend('/nonexistent/path/'.uniqid('', true)));
    AttachmentStorage::resetBackendCache();

    expect(Attachment::fetchContentFromRow(['type' => 'binary', 'storage_key' => 'missing-key']))->toBe('');
});

test('attachment fetchContent reads singleton row and unlink is safe on empty set', function (): void {
    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(Attachment::class);
        $connection = PdoConnection::sqliteMemory();
        $env = new Environment($connection, $registry);
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return $env;
    });

    $empty = $env->model('ir.attachment')->search([['id', '=', -1]]);
    $empty->unlink();
    expect($empty->count())->toBe(0);

    $att = $env->model('ir.attachment')->create([
        'name' => 'inline.txt',
        'type' => 'binary',
        'datas' => base64_encode('fetch-me'),
    ]);

    expect($att->fetchContent($att))->toBe('fetch-me');
});

test('attachment unlink removes storage keys and ignores delete failures', function (): void {
    $backend = new LocalStorageBackend(sys_get_temp_dir().'/velm_att_unlink_'.uniqid('', true));
    AttachmentStorage::resolveUsing(fn (): LocalStorageBackend => $backend);
    AttachmentStorage::resetBackendCache();

    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(Attachment::class);
        $connection = PdoConnection::sqliteMemory();
        $env = new Environment($connection, $registry);
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return $env;
    });

    $key = $backend->save('gone.txt', 'payload');
    $att = $env->model('ir.attachment')->create([
        'name' => 'gone.txt',
        'type' => 'binary',
        'storage_key' => $key,
    ]);

    $att->unlink();

    expect($env->model('ir.attachment')->search()->count())->toBe(0)
        ->and(fn () => $backend->load($key))->toThrow(RuntimeException::class);
});

test('attachment unlink ignores storage delete failures', function (): void {
    $backend = new class implements \Velm\Storage\StorageBackend {
        public function save(string $filename, string $content): string
        {
            return 'key';
        }

        public function load(string $key): string
        {
            return 'payload';
        }

        public function delete(string $key): void
        {
            throw new RuntimeException('delete failed');
        }
    };
    AttachmentStorage::resolveUsing(fn (): \Velm\Storage\StorageBackend => $backend);
    AttachmentStorage::resetBackendCache();

    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(Attachment::class);
        $connection = PdoConnection::sqliteMemory();
        $env = new Environment($connection, $registry);
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return $env;
    });

    $key = $backend->save('ignore-fail.txt', 'payload');
    $att = $env->model('ir.attachment')->create([
        'name' => 'ignore-fail.txt',
        'type' => 'binary',
        'storage_key' => $key,
    ]);

    $att->unlink();

    expect($env->model('ir.attachment')->search()->count())->toBe(0);
});
