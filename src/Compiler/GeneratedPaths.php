<?php

namespace Velm\Core\Compiler;

class GeneratedPaths
{
    public static function base(string $path = ''): string
    {
        $path = str($path)->ltrim('/\\')->prepend('framework/cache/velm/')->rtrim('/\\')->toString();

        return storage_path($path);
    }
}
