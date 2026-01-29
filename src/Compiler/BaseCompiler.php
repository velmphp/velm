<?php

namespace Velm\Core\Compiler;

use Velm\Core\Contracts\VelmCompilerContract;
use Velm\Core\Domain\Models\VelmModelProxy;

abstract class BaseCompiler implements VelmCompilerContract
{
    /**
     * Determine if a proxy class is stale and needs recompilation.
     *
     * @var class-string<VelmModelProxy>
     *
     * @throws \ReflectionException
     */
    public function isStale(string $proxy): bool
    {
        if (! class_exists($proxy)) {
            return true;
        }
        // Get the modify time of the proxy class file and compare with all the source files.
        $logicalName = $proxy::$logicalName;
        $sourceFiles = \Velm::registry()->models()->definitions($logicalName);
        $proxyFile = new \ReflectionClass($proxy)->getFileName();
        $proxyMTime = filemtime($proxyFile);
        foreach ($sourceFiles as $sourceFile) {
            $sourceReflection = new \ReflectionClass($sourceFile);
            $sourceFileName = $sourceReflection->getFileName();
            if (filemtime($sourceFileName) > $proxyMTime) {
                return true;
            }
        }

        return false;
    }
}
