<?php

namespace Velm\Core\Compiler;

enum DomainType: string
{
    case Models = 'Models';
    case Policies = 'Policies';
    case Forms = 'Forms';
    case Services = 'Services';

    public function namespace(string $relative = ''): string
    {
        $ns = match ($this) {
            DomainType::Models => 'Velm\\Models',
            DomainType::Policies => 'Velm\\Policies',
            DomainType::Forms => 'Velm\\Forms',
            DomainType::Services => 'Velm\\Services',
        };
        if ($relative) {
            $ns .= '\\'.trim(str_replace(DIRECTORY_SEPARATOR, '\\', $relative), '\\');
        }

        return $ns;
    }

    public function path(string $subpath = ''): string
    {
        $relativeNs = str_replace('Velm\\', '', $this->namespace());
        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeNs);
        $relativePath = trim($relativePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.trim($subpath, DIRECTORY_SEPARATOR);
        $path = GeneratedPaths::base($relativePath);
        // Ensure the directory exists
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $path;
    }
}
