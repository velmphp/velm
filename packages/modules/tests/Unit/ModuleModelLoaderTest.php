<?php

declare(strict_types=1);

use Velm\Modules\Mail\MailThreadService;
use Velm\Modules\Mail\Models\MailThread;
use Velm\Modules\ModuleModelLoader;
use Velm\Modules\ModuleSpec;
use Velm\Modules\Tests\Support\VersionedDemo;
use Velm\Registry;

test('module model loader ensureModelClassLoaded requires model file', function (): void {
    if (! is_dir(sys_get_temp_dir())) {
        skip('sys temp dir is not available.');
    }

    $root = rtrim(sys_get_temp_dir(), '/\\').'/velm_loader_'.bin2hex(random_bytes(6));
    $modulePath = $root.'/loader_demo';
    mkdir($modulePath.'/models', 0777, true);

    file_put_contents($modulePath.'/models/Widget.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Addons\LoaderDemo\Models;

use Velm\Fields\CharField;
use Velm\Models\Model;

final class Widget extends Model
{
    protected static ?string $name = 'loader.widget';

    public static function defineFields(): array
    {
        return ['name' => CharField::make()->required()];
    }
}
PHP);

    $class = 'Addons\\LoaderDemo\\Models\\Widget';

    ModuleModelLoader::ensureModelClassLoaded($class, $modulePath);

    expect(class_exists($class, false))->toBeTrue();
});

test('module model loader registerModelClass registers abstract mixins', function (): void {
    $registry = new Registry;

    ModuleModelLoader::registerModelClass(MailThread::class, $registry);

    expect(fn () => ModuleModelLoader::registerModelClass(MailThread::class, $registry))
        ->toThrow(RuntimeException::class, 'already registered');
});

test('module model loader syncs mail thread for models with mixin', function (): void {
    $modelClass = 'Addons\\MailMixinDemo\\Models\\Ticket';
    $modulePath = rtrim(sys_get_temp_dir(), '/\\').'/velm_mail_mixin_'.bin2hex(random_bytes(6)).'/mail_mixin_demo';
    mkdir($modulePath.'/models', 0777, true);
    file_put_contents($modulePath.'/models/Ticket.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace Addons\MailMixinDemo\Models;
use Velm\Fields\CharField;
use Velm\Models\Model;
final class Ticket extends Model
{
    protected static ?string $name = 'mail.mixin.ticket';
    protected static array $mixins = ['mail.thread'];
    public static function defineFields(): array
    {
        return ['name' => CharField::make()->required()];
    }
}
PHP);

    ModuleModelLoader::ensureModelClassLoaded($modelClass, $modulePath);

    $registry = new Registry;
    $registry->registerMixin(MailThread::class);
    ModuleModelLoader::registerModelClass($modelClass, $registry);

    expect(MailThreadService::hasThread('mail.mixin.ticket'))->toBeTrue();
});

test('module model loader load throws for missing model class', function (): void {
    $spec = new ModuleSpec(
        name: 'broken',
        version: [0, 1, 0],
        depends: [],
        path: sys_get_temp_dir(),
        models: ['Missing\\Model\\ClassName'],
    );

    expect(fn () => (new ModuleModelLoader)->load($spec, new Registry))
        ->toThrow(RuntimeException::class, 'was not found');
});

test('module model loader load throws when class does not extend model', function (): void {
    $spec = new ModuleSpec(
        name: 'broken',
        version: [0, 1, 0],
        depends: [],
        path: sys_get_temp_dir(),
        models: [stdClass::class],
    );

    expect(fn () => (new ModuleModelLoader)->load($spec, new Registry))
        ->toThrow(RuntimeException::class, 'must extend');
});

test('module model loader load registers concrete models', function (): void {
    $spec = new ModuleSpec(
        name: 'demo',
        version: [0, 1, 0],
        depends: [],
        path: sys_get_temp_dir(),
        models: [VersionedDemo::class],
    );
    $registry = new Registry;

    (new ModuleModelLoader)->load($spec, $registry);

    expect($registry->has('versioned.demo'))->toBeTrue();
});
