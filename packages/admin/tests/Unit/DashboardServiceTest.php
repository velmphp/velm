<?php

declare(strict_types=1);

use Velm\Admin\Dashboard\DashboardService;
use Velm\Admin\Tests\TestCase;
use Velm\Environment;
use Velm\Modules\Dashboard\DashboardWidgetSpec;
uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('dashboard service hides widgets for unknown models', function (): void {
    $env = app(Environment::class);
    $service = new DashboardService;
    $isVisible = new ReflectionMethod(DashboardService::class, 'isVisible');
    $isVisible->setAccessible(true);

    $spec = new DashboardWidgetSpec(
        id: 'ghost',
        module: 'test',
        title: 'Ghost',
        model: 'model.that.does.not.exist',
        view: 'velm-ui::dashboard.stat-card',
        resolver: 'Velm\\Modules\\Partners\\Dashboard\\PartnersSummaryWidget::resolve',
    );

    expect($isVisible->invoke($service, $env, $spec))->toBeFalse();
});

test('dashboard service rejects malformed widget resolvers', function (): void {
    $env = app(Environment::class);
    $service = new DashboardService;
    $resolveData = new ReflectionMethod(DashboardService::class, 'resolveData');
    $resolveData->setAccessible(true);

    $missingSyntax = new DashboardWidgetSpec(
        id: 'bad',
        module: 'test',
        title: 'Bad',
        model: 'res.partner',
        view: 'velm-ui::dashboard.stat-card',
        resolver: 'NotCallableSyntax',
    );

    expect(fn () => $resolveData->invoke($service, $env, $missingSyntax))
        ->toThrow(RuntimeException::class, 'must use Class::method syntax');

    $missingClass = new DashboardWidgetSpec(
        id: 'bad2',
        module: 'test',
        title: 'Bad',
        model: 'res.partner',
        view: 'velm-ui::dashboard.stat-card',
        resolver: 'Velm\\Missing\\Widget::resolve',
    );

    expect(fn () => $resolveData->invoke($service, $env, $missingClass))
        ->toThrow(RuntimeException::class, 'is not callable');
});

test('dashboard service returns empty data when resolver does not return array', function (): void {
    $env = app(Environment::class);
    $service = new DashboardService;
    $resolveData = new ReflectionMethod(DashboardService::class, 'resolveData');
    $resolveData->setAccessible(true);

    $spec = new DashboardWidgetSpec(
        id: 'scalar',
        module: 'test',
        title: 'Scalar',
        model: 'res.partner',
        view: 'velm-ui::dashboard.stat-card',
        resolver: DashboardScalarResolver::class.'::resolve',
    );

    expect($resolveData->invoke($service, $env, $spec))->toBe([]);
});

final class DashboardScalarResolver
{
    public static function resolve(Environment $env): string
    {
        return 'not-an-array';
    }
}
