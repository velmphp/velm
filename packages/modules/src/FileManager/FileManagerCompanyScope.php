<?php

declare(strict_types=1);

namespace Velm\Modules\FileManager;

use Velm\Environment;

final class FileManagerCompanyScope
{
    /**
     * Company to stamp on new library rows when the request has no active company
     * (e.g. superuser all-companies mode).
     */
    public static function stampCompanyId(Environment $env): ?int
    {
        $active = $env->companyId();

        if ($active !== null) {
            return $active;
        }

        $default = $env->userDefaultCompanyId();

        if ($default !== null && $default > 0) {
            return $default;
        }

        $allowed = $env->allowedCompanyIds();

        return $allowed[0] ?? self::defaultCompanyId($env);
    }

    public static function backfillOrphans(Environment $env): void
    {
        $companyId = self::defaultCompanyId($env);

        if ($companyId === null) {
            return;
        }

        $env->withAclBypass(function () use ($env, $companyId): void {
            if ($env->registry->has('res.attachment.folder') && $env->modelHasCompanyField('res.attachment.folder')) {
                $folders = $env->model('res.attachment.folder')->search([['company_id', '=', false]]);

                if ($folders->count() > 0) {
                    $folders->write(['company_id' => $companyId]);
                }
            }

            if ($env->registry->has('ir.attachment') && $env->modelHasCompanyField('ir.attachment')) {
                $attachments = $env->model('ir.attachment')->search([['company_id', '=', false]]);

                if ($attachments->count() > 0) {
                    $attachments->write(['company_id' => $companyId]);
                }
            }
        });
    }

    public static function envForCreate(Environment $env): Environment
    {
        $companyId = self::stampCompanyId($env);

        if ($companyId === null) {
            return $env;
        }

        return $env->withContext(['company_id' => $companyId]);
    }

    public static function defaultCompanyId(Environment $env): ?int
    {
        if (! $env->registry->has('res.company')) {
            return null;
        }

        return $env->withAclBypass(function () use ($env): ?int {
            $rows = $env->model('res.company')->search([], limit: 1, order: '"id" ASC')->read(['id']);
            $id = (int) ($rows[0]['id'] ?? 0);

            return $id > 0 ? $id : null;
        });
    }
}
