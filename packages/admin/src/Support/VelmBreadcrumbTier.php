<?php

declare(strict_types=1);

namespace Velm\Admin\Support;

/**
 * Fixed breadcrumb depth (never session/history based).
 *
 * Standard: Home → List → Detail → Edit
 * Special:  Home → Current page only
 */
enum VelmBreadcrumbTier
{
    case List;
    case Detail;
    case Create;
    case Edit;
    case Special;
}
