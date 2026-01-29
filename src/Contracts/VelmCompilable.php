<?php

namespace Velm\Core\Contracts;

interface VelmCompilable
{
    public static function getCompiler(): VelmCompilerContract;

    public static function compile(bool $lazy = false): void;
}
