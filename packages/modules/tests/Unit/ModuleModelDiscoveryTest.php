<?php

declare(strict_types=1);

use Velm\Modules\Manifest;
use Velm\Modules\ManifestReader;
use Velm\Modules\ModuleModelDiscovery;
use Velm\Modules\ModuleSpec;

test('discovers models from models directory without manifest listing', function (): void {
    if (! is_dir(sys_get_temp_dir())) {
        skip('sys temp dir is not available.');
    }

    $root = rtrim(sys_get_temp_dir(), '/\\').'/velm_model_discover_'.bin2hex(random_bytes(6));
    $modulePath = $root.'/acme_widgets';

    mkdir($modulePath.'/models', 0777, true);

    file_put_contents($modulePath.'/__velm__.php', <<<'PHP'
<?php

declare(strict_types=1);

use Velm\Modules\Manifest;

return Manifest::make('acme_widgets')
    ->version(0, 1, 0)
    ->depends('base');
PHP);

    file_put_contents($modulePath.'/models/widget.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Addons\AcmeWidgets\Models;

use Velm\Fields\CharField;
use Velm\Models\Model;

class Widget extends Model
{
    protected static ?string $name = 'acme.widget';

    public static function defineFields(): array
    {
        return ['name' => CharField::make()->required()];
    }
}
PHP);

    $discovered = ModuleModelDiscovery::discover($modulePath, 'acme_widgets');

    expect($discovered)->toBe(['Addons\AcmeWidgets\Models\Widget']);
});

test('manifest models merge with discovered models for non-conventional paths', function (): void {
    $spec = ModuleSpec::fromManifest(
        Manifest::make('partners_ext')
            ->version(0, 1, 0)
            ->depends('partners')
            ->models(\Velm\Modules\Tests\Support\PartnerExtension::class)
            ->toArray(),
        dirname(__DIR__, 2).'/tests/fixtures/partners_ext',
    );

    expect($spec->models)->toContain(\Velm\Modules\Tests\Support\PartnerExtension::class);
});

test('registration order places one2many comodels before owners', function (): void {
    $path = dirname(__DIR__, 4).'/apps/skeleton/addons/demo_relations';

    if (! is_dir($path)) {
        skip('demo_relations addon is not available.');
    }

    $classes = ModuleModelDiscovery::discover($path, 'demo_relations');

    $projectIdx = array_search(Addons\DemoRelations\Models\Project::class, $classes, true);
    $taskIdx = array_search(Addons\DemoRelations\Models\Task::class, $classes, true);

    expect($projectIdx)->not->toBeFalse()
        ->and($taskIdx)->not->toBeFalse()
        ->and($taskIdx)->toBeLessThan($projectIdx);
});

test('partners module discovers models without explicit manifest models key', function (): void {
    $path = dirname(__DIR__, 2).'/modules/partners';
    $manifest = require $path.'/__velm__.php';
    $array = $manifest instanceof Manifest ? $manifest->toArray() : $manifest;
    unset($array['MODELS']);

    $spec = ModuleSpec::fromManifest($array, $path);

    expect($spec->models)->toHaveCount(2);
});
