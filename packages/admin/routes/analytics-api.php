<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Velm\Admin\Http\Controllers\Analytics\GraphDataController;
use Velm\Admin\Http\Controllers\Analytics\PivotDataController;
use Velm\Admin\Http\Controllers\Analytics\ViewFieldsController;
use Velm\Framework\Http\Middleware\BindVelmEnvironment;

Route::middleware(['api', BindVelmEnvironment::class])
    ->group(function (): void {
        Route::get('graph/data', GraphDataController::class)->name('velm.api.graph.data');
        Route::get('pivot/data', PivotDataController::class)->name('velm.api.pivot.data');
        Route::get('view-fields', ViewFieldsController::class)->name('velm.api.view-fields');
    });
