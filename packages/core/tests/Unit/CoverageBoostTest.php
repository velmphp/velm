<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\BadComputeArticle;
use Velm\Core\Tests\Support\ComputedArticle;
use Velm\Core\Tests\Support\Country;
use Velm\Core\Tests\Support\TestAttachment;
use Velm\Core\Tests\Support\UnsupportedFieldModel;
use Velm\Computed\ComputeRunner;
use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Fields\CharField;
use Velm\Fields\DatetimeField;
use Velm\Fields\Many2oneField;
use Velm\Registry;
use Velm\Schema\FieldBlueprint;
use Velm\Schema\LaravelSchema;
use Velm\Schema\SchemaBuilder;
use Velm\Schema\SchemaDiffer;
use Velm\Storage\AttachmentStorage;
use Velm\Storage\LocalStorageBackend;
use Illuminate\Database\Schema\Blueprint;

test('datetime field sql type and conversions', function (): void {
    $field = DatetimeField::make('Created', default: '2026-01-01 00:00:00', required: true);

    expect($field->sqlType())->toBe('TIMESTAMP')
        ->and($field->toPhp(null))->toBeNull()
        ->and($field->toPhp('2026-01-01'))->toBe('2026-01-01')
        ->and($field->toSql(null))->toBeNull()
        ->and($field->toSql(new DateTimeImmutable('2026-06-03 12:00:00')))->toBe('2026-06-03 12:00:00');
});

test('many2one field sql type succeeds with comodel', function (): void {
    $field = Many2oneField::make('res.country')->required();

    expect($field->sqlType())->toBe('INTEGER')
        ->and($field->toPhp(null))->toBeNull()
        ->and($field->toPhp('7'))->toBe(7);
});

test('field blueprint rejects unsupported field types', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $schema = LaravelSchema::for($connection);
    $field = UnsupportedFieldModel::fields()['custom'];

    expect(fn () => $schema->createModelTable('test_unsupported', [$field]))
        ->toThrow(InvalidArgumentException::class, 'cannot be mapped to a schema column');
});

test('field blueprint defineColumn throws for unknown field class', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $schema = LaravelSchema::for($connection);
    $schema->createModelTable('probe_table', [CharField::make()->bind('name')]);
    $field = UnsupportedFieldModel::fields()['custom']->bind('custom');

    expect(fn () => $schema->addFieldColumn('probe_table', $field))
        ->toThrow(InvalidArgumentException::class);
});

test('attachment model fetchContentFromRow handles url datas and storage', function (): void {
    AttachmentStorage::resolveUsing(null);
    AttachmentStorage::resetBackendCache();

    expect(TestAttachment::fetchContentFromRow(['type' => 'url']))->toBe('')
        ->and(TestAttachment::fetchContentFromRow(['type' => 'binary', 'datas' => base64_encode('hello')]))->toBe('hello')
        ->and(TestAttachment::fetchContentFromRow(['type' => 'binary', 'datas' => '!!!']))->toBe('')
        ->and(TestAttachment::fetchContentFromRow(['type' => 'binary', 'storage_key' => '']))->toBe('');

    $root = sys_get_temp_dir().'/velm-att-probe-'.uniqid('', true);
    mkdir($root, 0775, true);
    $backend = new LocalStorageBackend($root);
    $key = $backend->save('probe.txt', 'payload');

    AttachmentStorage::resolveUsing(fn () => $backend);

    expect(TestAttachment::fetchContentFromRow(['type' => 'binary', 'storage_key' => $key]))->toBe('payload')
        ->and(TestAttachment::fetchContentFromRow(['type' => 'binary', 'storage_key' => 'missing/key']))->toBe('');
});

test('attachment fetchContent and unlink no-op paths', function (): void {
    AttachmentStorage::resolveUsing(null);
    AttachmentStorage::resetBackendCache();

    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(TestAttachment::class);
        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry);
    });

    $empty = $env->model('ir.attachment')->search([['id', '=', -1]]);
    $empty->unlink();

    $key = AttachmentStorage::backend()->save('inline.txt', 'bytes');
    $att = $env->model('ir.attachment')->create([
        'name' => 'inline.txt',
        'storage_key' => $key,
    ]);

    expect($att->fetchContent($att))->toBe('bytes');

    $att->write(['storage_key' => '']);
    expect($att->fetchContent($att))->toBe('');
});

test('laravel schema skips existing tables columns and alters nullability', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $schema = LaravelSchema::for($connection);

    $schema->createModelTable('res_country', Country::fields());
    $schema->createModelTable('res_country', Country::fields());

    $schema->addFieldColumn('res_country', Country::fields()['name']);

    $schema->createMigrationTable('migration_dup', []);
    $schema->createMigrationTable('migration_dup', []);

    $schema->addMigrationColumn('migration_dup', new Velm\Migrations\ColumnDefinition('extra', 'TEXT', true));

    $schema->setColumnNullable('res_country', Country::fields()['code'], true);

    expect($schema->columnListing('res_country'))->toContain('code')
        ->and($schema->columnListing('migration_dup'))->toContain('extra');
});

test('compute runner edge cases and unstored fill', function (): void {
    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(BadComputeArticle::class);
        $registry->register(ComputedArticle::class);
        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry);
    });

    $runner = new ComputeRunner($env);
    $bad = $env->model('test.bad.compute')->create(['title' => 'x']);
    $article = $env->model('test.article')->create(['title' => 'Velm']);

    $runner->recomputeAfterWrite($article, []);
    $runner->fillUnstoredForRead($article, ['headline']);

    expect($article->read(['headline'])[0]['headline'])->toBe('Velm')
        ->and(fn () => $runner->compute($bad, 'broken'))
        ->toThrow(RuntimeException::class, 'must return an array');
});

test('pdo connection driver mapping and illuminate reuse', function (): void {
    $connection = PdoConnection::sqliteMemory();

    expect($connection->driver())->toBe('sqlite')
        ->and($connection->illuminateConnection())->toBe($connection->illuminateConnection());

    $connection->execute('CREATE TABLE probe (id INTEGER PRIMARY KEY, note TEXT)');
    $connection->execute('INSERT INTO probe (note) VALUES (?)', ['hello']);

    expect($connection->fetchOne('SELECT note FROM probe WHERE id = ?', [1])['note'])->toBe('hello')
        ->and($connection->lastInsertId())->toBe(1);
});

test('pdo connection maps mysql driver and rejects schema operations', function (): void {
    $pdo = new class('sqlite::memory:') extends PDO {
        public function getAttribute(int $attribute): mixed
        {
            if ($attribute === PDO::ATTR_DRIVER_NAME) {
                return 'mysql';
            }

            return parent::getAttribute($attribute);
        }
    };
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $connection = new PdoConnection($pdo);

    expect($connection->driver())->toBe('mysql')
        ->and(fn () => $connection->illuminateConnection())
        ->toThrow(RuntimeException::class, 'only supported for sqlite');
});

test('registry extension and mixin validation errors', function (): void {
    expect(fn () => Registry::using(function (Registry $registry): void {
        $registry->registerExtension(Country::class);
    }))->toThrow(RuntimeException::class, 'must set $inherit');

    expect(fn () => Registry::using(function (Registry $registry): void {
        $registry->registerMixin(Country::class);
    }))->toThrow(RuntimeException::class, 'must set $abstract = true');

    Registry::using(function (Registry $registry): void {
        expect(fn () => $registry->baseModelClass('missing.model'))
            ->toThrow(InvalidArgumentException::class)
            ->and($registry->extensionsFor('missing.model'))->toBe([]);
    });
});

test('registry guards duplicate abstract and mixin registration', function (): void {
    expect(fn () => Registry::using(function (Registry $registry): void {
        $registry->register(\Velm\Core\Tests\Support\AbstractMixinProbe::class);
    }))->toThrow(RuntimeException::class, 'abstract');

    expect(fn () => Registry::using(function (Registry $registry): void {
        $registry->register(Country::class);
        $registry->register(Country::class);
    }))->toThrow(RuntimeException::class, 'already registered');

    expect(fn () => Registry::using(function (Registry $registry): void {
        $registry->registerMixin(\Velm\Core\Tests\Support\AbstractMixinProbe::class);
        $registry->registerMixin(\Velm\Core\Tests\Support\AbstractMixinProbe::class);
    }))->toThrow(RuntimeException::class, 'already registered');

    Registry::using(function (Registry $registry): void {
        expect(fn () => $registry->modelClass('missing.model'))
            ->toThrow(InvalidArgumentException::class)
            ->and(fn () => $registry->fieldSet('missing.model'))
            ->toThrow(InvalidArgumentException::class)
            ->and($registry->field('res.country', 'missing'))->toBeNull()
            ->and($registry->fieldsFor('missing.model'))->toBe([]);
    });

    expect(fn () => Registry::active())->toThrow(RuntimeException::class);
});

test('schema differ apply executes drop not null alterations on mysql-capable schema', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(\Velm\Core\Tests\Support\Partner::class);

        return $registry;
    });

    $connection->execute(
        'CREATE TABLE res_partner (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, active INTEGER NOT NULL DEFAULT 1, country_id INTEGER)',
    );

    $differ = new SchemaDiffer($connection);
    $diff = $differ->compute($registry, [\Velm\Core\Tests\Support\Partner::class]);

    expect(collect($diff->alterations)->pluck('kind')->all())->toContain('drop_not_null');
});

test('local storage backend read failure throws', function (): void {
    $root = sys_get_temp_dir().'/velm-storage-readfail-'.uniqid('', true);
    mkdir($root, 0775, true);
    $backend = new LocalStorageBackend($root);
    $key = $backend->save('broken.txt', 'x');
    unlink($root.'/'.$key);

    expect(fn () => $backend->load($key))
        ->toThrow(RuntimeException::class, 'Attachment file not found');
});

test('attachment storage config paths without explicit resolver', function (): void {
    AttachmentStorage::resolveUsing(null);
    AttachmentStorage::resetBackendCache();

    expect(AttachmentStorage::backend())->toBeInstanceOf(LocalStorageBackend::class)
        ->and(AttachmentStorage::fallbackLocalRoot())->toContain('velm');
});

test('compute runner short-circuits empty ids and invalid fields', function (): void {
    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(ComputedArticle::class);
        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry);
    });

    $runner = new ComputeRunner($env);
    $empty = $env->model('test.article')->search([['id', '=', -1]]);
    $article = $env->model('test.article')->create(['title' => 'Velm']);

    $runner->compute($empty, 'headline');
    $runner->recomputeAfterWrite($article, ['missing_field']);

    expect(fn () => $runner->compute($article, 'title'))
        ->toThrow(InvalidArgumentException::class, 'not computed');
});

test('registry registerMixin and mail thread detection', function (): void {
    Registry::using(function (Registry $registry): void {
        $registry->registerMixin(\Velm\Core\Tests\Support\AbstractMixinProbe::class);
        $registry->register(\Velm\Core\Tests\Support\MailThreadProbe::class);

        expect($registry->hasMixin('test.mail.thread', 'mail.thread'))->toBeTrue()
            ->and($registry->computedGraph())->not->toBeNull();
    });
});

test('local storage backend mkdir and write failures', function (): void {
    $blockedRoot = sys_get_temp_dir().'/velm-blocked-root-'.uniqid('', true);
    file_put_contents($blockedRoot, 'not-a-directory');
    $backend = new LocalStorageBackend($blockedRoot);

    expect(fn () => $backend->save('blocked.txt', 'data'))
        ->toThrow(RuntimeException::class, 'Could not create attachment directory');

    @unlink($blockedRoot);
});

test('local storage backend delete prunes parent directories', function (): void {
    $root = sys_get_temp_dir().'/velm-storage-prune-'.uniqid('', true);
    mkdir($root, 0775, true);
    $backend = new LocalStorageBackend($root);
    $key = $backend->save('deep.txt', 'payload');

    $backend->delete($key);

    expect(is_dir($root))->toBeTrue();
});
