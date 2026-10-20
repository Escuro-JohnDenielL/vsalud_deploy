<?php

use App\Http\Controllers\Admin\IncidentLogController;
use Illuminate\Support\Facades\Route;

/*
| Resolve and Delete are wired by incident-logs.js to two routes. The original
| bug was client-side (the overflow menu is moved to document.body), but these
| assertions catch a future route rename or verb change. No database is needed.
*/

it('keeps the incident update and delete routes wired', function () {
    expect(Route::has('admin.incident-logs.update'))->toBeTrue();
    expect(Route::has('admin.incident-logs.destroy'))->toBeTrue();

    $update = Route::getRoutes()->getByName('admin.incident-logs.update');
    $destroy = Route::getRoutes()->getByName('admin.incident-logs.destroy');

    expect($update->methods())->toContain('POST');
    expect($destroy->methods())->toContain('DELETE');
    expect($update->getActionName())->toContain(IncidentLogController::class . '@update');
    expect($destroy->getActionName())->toContain(IncidentLogController::class . '@destroy');
});
