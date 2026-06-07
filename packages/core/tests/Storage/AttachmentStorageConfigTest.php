<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\ComputedArticle;
use Velm\Core\Tests\Support\DottedDepComputedArticle;
use Velm\Computed\ComputedFieldGraph;
use Velm\Framework\Tests\TestCase;
use Velm\Storage\AttachmentStorage;
use Velm\Storage\DbStorageBackend;

uses(TestCase::class);

afterEach(function (): void {
    AttachmentStorage::resetTestingState();
});

test('attachment storage reads db backend from config', function (): void {
    AttachmentStorage::resolveUsing(null);
    AttachmentStorage::resetBackendCache();
    config(['velm.attachments.backend' => 'db']);

    expect(AttachmentStorage::backend())->toBeInstanceOf(DbStorageBackend::class);
});

test('attachment storage fallback handles missing app and storage path failures', function (): void {
    AttachmentStorage::useAppFactoryForTesting(static fn (): object => new class
    {
        public function storagePath(string $path = ''): string
        {
            throw new RuntimeException('storage unavailable');
        }
    });
    config(['velm.attachments.dir' => null]);

    expect(AttachmentStorage::fallbackLocalRoot())->toBe(sys_get_temp_dir().'/velm-attachments');

    AttachmentStorage::useAppFactoryForTesting(static fn (): object => new stdClass);
    expect(AttachmentStorage::fallbackLocalRoot())->toBe(sys_get_temp_dir().'/velm-attachments');
});

test('attachment storage config helper tolerates missing app and config failures', function (): void {
    AttachmentStorage::resolveUsing(null);
    AttachmentStorage::forceNoAppFunctionForTesting(true);
    AttachmentStorage::resetBackendCache();

    expect(AttachmentStorage::backend())->toBeInstanceOf(\Velm\Storage\LocalStorageBackend::class);

    AttachmentStorage::forceNoAppFunctionForTesting(false);
    AttachmentStorage::resolveUsing(null);
    AttachmentStorage::useAppFactoryForTesting(static fn (): object => new class
    {
        public function bound(string $abstract): bool
        {
            return true;
        }

        public function make(string $abstract): object
        {
            return new class
            {
                public function get(string $key): mixed
                {
                    throw new RuntimeException('config unavailable');
                }
            };
        }
    });
    AttachmentStorage::resetBackendCache();

    expect(AttachmentStorage::backend())->toBeInstanceOf(\Velm\Storage\LocalStorageBackend::class);
});

test('attachment storage rejects unknown configured backend', function (): void {
    AttachmentStorage::resolveUsing(null);
    config(['velm.attachments.backend' => 's3']);
    AttachmentStorage::resetBackendCache();

    expect(fn () => AttachmentStorage::backend())
        ->toThrow(RuntimeException::class, "Unknown attachment backend 's3'");
});

test('attachment storage fallbackLocalRoot honors configured dir', function (): void {
    config(['velm.attachments.dir' => '/tmp/custom-attachments/']);

    expect(AttachmentStorage::fallbackLocalRoot())->toBe('/tmp/custom-attachments');
});

test('attachment storage fallbackLocalRoot uses app storage path', function (): void {
    config(['velm.attachments.dir' => null]);

    expect(AttachmentStorage::fallbackLocalRoot())->toContain('velm/attachments');
});

test('attachment storage backend cache returns same instance', function (): void {
    AttachmentStorage::resolveUsing(null);
    AttachmentStorage::resetBackendCache();

    expect(AttachmentStorage::backend())->toBe(AttachmentStorage::backend());
});
