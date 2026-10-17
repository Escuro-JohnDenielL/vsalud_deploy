<?php

use App\Http\Middleware\CheckPagePermission;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

/*
| The Reserve Logs CSV export must sit behind the same page permission as the
| page itself. Unmapped routes are allowed for every admin, so the mapping is
| what makes the denial work.
*/

uses(RefreshDatabase::class);

function reserveLogsAdmin(): Admin
{
    return Admin::create([
        'email' => 'reserve-logs-admin@example.test',
        'f_name' => 'Logs',
        'l_name' => 'Admin',
        'phone' => '09170000000',
        'password' => bcrypt('secret'),
        'role' => 'admin',
        'mfa_method' => 'email',
        'mfa_setup_completed_at' => now(),
    ]);
}

it('keeps the export inside the protected admin group and maps it', function () {
    $route = Route::getRoutes()->getByName('admin.reserve-logs.export');

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('auth:admin')
        ->and($route->gatherMiddleware())->toContain('page.permission')
        ->and(CheckPagePermission::ROUTE_PAGE_MAP['admin.reserve-logs.export'] ?? null)
        ->toBe('reserve-logs');
});

it('refuses the export to an admin without the reserve-logs page', function () {
    $admin = reserveLogsAdmin();

    $this->actingAs($admin, 'admin')
        ->withSession(['mfa_verified_at' => now()])
        ->get('/admin/reserve-logs/export')
        ->assertRedirect(route('admin.profile'));
});
