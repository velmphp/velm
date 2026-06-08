<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

enum ActionVariant: string
{
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Warning = 'warning';
    case Success = 'success';
    case Danger = 'danger';
}
