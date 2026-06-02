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

    $connection->execute(
        'CREATE TABLE "cron_test" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" TEXT NOT NULL, "ticked" INTEGER DEFAULT 0)',
    );
    $connection->execute(
        'CREATE TABLE "ir_actions_server" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" TEXT NOT NULL, "model" TEXT NOT NULL, "action_type" TEXT NOT NULL, "vals_json" TEXT)',
    );
    $connection->execute(
        'CREATE TABLE "ir_cron" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" TEXT NOT NULL, "action_id" INTEGER, "interval_number" INTEGER, "interval_type" TEXT, "nextcall" TEXT, "lastcall" TEXT, "active" INTEGER)',
    );

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
