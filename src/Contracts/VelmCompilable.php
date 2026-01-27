<?php

namespace Velm\Core\Contracts;

interface VelmCompilable
{
    public static function getCompiler(): VelmCompilerContract;
}
