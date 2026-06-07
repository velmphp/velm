<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Fields\TextField;
use Velm\Models\Model;
use Velm\Recordset\Recordset;
use Velm\Storage\AttachmentStorage;

final class TestAttachment extends Model
{
    protected static ?string $name = 'ir.attachment';

    protected static ?string $table = 'ir_attachment';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'datas_fname' => CharField::make()->label('Filename'),
            'mimetype' => CharField::make()->default('application/octet-stream')->label('Content Type'),
            'file_size' => IntegerField::make()->label('Size'),
            'res_model' => CharField::make()->label('Linked Model'),
            'res_id' => IntegerField::make()->label('Linked Record'),
            'type' => CharField::make()->default('binary')->label('Type'),
            'url' => CharField::make()->label('URL'),
            'storage_key' => CharField::make()->label('Storage Key'),
            'datas' => TextField::make()->label('Data'),
            'public' => BooleanField::make()->default(false)->label('Public'),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fetchContentFromRow(array $row): string
    {
        $type = (string) ($row['type'] ?? 'binary');

        if ($type === 'url') {
            return '';
        }

        $datas = $row['datas'] ?? null;

        if (is_string($datas) && $datas !== '') {
            $decoded = base64_decode($datas, true);

            return $decoded !== false ? $decoded : '';
        }

        $storageKey = (string) ($row['storage_key'] ?? '');

        if ($storageKey === '') {
            return '';
        }

        try {
            return AttachmentStorage::backend()->load($storageKey);
        } catch (\Throwable) {
            return '';
        }
    }

    public function fetchContent(Recordset $rs): string
    {
        $rs->ensureOne();
        $rows = $rs->read(['type', 'datas', 'storage_key']);

        return self::fetchContentFromRow($rows[0] ?? []);
    }

    public function unlink(Recordset $rs): void
    {
        if ($rs->count() === 0) {
            return;
        }

        $rows = $rs->read(['storage_key']);
        $keys = [];

        foreach ($rows as $row) {
            $key = (string) ($row['storage_key'] ?? '');

            if ($key !== '') {
                $keys[] = $key;
            }
        }

        $rs->unlinkFromDatabase();

        $backend = AttachmentStorage::backend();

        foreach ($keys as $key) {
            try {
                $backend->delete($key);
            } catch (\Throwable) {
            }
        }
    }
}
