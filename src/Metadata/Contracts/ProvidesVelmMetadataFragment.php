<?php

namespace Velm\Core\Metadata\Contracts;

use Velm\Core\Metadata\Access\VelmAccess;
use Velm\Core\Metadata\Behavior\DefinedAction;
use Velm\Core\Metadata\Fields\VelmField;
use Velm\Core\Metadata\Lifecycle\VelmLifecycle;
use Velm\Core\Metadata\Relation\VelmRelation;

interface ProvidesVelmMetadataFragment
{
    /**
     * @return array<string, VelmField>
     */
    public function fields(): array;

    /**
     * @return array<string, VelmRelation>
     */
    public function relations(): array;

    /**
     * @return array<string, DefinedAction>
     */
    public function definedActions(): array;

    public function access(): ?VelmAccess;

    public function lifecycle(): ?VelmLifecycle;

    public function presentation(): array;
}
