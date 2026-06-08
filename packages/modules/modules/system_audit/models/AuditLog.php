<?php

declare(strict_types=1);

namespace Velm\Modules\SystemAudit\Models;

use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2oneField;
use Velm\Fields\TextField;
use Velm\Models\Model;

final class AuditLog extends Model
{
    protected static ?string $name = 'ir.audit.log';

    protected static ?string $table = 'ir_audit_log';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Summary'),
            'model' => CharField::make()->label('Model'),
            'res_id' => IntegerField::make()->label('Record'),
            'action' => CharField::make()->required()->label('Action'),
            'user_id' => Many2oneField::make('res.users')->label('User'),
            'company_id' => Many2oneField::make('res.company')->label('Company'),
            'old_values' => TextField::make()->label('Before'),
            'new_values' => TextField::make()->label('After'),
            'ip_address' => CharField::make()->label('IP address'),
            'user_agent' => CharField::make()->label('User agent'),
        ];
    }
}
