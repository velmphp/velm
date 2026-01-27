<?php

namespace Velm\Core\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Velm\Core\Concerns\BelongsToVelmModule;
use Velm\Core\Domain\Contracts\VelmModelContract;

abstract class VelmModel extends Model implements VelmModelContract
{
    use BelongsToVelmModule;
}
