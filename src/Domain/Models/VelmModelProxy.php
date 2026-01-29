<?php

namespace Velm\Core\Domain\Models;

use Velm\Core\Compiler\Concerns\HasVelmPipelines;
use Velm\Core\Metadata\Contracts\ProvidesVelmMetadata;
use Velm\Core\Metadata\Fields\VelmField;
use Velm\Core\Metadata\VelmModelMetadata;

abstract class VelmModelProxy extends VelmModel implements ProvidesVelmMetadata
{
    use HasVelmPipelines;

    protected static ?VelmModelMetadata $_velmMetadata = null;

    private static ?array $sortedMetadataFields = null;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        static::bootVelmIfNotBooted();
    }

    protected static string $logicalName = '';

    protected static function booted(): void
    {
        parent::booted();
        static::bootVelmIfNotBooted();
    }

    final public static function metadata(): VelmModelMetadata
    {
        return static::$_velmMetadata ??= static::compileMetadata();
    }

    /**
     * Compile immutable, deterministic, UI-agnostic metadata.
     *
     * ⚠️ MUST NOT:
     * - Read instance state
     * - Perform I/O
     * - Mutate the model
     * - Depend on runtime context
     */
    private static function compileMetadata(): VelmModelMetadata
    {
        $model = new static;

        return new VelmModelMetadata(
            fields: static::$sortedMetadataFields ??= static::sortMetadataFields(),
            relations: $model->relations(),
            definedActions: $model->definedActions(),
            access: $model->access(),
            lifecycle: $model->lifecycle(),
            presentation: $model->presentation(),
        );
    }

    private static function sortMetadataFields(): array
    {
        $model = new static;
        $fields = $model->fields();
        usort($fields, function (VelmField $a, VelmField $b) {
            return $a->getPosition() <=> $b->getPosition();
        });

        // Convert to associative array keyed by field name
        return array_combine(
            array_map(fn (VelmField $field) => $field->getName(), $fields),
            $fields,
        );
    }
}
