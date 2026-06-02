<?php

declare(strict_types=1);

namespace Velm\Cron;

use Velm\Environment;

final class CronJob
{
    /**
     * @return list<string> Names of executed jobs.
     */
    public static function runDue(Environment $env): array
    {
        if (! $env->registry->has('ir.cron')) {
            return [];
        }

        return $env->withAclBypass(function () use ($env): array {
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            $executed = [];

            $jobs = $env->model('ir.cron')->search([['active', '=', true]]);

            foreach ($jobs->read() as $job) {
                $nextcall = (string) ($job['nextcall'] ?? '');

                if ($nextcall !== '' && $nextcall > $now->format('Y-m-d H:i:s')) {
                    continue;
                }

                $actionId = (int) ($job['action_id'] ?? 0);

                if ($actionId <= 0) {
                    continue;
                }

                if (! $env->registry->has('ir.actions.server')) {
                    continue;
                }

                $actionRows = $env->model('ir.actions.server')->search([['id', '=', $actionId]])->read();

                if ($actionRows === []) {
                    continue;
                }

                $action = $actionRows[0];
                $targetModel = (string) ($action['model'] ?? '');

                if ($targetModel === '' || ! $env->registry->has($targetModel)) {
                    self::advanceSchedule($env, (int) $job['id'], $job, $now);

                    continue;
                }

                try {
                    self::executeAction($env, $action, $targetModel);
                    $executed[] = (string) ($job['name'] ?? 'cron');
                } catch (\Throwable) {
                    // Advance schedule even on failure to avoid tight loops.
                }

                self::advanceSchedule($env, (int) $job['id'], $job, $now);
            }

            return $executed;
        });
    }

    /**
     * @param  array<string, mixed>  $action
     */
    private static function executeAction(Environment $env, array $action, string $targetModel): void
    {
        $target = $env->model($targetModel)->search();
        $kind = (string) ($action['action_type'] ?? '');
        $vals = json_decode((string) ($action['vals_json'] ?? '{}'), true);

        if (! is_array($vals)) {
            $vals = [];
        }

        match ($kind) {
            'write' => $target->count() > 0 ? $target->write($vals) : null,
            'create' => $env->model($targetModel)->create($vals),
            'unlink' => $target->count() > 0 ? $target->unlink() : null,
            default => throw new \InvalidArgumentException("Unknown action_type {$kind}."),
        };
    }

    /**
     * @param  array<string, mixed>  $job
     */
    private static function advanceSchedule(
        Environment $env,
        int $jobId,
        array $job,
        \DateTimeImmutable $now,
    ): void {
        $intervalType = (string) ($job['interval_type'] ?? 'hours');
        $intervalNumber = (int) ($job['interval_number'] ?? 1);
        $next = $now->add(self::intervalDelta($intervalType, $intervalNumber));

        $env->model('ir.cron')->search([['id', '=', $jobId]])->write([
            'lastcall' => $now->format('Y-m-d H:i:s'),
            'nextcall' => $next->format('Y-m-d H:i:s'),
        ]);
    }

    private static function intervalDelta(string $type, int $number): \DateInterval
    {
        $n = max($number, 1);

        return match ($type) {
            'minutes' => new \DateInterval('PT'.$n.'M'),
            'days' => new \DateInterval('P'.$n.'D'),
            'weeks' => new \DateInterval('P'.$n.'W'),
            default => new \DateInterval('PT'.$n.'H'),
        };
    }
}
