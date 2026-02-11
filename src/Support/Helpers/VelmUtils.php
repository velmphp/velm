<?php

namespace Velm\Core\Support\Helpers;

use function Laravel\Prompts\alert;
use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\warning;

enum ConsoleLogType: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case ALERT = 'alert';
    case NOTE = 'note';
    case INTRO = 'intro';
    case OUTRO = 'outro';
    case ERROR = 'error';

    case SUCCESS = 'success';
}
class VelmUtils
{
    public function consoleLog(string $message, ConsoleLogType $type = ConsoleLogType::INFO): void
    {
        if (app()->runningInConsole()) {
            match ($type) {
                ConsoleLogType::INFO => \Laravel\Prompts\info($message),
                ConsoleLogType::WARNING => warning("⚠️ {$message}"),
                ConsoleLogType::ALERT => alert("🚨 {$message}"),
                ConsoleLogType::NOTE => note("📝 {$message}"),
                ConsoleLogType::INTRO => intro($message),
                ConsoleLogType::OUTRO => outro($message),
                ConsoleLogType::ERROR => error("❌ {$message}"),
                ConsoleLogType::SUCCESS => outro("✅ {$message}"),
                default => note($message),
            };
        }
    }

    public function inspectDynamicClass(string $fqcn): string
    {
        $classPath = $fqcn;
        if (! class_exists($classPath)) {
            return "Class {$classPath} does not exist.";
        }

        $reflection = new \ReflectionClass($classPath);
        $output = [];

        // 1. Namespace
        if ($reflection->inNamespace()) {
            $output[] = 'namespace '.$reflection->getNamespaceName().';';
        }

        // 2. Class Declaration
        $declaration = 'class '.$reflection->getShortName();

        if ($parent = $reflection->getParentClass()) {
            $declaration .= ' extends \\'.$parent->getName();
        }

        $interfaces = $reflection->getInterfaceNames();
        if (! empty($interfaces)) {
            $declaration .= ' implements '.implode(', ', array_map(fn ($i) => "\\$i", $interfaces));
        }

        $output[] = $declaration."\n{";

        // 3. Traits
        foreach ($reflection->getTraitNames() as $trait) {
            $output[] = "    use \\{$trait};";
        }

        // 4. Properties
        foreach ($reflection->getProperties() as $prop) {
            $modifiers = implode(' ', \Reflection::getModifierNames($prop->getModifiers()));
            $output[] = "    {$modifiers} \${$prop->getName()};";
        }

        // 5. Methods (Signatures only)
        foreach ($reflection->getMethods() as $method) {
            $modifiers = implode(' ', \Reflection::getModifierNames($method->getModifiers()));
            $params = [];
            foreach ($method->getParameters() as $param) {
                $paramStr = ($param->hasType() ? $param->getType().' ' : '').'$'.$param->getName();
                if ($param->isDefaultValueAvailable()) {
                    $paramStr .= ' = '.var_export($param->getDefaultValue(), true);
                }
                $params[] = $paramStr;
            }
            $output[] = "\n    {$modifiers} function {$method->getName()}(".implode(', ', $params).')';
            $output[] = "    { \n        // Body is inaccessible for eval'd code \n    }";
        }

        $output[] = '}';

        return implode("\n", $output);
    }

    public function formatVelmName(string $logicalName, ?string $suffix = null): string
    {
        // Replace dots with dashes and convert to kebab case
        $baseName = str_replace('.', '-', $logicalName);
        $baseName = str($baseName)->kebab()->studly();
        if ($suffix) {
            $suffix = str($suffix)->studly()->toString();
            // if string doesn't already end with suffix, append it
            if (! str_ends_with($baseName, $suffix)) {
                $baseName = $baseName->append($suffix);
            }
        }

        return $baseName->toString();
    }

    public function getBaseClassName(string $logicalName, ?string $suffix = null): string
    {
        $baseName = $this->formatVelmName($logicalName, $suffix);
        if ((! $suffix || $suffix === 'Model') && str_ends_with($baseName, 'Model')) {
            return str($baseName)->replaceLast('Model', '')->toString();
        }

        return $baseName;
    }
}
