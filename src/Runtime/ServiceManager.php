<?php

namespace Velm\Core\Runtime;

use Velm\Core\Compiler\DomainType;

class ServiceManager extends BaseResourceManager
{
    protected function getCodeStub($className, $logicalName): string
    {
        return "<?php\n\nnamespace Velm\\Services;\n\n".
            "/**\n * Generated Service - Do not edit manually.\n */\n".
            "final class {$className} extends \\Velm\\Core\\Runtime\\RuntimeLogicalService {\n".
            "    public static string \$logicalName = '{$logicalName}';\n".
            '}';
    }

    protected function getType(): DomainType
    {
        return DomainType::Services;
    }
}
