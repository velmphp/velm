<?php

namespace Velm\Core\Domain\Policies;

use Velm\Core\Compiler\Concerns\HasVelmPipelines;

class VelmPolicyProxy extends VelmPolicy
{
    use HasVelmPipelines;
}
