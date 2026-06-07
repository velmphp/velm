<?php

declare(strict_types=1);

namespace Velm\Console\Tests\Support;

use Illuminate\Support\Facades\DB;

final class ResPartnerSchemaHelper
{
    /**
     * Recreate res_partner with a nullable name column so schema diff reports set_not_null.
     */
    public static function recreateWithNullableName(bool $withNullNameRow = false): void
    {
        $columns = DB::select('PRAGMA table_info("res_partner")');

        $createParts = [];
        $selectColumns = [];

        foreach ($columns as $column) {
            $name = (string) $column->name;
            $type = (string) ($column->type ?: 'TEXT');
            $selectColumns[] = '"'.$name.'"';

            if ($name === 'id') {
                $createParts[] = '"id" INTEGER PRIMARY KEY AUTOINCREMENT';

                continue;
            }

            $notNull = $name === 'name' ? '' : ((int) $column->notnull === 1 ? ' NOT NULL' : '');
            $default = $column->dflt_value !== null ? ' DEFAULT '.$column->dflt_value : '';
            $createParts[] = '"'.$name.'" '.$type.$notNull.$default;
        }

        DB::statement('PRAGMA foreign_keys=OFF');
        DB::statement('DROP TABLE IF EXISTS "_res_partner_backup"');
        DB::statement('CREATE TABLE "_res_partner_backup" AS SELECT * FROM "res_partner"');
        DB::statement('DROP TABLE "res_partner"');
        DB::statement('CREATE TABLE "res_partner" ('.implode(', ', $createParts).')');
        DB::statement(
            'INSERT INTO "res_partner" ('.implode(', ', $selectColumns).') '
            .'SELECT '.implode(', ', $selectColumns).' FROM "_res_partner_backup"',
        );
        DB::statement('DROP TABLE "_res_partner_backup"');
        DB::statement('PRAGMA foreign_keys=ON');

        if ($withNullNameRow) {
            DB::table('res_partner')->insert([
                'name' => null,
                'active' => 1,
                'is_company' => 0,
            ]);
        }
    }
}
