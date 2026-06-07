<?php

declare(strict_types=1);

use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Core\Tests\Support\TestAttachment;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;
use Velm\Storage\AttachmentStorage;

test('unlink removes database row and local storage blob', function (): void {
    AttachmentStorage::resolveUsing(null);
    AttachmentStorage::resetBackendCache();

    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(TestAttachment::class);

        $connection = PdoConnection::sqliteMemory();
        $env = new Environment($connection, $registry);
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return $env;
    });

    $key = AttachmentStorage::backend()->save('note.txt', 'hello');

    $att = $env->model('ir.attachment')->create([
        'name' => 'note.txt',
        'datas_fname' => 'note.txt',
        'mimetype' => 'text/plain',
        'file_size' => 5,
        'type' => 'binary',
        'storage_key' => $key,
    ]);

    $id = $att->ids()[0];
    $att->unlink();

    expect($env->model('ir.attachment')->search([['id', '=', $id]])->count())->toBe(0);

    expect(fn () => AttachmentStorage::backend()->load($key))
        ->toThrow(RuntimeException::class);
});
