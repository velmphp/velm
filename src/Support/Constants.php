<?php

namespace Velm\Core\Support;

final class Constants
{
    public const MODULES_DIRECTORY = 'modules';

    public const ARRAY_MODEL_ATTRIBUTES = [
        'fillable',
        'guarded',
        'casts',
        'hidden',
        'appends',
        'with',
        'withCount',
    ];

    public const PRIMITIVE_MODEL_ATTRIBUTES = [
        'table',
        'connection',
        'primaryKey',
        'keyType',
        'incrementing',
        'timestamps',
    ];
}
