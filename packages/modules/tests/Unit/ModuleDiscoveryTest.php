<?php

declare(strict_types=1);

use Velm\Modules\DependencyResolver;
use Velm\Modules\ModuleDiscovery;

beforeEach(function (): void {
    $this->tempRoot = sys_get_temp_dir().'/velm-modules-'.uniqid('', true);
    mkdir($this->tempRoot, 0777, true);
});

afterEach(function (): void {
    if (is_dir($this->tempRoot ?? '')) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tempRoot, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->tempRoot);
    }
});

function writeModule(string $root, string $name, array $manifest): void
{
    $path = $root.DIRECTORY_SEPARATOR.$name;
    mkdir($path, 0777, true);
    $export = var_export($manifest, true);
    file_put_contents($path.DIRECTORY_SEPARATOR.'__velm__.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn {$export};\n");
}

test('discovers manifests under addon roots', function (): void {
    writeModule($this->tempRoot, 'base', [
        'NAME' => 'base',
        'VERSION' => [0, 1, 0],
        'DEPENDS' => [],
    ]);
    writeModule($this->tempRoot, 'partners', [
        'NAME' => 'partners',
        'VERSION' => [0, 1, 0],
        'DEPENDS' => ['base'],
        'SUMMARY' => 'Partners',
    ]);

    $specs = (new ModuleDiscovery)->discover([$this->tempRoot]);

    expect($specs)->toHaveKeys(['base', 'partners'])
        ->and($specs['partners']->summary)->toBe('Partners');
});

test('resolver orders dependencies before dependents', function (): void {
    writeModule($this->tempRoot, 'base', [
        'NAME' => 'base',
        'VERSION' => [0, 1, 0],
        'DEPENDS' => [],
    ]);
    writeModule($this->tempRoot, 'partners_pro', [
        'NAME' => 'partners_pro',
        'VERSION' => [0, 1, 0],
        'DEPENDS' => ['partners'],
    ]);
    writeModule($this->tempRoot, 'partners', [
        'NAME' => 'partners',
        'VERSION' => [0, 1, 0],
        'DEPENDS' => ['base'],
    ]);

    $specs = (new ModuleDiscovery)->discover([$this->tempRoot]);
    $order = array_map(static fn ($spec) => $spec->name, (new DependencyResolver)->resolve($specs));

    expect($order)->toBe(['base', 'partners', 'partners_pro']);
});

test('resolver detects dependency cycles', function (): void {
    writeModule($this->tempRoot, 'a', [
        'NAME' => 'a',
        'VERSION' => [0, 1, 0],
        'DEPENDS' => ['b'],
    ]);
    writeModule($this->tempRoot, 'b', [
        'NAME' => 'b',
        'VERSION' => [0, 1, 0],
        'DEPENDS' => ['a'],
    ]);

    $specs = (new ModuleDiscovery)->discover([$this->tempRoot]);

    expect(fn () => (new DependencyResolver)->resolve($specs))
        ->toThrow(RuntimeException::class, 'dependency cycle');
});
