<?php

namespace Velm\Core\Compiler;

final class ModelAttributeRules
{
    public const MERGEABLE = [
        'fillable',
        'guarded',
        'hidden',
        'casts',
        'appends',
        'with',
        'touches',
    ];

    public const SINGLETON = [
        'table',
        'primaryKey',
        'keyType',
        'incrementing',
        'connection',
    ];
}
