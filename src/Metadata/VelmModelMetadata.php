<?php

namespace Velm\Core\Metadata;

use Velm\Core\Metadata\Access\VelmAccess;
use Velm\Core\Metadata\Behavior\DefinedAction;
use Velm\Core\Metadata\Fields\VelmField;
use Velm\Core\Metadata\Lifecycle\VelmLifecycle;
use Velm\Core\Metadata\Relation\VelmRelation;

final class VelmModelMetadata
{
    /**
     * @param  VelmField[]  $fields
     * @param  VelmRelation[]  $relations
     * @param  DefinedAction[]  $definedActions
     */
    public function __construct(
        public readonly array $fields,
        public readonly array $relations,
        public readonly array $definedActions,
        public readonly VelmAccess $access,
        public readonly ?VelmLifecycle $lifecycle,
        public readonly array $presentation,
    ) {}

    public function getField(string $name): ?VelmField
    {
        return $this->fields[$name] ?? null;
    }

    public function toArray(): array
    {
        return [
            'fields' => array_map(fn (VelmField $field) => $field->toArray(), $this->fields),
            'relations' => $this->relations,
            'definedActions' => $this->definedActions,
            'access' => $this->access,
            'lifecycle' => $this->lifecycle,
            'presentation' => $this->presentation,
        ];
    }
}
