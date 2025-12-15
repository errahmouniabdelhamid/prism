<?php

use Illuminate\Support\Facades\Route;
use Prism\Prism\Http\Controllers\ObservabilityController;

Route::prefix(config('prism.observability.path', 'prism/observability'))
    ->group(function (): void {
        Route::get('/', ObservabilityController::class)->name('prism.observability');
    });
