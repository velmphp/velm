<?php

namespace Velm\Core\Runtime;

use Velm\Core\Compiler\DomainType;

class PolicyManager extends BaseResourceManager
{
    protected function getType(): DomainType
    {
        return DomainType::Policies;
    }

    protected function getCodeStub(string $className, string $logicalName): string
    {
        return "<?php\nnamespace Velm\\Policies;\n\n".
            "final class {$className} extends \\Velm\\Core\\Runtime\\RuntimeLogicalPolicy {\n".
            "    public static string \$logicalName = '{$className}';\n".
            '}';
    }
}
