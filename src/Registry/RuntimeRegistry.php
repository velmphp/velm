<?php

namespace Velm\Core\Registry;

use Velm\Core\Compiler\GeneratedPaths;

class RuntimeRegistry
{
    public static array $pipelines = [];

    public static array $relationships = [];

    public function __construct()
    {
        static::load();
    }

    public static function load(): void
    {
        static::$pipelines = require GeneratedPaths::base('pipelines.php');
        static::$relationships = require GeneratedPaths::base('relationships.php');
    }
}
