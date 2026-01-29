<?php

namespace Velm\Core\Metadata\Types;

enum RelationType
{
    case BelongsTo;
    case HasOne;
    case HasMany;
    case BelongsToMany;
    case MorphTo;
    case MorphOne;
    case MorphMany;
    case MorphToMany;
}
