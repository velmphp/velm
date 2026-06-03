<?php

declare(strict_types=1);

namespace Velm\Ui\Forms;

enum FormLayoutKind: string
{
    case Section = 'section';
    case Notebook = 'notebook';
}
