<?php
namespace Velm\Core\Contracts;
interface VelmModuleContract
{
    public static function slug(): string;
    public static function name(): string;
    public static function packageName(): string;
    public static function version(): string;
    public static function description(): string;
    public static function documentation(): string;
}
