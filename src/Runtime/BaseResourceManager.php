<?php

namespace Velm\Core\Runtime;

use Velm\Core\Compiler\DomainType;

abstract class BaseResourceManager
{
    protected string $cachePath;

    public function __construct(protected readonly bool $readOnly = false)
    {
        $this->cachePath = $this->getType()->path();
        if (! $this->readOnly && ! file_exists($this->cachePath)) {
            mkdir($this->cachePath, 0775, true);
        }
    }

    abstract protected function getCodeStub(string $className, string $logicalName): string;

    abstract protected function getType(): DomainType;

    public function generate(string $path, string $className, string $logicalName): void
    {
        $suffix = str($this->getType()->value)->snake()->lower()->append('_')->toString();
        $code = $this->getCodeStub($className, $logicalName);

        $tmp = tempnam(dirname($path), $suffix);
        file_put_contents($tmp, $code);
        rename($tmp, $path);

        if ($this->readOnly) {
            chmod($path, 0664);
        }
    }

    public function instance(string $logicalName, array $parameters = []): object
    {
        $suffix = str($this->getType()->value)->singular()->studly()->toString();
        $baseName = velm_utils()->getBaseClassName($logicalName, $suffix);
        $safeName = $this->sanitize($baseName);
        $fqcn = $this->getType()->namespace($safeName);
        $filePath = $this->getType()->path("{$safeName}.php");

        if (! class_exists($fqcn)) {
            if (! file_exists($filePath)) {
                if ($this->readOnly) {
                    throw new \RuntimeException("$suffix '{$logicalName}' not found. Did you run optimize?");
                }
                $this->generate($filePath, $safeName, $logicalName);
            }
            require_once $filePath;
        }

        return new $fqcn(...$parameters);
    }

    protected function sanitize(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
    }
}
