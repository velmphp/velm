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

    public static function getModelsPath(string $subpath = ''): string;

    public static function getMigrationsPath(string $subpath = ''): string;

    public static function getFactoriesPath(string $subpath = ''): string;

    public static function getSeedersPath(string $subpath = ''): string;

    public static function getRoutesPath(string $subpath = ''): string;

    public static function getViewsPath(string $subpath = ''): string;

    public static function getTranslationsPath(string $subpath = ''): string;

    public static function getConfigPath(string $subpath = ''): string;

    public static function getAssetsPath(string $subpath = ''): string;

    public static function getPoliciesPath(string $subpath = ''): string;

    public static function getCommandsPath(string $subpath = ''): string;

    public static function getAppPath(string $subpath = ''): string;

    public function registering(): void;

    public function registered(): void;

    public function booting(): void;

    public function booted(): void;

    public function destroy(): void;
}
