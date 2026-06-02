<?php

declare(strict_types=1);

namespace Velm\Framework\Tests;

use Velm\Framework\VelmServiceProvider;
use Velm\Modules\Tests\TestCase as ModulesTestCase;

abstract class TestCase extends ModulesTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            VelmServiceProvider::class,
        ];
    }
}
