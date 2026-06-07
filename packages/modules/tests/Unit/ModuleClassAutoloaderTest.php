<?php

declare(strict_types=1);

use Velm\Modules\Autoload\ModuleClassAutoloader;
use Velm\Modules\Autoload\ModuleClassResolver;
use Velm\Modules\Support\ModuleNaming;

test('resolver maps studly module names to snake_case directories', function (): void {
    expect(ModuleNaming::studlyToSnake('ChangeManagement'))->toBe('change_management')
        ->and(ModuleNaming::studlyToSnake('DemoRelations'))->toBe('demo_relations')
        ->and(ModuleNaming::studlyToSnake('PartnersExt'))->toBe('partners_ext');
});

test('autoloader resolves addon model and hook classes without composer psr-4', function (): void {
    if (! is_dir(sys_get_temp_dir())) {
        skip('sys temp dir is not available.');
    }

    $root = rtrim(sys_get_temp_dir(), '/\\').'/velm_autoload_'.bin2hex(random_bytes(6));
    $modulePath = $root.'/shipping_labels';

    mkdir($modulePath.'/models', 0777, true);

    file_put_contents($modulePath.'/__velm__.php', "<?php\nreturn ['NAME' => 'shipping_labels', 'VERSION' => [0, 1, 0], 'DEPENDS' => []];\n");

    file_put_contents($modulePath.'/models/Label.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Addons\ShippingLabels\Models;

final class Label
{
    public static function label(): string
    {
        return 'shipping-label';
    }
}
PHP);

    file_put_contents($modulePath.'/ShippingLabelsInstallHooks.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Addons\ShippingLabels;

final class ShippingLabelsInstallHooks
{
    public static function install(): string
    {
        return 'installed';
    }
}
PHP);

    ModuleClassAutoloader::register([
        'Addons\\' => [$root],
    ]);

    expect(class_exists(Addons\ShippingLabels\Models\Label::class))->toBeTrue()
        ->and(Addons\ShippingLabels\Models\Label::label())->toBe('shipping-label')
        ->and(class_exists(Addons\ShippingLabels\ShippingLabelsInstallHooks::class))->toBeTrue()
        ->and(Addons\ShippingLabels\ShippingLabelsInstallHooks::install())->toBe('installed');

    $resolver = new ModuleClassResolver('Addons\\', [$root]);

    expect($resolver->resolve(Addons\ShippingLabels\Models\Label::class))
        ->toBe($modulePath.'/models/Label.php');
});

test('autoloader resolves bundled velm module models', function (): void {
    $bundledRoot = dirname(__DIR__, 2).'/modules';

    ModuleClassAutoloader::register([
        'Velm\\Modules\\' => [$bundledRoot],
    ]);

    expect(class_exists(Velm\Modules\Partners\Models\Partner::class))->toBeTrue();
});

test('autoloader reset unregisters handler and callable roots work', function (): void {
    $root = rtrim(sys_get_temp_dir(), '/\\').'/velm_autoload_reset_'.bin2hex(random_bytes(6));
    $modulePath = $root.'/reset_demo';
    mkdir($modulePath.'/models', 0777, true);
    file_put_contents($modulePath.'/__velm__.php', "<?php\nreturn ['NAME' => 'reset_demo', 'VERSION' => [0, 1, 0], 'DEPENDS' => []];\n");
    file_put_contents($modulePath.'/models/Gadget.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace Addons\ResetDemo\Models;
final class Gadget
{
    public static function id(): string
    {
        return 'gadget';
    }
}
PHP);

    ModuleClassAutoloader::register([
        'Addons\\' => static fn (): array => [$root],
    ]);

    expect(class_exists(Addons\ResetDemo\Models\Gadget::class))->toBeTrue()
        ->and(Addons\ResetDemo\Models\Gadget::id())->toBe('gadget');

    ModuleClassAutoloader::reset();
});
