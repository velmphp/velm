<?php

namespace Velm\Core\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
abstract class BaseModel
{
    public static int $velm_priority = 0;
    // Marker class for domain model compilation
}
