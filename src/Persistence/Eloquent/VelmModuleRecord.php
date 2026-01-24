<?php

namespace Velm\Core\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VelmModuleRecord extends Model
{
    protected $table = 'velm_modules';

    protected $fillable = [
        'package',
        'version',
        'tenant',
        'is_enabled',
        'enabled_at',
        'disabled_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'enabled_at' => 'datetime',
        'disabled_at' => 'datetime',
    ];

    #[Scope]
    public function findForTenant($query, string $package, ?string $tenant = null): Builder
    {
        $query->where('package', $package);

        if (filled($tenant)) {
            $query->where('tenant', $tenant);
        } else {
            $query->whereNull('tenant');
        }

        return $query;
    }

    public function getEnabledAttribute()
    {
        return $this->is_enabled;
    }

    public function getDisabledAttribute()
    {
        return ! $this->is_enabled;
    }
}
