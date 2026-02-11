<?php

namespace Velm\Core\Runtime;

use Velm\Core\Compiler\DomainType;

class ModelManager extends BaseResourceManager
{
    protected function getType(): DomainType
    {
        return DomainType::Models;
    }

    protected function getCodeStub(string $className, string $logicalName): string
    {
        return "<?php\n\nnamespace Velm\\Models;\n\n".
            "/**\n * Generated Model - Do not edit manually.\n */\n".
            "final class {$className} extends \\Velm\\Core\\Runtime\\RuntimeLogicalModel {\n".
            "    public static string \$logicalName = '{$logicalName}';\n".
            '}';
    }
}
