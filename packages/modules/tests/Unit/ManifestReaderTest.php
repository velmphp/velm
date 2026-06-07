<?php

declare(strict_types=1);

use Velm\Modules\Manifest;
use Velm\Modules\ManifestReader;

beforeEach(function (): void {
    $this->tempRoot = sys_get_temp_dir().'/velm-manifest-'.uniqid('', true);
    mkdir($this->tempRoot, 0777, true);
});

afterEach(function (): void {
    if (! is_dir($this->tempRoot ?? '')) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->tempRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($this->tempRoot);
});

test('manifest reader accepts fluent manifest return value', function (): void {
    $path = $this->tempRoot.DIRECTORY_SEPARATOR.'fluent';
    mkdir($path, 0777, true);

    file_put_contents($path.DIRECTORY_SEPARATOR.'__velm__.php', "<?php\n\ndeclare(strict_types=1);\n\nuse Velm\\Modules\\Manifest;\n\nreturn Manifest::make('fluent_mod')->version(0, 1, 0)->depends('base')->summary('Fluent manifest');\n");

    $spec = (new ManifestReader)->read($path);

    expect($spec->name)->toBe('fluent_mod')
        ->and($spec->summary)->toBe('Fluent manifest');
});

test('manifest reader rejects missing manifest file', function (): void {
    expect(fn () => (new ManifestReader)->read($this->tempRoot.'/empty'))
        ->toThrow(InvalidArgumentException::class, 'No __velm__.php');
});

test('manifest reader rejects non-array manifest return', function (): void {
    $path = $this->tempRoot.DIRECTORY_SEPARATOR.'bad';
    mkdir($path, 0777, true);
    file_put_contents($path.DIRECTORY_SEPARATOR.'__velm__.php', "<?php\nreturn 'nope';\n");

    expect(fn () => (new ManifestReader)->read($path))
        ->toThrow(InvalidArgumentException::class, 'must return');
});
