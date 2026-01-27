<?php

namespace Velm\Core\Metadata\Types;

enum FieldType: string
{
    case String = 'string';
    case Text = 'text';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Boolean = 'boolean';
    case Date = 'date';
    case DateTime = 'datetime';
    case Time = 'time';
    case Float = 'float';
    case Enum = 'enum';
    case Json = 'json';
    case Uuid = 'uuid';
    case Money = 'money';
}
