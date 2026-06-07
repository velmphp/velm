<?php

declare(strict_types=1);

use Velm\Cron\CronJob;
use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;

class CronTestPartner extends Model
{
    protected static ?string $name = 'cron.test';

    protected static ?string $table = 'cron_test';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required(),
            'ticked' => BooleanField::make()->default(false),
        ];
    }
}

class CronTestAction extends Model
{
    protected static ?string $name = 'ir.actions.server';

    protected static ?string $table = 'ir_actions_server';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required(),
            'model' => CharField::make()->required(),
            'action_type' => CharField::make()->required(),
            'vals_json' => CharField::make(),
        ];
    }
}

class CronTestCron extends Model
{
    protected static ?string $name = 'ir.cron';

    protected static ?string $table = 'ir_cron';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required(),
            'action_id' => Many2oneField::make('ir.actions.server'),
            'interval_number' => IntegerField::make()->default(1),
            'interval_type' => CharField::make()->default('hours'),
            'nextcall' => CharField::make(),
            'lastcall' => CharField::make(),
            'active' => BooleanField::make()->default(true),
        ];
    }
}

test('cron job run due executes write action and advances schedule', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(CronTestPartner::class);
        $registry->register(CronTestAction::class);
        $registry->register(CronTestCron::class);

        return $registry;
    });
    $env = new Environment($connection, $registry);
    (new SchemaBuilder($connection))->syncRegistry($registry);

    $partner = $env->model('cron.test')->create(['name' => 'Acme', 'ticked' => false]);
    $partnerId = $partner->ids()[0];

    $action = $env->model('ir.actions.server')->create([
        'name' => 'Tick partners',
        'model' => 'cron.test',
        'action_type' => 'write',
        'vals_json' => json_encode(['ticked' => true]),
    ]);

    $past = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify('-1 hour')->format('Y-m-d H:i:s');

    $env->model('ir.cron')->create([
        'name' => 'Tick job',
        'action_id' => $action->ids()[0],
        'interval_number' => 1,
        'interval_type' => 'hours',
        'nextcall' => $past,
        'active' => true,
    ]);

    $executed = CronJob::runDue($env);

    expect($executed)->toBe(['Tick job'])
        ->and($env->model('cron.test')->search([['id', '=', $partnerId]])->read()[0]['ticked'])->toBeTruthy();
});

test('cron job run due returns empty when ir.cron is not registered', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $registry = Registry::using(fn (Registry $registry): Registry => $registry);
    $env = new Environment($connection, $registry);

    expect(CronJob::runDue($env))->toBe([]);
});

test('cron job skips jobs scheduled in the future', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(CronTestPartner::class);
        $registry->register(CronTestAction::class);
        $registry->register(CronTestCron::class);

        return $registry;
    });
    $env = new Environment($connection, $registry);
    (new SchemaBuilder($connection))->syncRegistry($registry);

    $action = $env->model('ir.actions.server')->create([
        'name' => 'Tick partners',
        'model' => 'cron.test',
        'action_type' => 'write',
        'vals_json' => json_encode(['ticked' => true]),
    ]);

    $future = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify('+1 day')->format('Y-m-d H:i:s');

    $env->model('ir.cron')->create([
        'name' => 'Future job',
        'action_id' => $action->ids()[0],
        'nextcall' => $future,
        'active' => true,
    ]);

    expect(CronJob::runDue($env))->toBe([]);
});

test('cron job skips jobs without action id', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(CronTestCron::class);

        return $registry;
    });
    $env = new Environment($connection, $registry);
    (new SchemaBuilder($connection))->syncRegistry($registry);

    $past = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify('-1 hour')->format('Y-m-d H:i:s');

    $env->model('ir.cron')->create([
        'name' => 'No action',
        'action_id' => 0,
        'nextcall' => $past,
        'active' => true,
    ]);

    expect(CronJob::runDue($env))->toBe([]);
});

test('cron job executes create unlink and advances on missing target model', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(CronTestPartner::class);
        $registry->register(CronTestAction::class);
        $registry->register(CronTestCron::class);

        return $registry;
    });
    $env = new Environment($connection, $registry);
    (new SchemaBuilder($connection))->syncRegistry($registry);

    $partner = $env->model('cron.test')->create(['name' => 'Delete me']);
    $partnerId = $partner->ids()[0];
    $past = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify('-1 hour')->format('Y-m-d H:i:s');

    $unlinkAction = $env->model('ir.actions.server')->create([
        'name' => 'Unlink partners',
        'model' => 'cron.test',
        'action_type' => 'unlink',
        'vals_json' => '{}',
    ]);
    $env->model('ir.cron')->create([
        'name' => 'Unlink job',
        'action_id' => $unlinkAction->ids()[0],
        'nextcall' => $past,
        'interval_type' => 'minutes',
        'interval_number' => 5,
        'active' => true,
    ]);

    expect(CronJob::runDue($env))->toBe(['Unlink job'])
        ->and($env->model('cron.test')->search([['id', '=', $partnerId]])->count())->toBe(0);

    $createAction = $env->model('ir.actions.server')->create([
        'name' => 'Create partner',
        'model' => 'cron.test',
        'action_type' => 'create',
        'vals_json' => json_encode(['name' => 'Created by cron']),
    ]);
    $env->model('ir.cron')->create([
        'name' => 'Create job',
        'action_id' => $createAction->ids()[0],
        'nextcall' => $past,
        'interval_type' => 'days',
        'active' => true,
    ]);

    CronJob::runDue($env);

    expect($env->model('cron.test')->search([['name', '=', 'Created by cron']])->count())->toBe(1);

    $missingModelAction = $env->model('ir.actions.server')->create([
        'name' => 'Missing model',
        'model' => 'missing.model',
        'action_type' => 'write',
        'vals_json' => '{}',
    ]);
    $cron = $env->model('ir.cron')->create([
        'name' => 'Missing model job',
        'action_id' => $missingModelAction->ids()[0],
        'nextcall' => $past,
        'interval_type' => 'weeks',
        'active' => true,
    ]);
    $cronId = $cron->ids()[0];

    CronJob::runDue($env);

    $row = $env->model('ir.cron')->search([['id', '=', $cronId]])->read()[0];

    expect($row['lastcall'])->not->toBe('')
        ->and($row['nextcall'])->not->toBe('');
});

test('cron job skips when server actions model is missing', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(CronTestCron::class);

        return $registry;
    });
    $env = new Environment($connection, $registry);
    (new SchemaBuilder($connection))->syncRegistry($registry);

    $past = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify('-1 hour')->format('Y-m-d H:i:s');

    $env->model('ir.cron')->create([
        'name' => 'Orphan job',
        'action_id' => 99,
        'nextcall' => $past,
        'active' => true,
    ]);

    expect(CronJob::runDue($env))->toBe([]);
});

test('cron job skips when action row is missing', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(CronTestAction::class);
        $registry->register(CronTestCron::class);

        return $registry;
    });
    $env = new Environment($connection, $registry);
    (new SchemaBuilder($connection))->syncRegistry($registry);

    $past = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify('-1 hour')->format('Y-m-d H:i:s');

    $env->model('ir.cron')->create([
        'name' => 'Missing action row',
        'action_id' => 999,
        'nextcall' => $past,
        'active' => true,
    ]);

    expect(CronJob::runDue($env))->toBe([]);
});

test('cron job tolerates invalid vals json on write action', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(CronTestPartner::class);
        $registry->register(CronTestAction::class);
        $registry->register(CronTestCron::class);

        return $registry;
    });
    $env = new Environment($connection, $registry);
    (new SchemaBuilder($connection))->syncRegistry($registry);

    $env->model('cron.test')->create(['name' => 'Acme', 'ticked' => false]);
    $past = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify('-1 hour')->format('Y-m-d H:i:s');

    $action = $env->model('ir.actions.server')->create([
        'name' => 'Bad json',
        'model' => 'cron.test',
        'action_type' => 'write',
        'vals_json' => 'not-json',
    ]);

    $env->model('ir.cron')->create([
        'name' => 'Bad json job',
        'action_id' => $action->ids()[0],
        'nextcall' => $past,
        'active' => true,
    ]);

    expect(CronJob::runDue($env))->toBe(['Bad json job']);
});

test('cron job continues schedule when action execution fails', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(CronTestAction::class);
        $registry->register(CronTestCron::class);

        return $registry;
    });
    $env = new Environment($connection, $registry);
    (new SchemaBuilder($connection))->syncRegistry($registry);

    $past = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify('-1 hour')->format('Y-m-d H:i:s');

    $action = $env->model('ir.actions.server')->create([
        'name' => 'Bad action',
        'model' => 'cron.test',
        'action_type' => 'frobnicate',
        'vals_json' => '{}',
    ]);
    $cron = $env->model('ir.cron')->create([
        'name' => 'Bad job',
        'action_id' => $action->ids()[0],
        'nextcall' => $past,
        'active' => true,
    ]);
    $cronId = $cron->ids()[0];

    expect(CronJob::runDue($env))->toBe([]);

    $row = $env->model('ir.cron')->search([['id', '=', $cronId]])->read()[0];

    expect($row['lastcall'])->not->toBe('')
        ->and($row['nextcall'])->not->toBe('');
});
