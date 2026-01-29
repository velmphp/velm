<?php

namespace Velm\Core\Contracts;

use Velm\Core\Domain\DomainDescriptor;
use Velm\Core\Modules\ModuleDescriptor;

interface VelmClassContract
{
    public static function velm(): DomainDescriptor;

    public static function module(): ?ModuleDescriptor;

    public static function proxyCandidateClass(): ?string;

    public static function initialDefinition(): string;

    public static function getName(): string;
}
