<?php

declare(strict_types=1);

namespace Velm\Modules\Base\Models;

use Illuminate\Support\Facades\Hash;
use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\Many2manyField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

/**
 * Velm security principal backed by Laravel's {@code users} table (panel login + ACL uid).
 */
class User extends Model
{
    protected static ?string $name = 'res.users';

    protected static ?string $table = 'users';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'email' => CharField::make()->required()->label('Email')->column('email'),
            'password' => CharField::make()->label('Password'),
            'active' => BooleanField::make()->default(true)->label('Active'),
            'group_ids' => Many2manyField::make('res.groups')->label('Groups'),
            'company_id' => Many2oneField::make('res.company')->label('Company'),
        ];
    }

    /**
     * @return list<string>
     */
    public static function schemaExternalColumns(): array
    {
        return [
            'remember_token',
            'email_verified_at',
            'password',
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function prepareValues(array $values, string $operation): array
    {
        if (! array_key_exists('password', $values)) {
            return $values;
        }

        $password = $values['password'];

        if ($password === null || $password === '') {
            unset($values['password']);

            return $values;
        }

        if (is_string($password) && (str_starts_with($password, '$2y$') || str_starts_with($password, '$argon2'))) {
            return $values;
        }

        $values['password'] = Hash::make((string) $password);

        return $values;
    }
}
