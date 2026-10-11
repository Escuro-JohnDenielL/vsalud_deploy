<?php

use App\Http\Middleware\CheckPagePermission;
use App\Models\AdminPagePermission;
use Illuminate\Support\Facades\Route;

/*
 * Guards the permission map against drift. A protected admin route that is
 * neither mapped to a page slug nor allowlisted falls through the middleware's
 * "no slug" branch and becomes reachable by every admin, so these checks must
 * stay green.
 *
 * These cases boot the framework but touch no database.
 */

it('maps or allowlists every permission-protected admin route', function () {
    $mapped = CheckPagePermission::ROUTE_PAGE_MAP;
    $allowlisted = CheckPagePermission::SELF_SERVICE_ROUTES;

    $uncovered = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => in_array('page.permission', $route->middleware(), true))
        ->reject(function ($route) use ($mapped, $allowlisted) {
            $name = $route->getName();

            return $name !== null
                && (isset($mapped[$name]) || in_array($name, $allowlisted, true));
        })
        ->map(fn ($route) => ($route->getName() ?? '(unnamed)')
            . ' [' . implode('|', $route->methods()) . ' ' . $route->uri() . ']')
        ->values()
        ->all();

    expect($uncovered)->toBe([]);
});

it('only maps to slugs that exist on the Permissions page', function () {
    $slugs = array_keys(AdminPagePermission::availablePages());

    $unknown = array_values(array_unique(
        array_diff(array_values(CheckPagePermission::ROUTE_PAGE_MAP), $slugs)
    ));

    expect($unknown)->toBe([]);
});

it('only presets slugs that exist on the Permissions page', function () {
    $slugs = array_keys(AdminPagePermission::availablePages());

    $unknown = [];

    foreach (AdminPagePermission::presets() as $key => $preset) {
        foreach ($preset['pages'] as $slug) {
            if (! in_array($slug, $slugs, true)) {
                $unknown[] = $key . ':' . $slug;
            }
        }
    }

    expect($unknown)->toBe([]);
});
