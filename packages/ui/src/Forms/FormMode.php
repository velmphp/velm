<?php

declare(strict_types=1);

namespace Velm\Ui\Forms;

enum FormMode: string
{
    case New = 'new';
    case Edit = 'edit';
    case Display = 'display';
}
