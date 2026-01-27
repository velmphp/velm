<?php

namespace Velm\Core\Metadata\Contracts;

use Velm\Core\Metadata\VelmModelMetadata;

interface ProvidesVelmMetadata
{
    public static function metadata(): VelmModelMetadata;
}
