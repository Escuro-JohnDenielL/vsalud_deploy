<?php

// Patron Controller Related
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Patron\HomeController;
use App\Http\Controllers\Patron\FeedbackController;
use App\Http\Controllers\Patron\PaymentController;

// Unified
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\CancellationController;

// Admin Controller Related
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\AdminHomeController;
use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\AvailabilityController;
use App\Http\Controllers\Admin\AdminActivityController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Admin\AdminPermissionController;
use App\Http\Controllers\Admin\FormBuilderController;
use App\Http\Controllers\Admin\CancellationController as AdminCancellationController;
use App\Http\Controllers\Admin\WaitlistController as AdminWaitlistController;
use App\Http\Controllers\WaitlistController;
use App\Models\Form;
use App\Models\Inquiry;
use Illuminate\Support\Facades\Log;

// Patron Routes
Route::name('patron.')->group(function () {
    // Home page
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Feedback
    Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback');
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');

    // Reservation form
    Route::get('/mreserve', [ReservationController::class, 'create'])->name('mreserve');
    Route::post('/mreserve', [ReservationController::class, 'store'])->name('mreserve.submit');
    Route::post('/agree', [ReservationController::class, 'acceptAgreement'])->name('agree');

    // Payment (proof of receipt upload)
    Route::get('/payment', [PaymentController::class, 'index'])->name('payment');
    // Route::post('/payment', [PaymentController::class, 'store'])->name('payment.store');

    // Placeholder views for other patron pages
    Route::get('/vreserve', [ReservationController::class, 'fetch_vreserve'])->name('vreserve');
    // Route::view('/vreserve', 'patron.vreserve')->name('vreserve');
    Route::view('/faq', 'patron.faq')->name('faq');
    Route::view('/guidelines', 'patron.guidelines')->name('guidelines');

    // Cancellation request
    Route::get('/cancel-reservation', [CancellationController::class, 'index'])->name('cancel-reservation');
});

// Patron cancellation (public routes, no auth required — uses tracking code lookup)
Route::post('/cancel-reservation/lookup', [CancellationController::class, 'lookup'])->name('cancel-reservation.lookup');
Route::post('/cancel-reservation/submit', [CancellationController::class, 'submit'])->name('cancel-reservation.submit');

// Waitlist (patron-facing)
Route::post('/waitlist/join', [WaitlistController::class, 'join'])->name('waitlist.join');

// Admin Authentication Routes (NO MIDDLEWARE - accessible to non-authenticated users)
Route::prefix('admin')->name('admin.')->group(function () {
    // Login (signup removed — accounts created via IT section only)
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

// Protected Admin Routes (WITH MIDDLEWARE - requires authentication + page permissions)
Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'auth.session', 'page.permission'])->group(function () {
    // Admin Homepage
    Route::get('/home', [AdminHomeController::class, 'index'])->name('home');

    // Packages on Admin homepage
    Route::get('/packages', [PackageController::class, 'index']);
    Route::post('/packages', [PackageController::class, 'store'])->name('admin.packages.store');
    Route::put('/packages/{package}', [PackageController::class, 'update']);
    Route::delete('/packages/{package}', [PackageController::class, 'destroy'])->name('admin.packages.destroy');

    // Inquiry
    Route::get('/inquiry', [InquiryController::class, 'index'])->name('inquiry');
    Route::post('/inquiries/{id}/update-status', [InquiryController::class, 'updateStatusAjax']);
    Route::post('/inquiry', [InquiryController::class, 'store'])->name('inquiry.store');

    // Feedback
    Route::get('/feedback', [AdminFeedbackController::class, 'index'])->name('feedback');
    Route::delete('/feedback/{id}', [AdminFeedbackController::class, 'destroy'])->name('feedback.destroy');

    // Availability for the calendar on admin making inquiry/reservation
    Route::get('/availability', [AvailabilityController::class, 'index'])->name('availability.index');
    Route::post('/availability', [AvailabilityController::class, 'toggle'])->name('availability.toggle');
    Route::post('/availability/remove-override', [AvailabilityController::class, 'removeOverride'])->name('availability.remove-override');

    // Admin creates reservation (inquiry form)
    Route::get('/reserve', [ReservationController::class, 'create'])->name('reserve.create');
    Route::post('/reserve', [ReservationController::class, 'store'])->name('reserve.store');

    // Activity Log for Admin Profile
    Route::get('/activities', [AdminActivityController::class, 'index'])->name('activities');
    Route::post('activities/store', [AdminActivityController::class, 'store'])->name('activities.store');

    // Profile editing and saving
    Route::view('/profile', 'admin.profile')->name('profile');
    Route::post('profile/update', [AdminProfileController::class, 'update'])->name('profile.update');

    // For change password (step 1: send email code)
    Route::post('profile/send-reset-code', [AdminProfileController::class, 'sendPasswordResetCode'])->name('password.send-code')->middleware('auth.session');

    // For change password (step 2: verify code & update)
    Route::post('profile/change-password', [AdminProfileController::class, 'changePassword'])->name('password.change')->middleware('auth.session');

    // All activities of Admin in the reports tab
    Route::get('/dashboard-activities', [AdminActivityController::class, 'allActivities'])->name('activities.all');

    // Inquiry Data on Report Tab
    Route::get('/inquiry-data', [AdminHomeController::class, 'getInquiryData'])->name('inquiry.data');

    // Reservation Data on Report Tab
    Route::get('/reservation-data', [AdminHomeController::class, 'getReservationData'])->name('reservation.data');

    // Theme Data on Report Tab
    Route::get('/theme-data', [AdminHomeController::class, 'getThemeData'])->name('theme.data');

    // Inquiry Category on Report Tab
    Route::get('/event-type-data', [AdminHomeController::class, 'getEventTypeData'])->name('event-type.data');

    // Reserve Logs - FIXED ROUTES
    Route::get('/reserve-logs', [AdminReservationController::class, 'showReservationLogs'])->name('reserve-logs');

    // API routes for reservation management (for AJAX calls)
    Route::get('/reservations/{id}', [AdminReservationController::class, 'getReservation'])->name('reservations.show');
    Route::delete('/reservations/{id}', [AdminReservationController::class, 'deleteReservation'])->name('reservations.delete');

    // Payment status management
    Route::patch('/payments/{payment}/status', [\App\Http\Controllers\Admin\PaymentStatusController::class, 'update'])->name('payments.status');

    // Payment settings (admin-editable)
    Route::get('/payment-settings', [\App\Http\Controllers\Admin\PaymentSettingsController::class, 'index'])->name('payment-settings');
    Route::post('/payment-settings', [\App\Http\Controllers\Admin\PaymentSettingsController::class, 'update'])->name('payment-settings.update');

    // Report page
    Route::view('/report', 'admin.report')->name('report');

    Route::post('/send-reply', [ReservationController::class, 'sendReply']);

    // Cancellation requests (admin)
    Route::get('/cancellations', [AdminCancellationController::class, 'index'])->name('cancellations');
    Route::post('/cancellations/{id}/approve', [AdminCancellationController::class, 'approve'])->name('cancellations.approve');
    Route::post('/cancellations/{id}/deny', [AdminCancellationController::class, 'deny'])->name('cancellations.deny');

    // Waitlist (admin, read-only)
    Route::get('/waitlist', [AdminWaitlistController::class, 'index'])->name('waitlist');
});

// Logout Route (accessible to authenticated users)
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

Route::post('/fetch-reservation', [ReservationController::class, 'fetchReservation'])->name('fetch.reservation');

Route::post('/submit-payment', [PaymentController::class, 'store'])->name('payment.store');

Route::get('/calendar/availability', function () {
    $maxInquiries = config('availability.max_inquiries', 4);

    // 1. Collect all dates that have admin overrides
    $overrides = \App\Models\Availability::where('is_override', true)
        ->get()
        ->keyBy(fn ($item) => $item->date->format('Y-m-d'));

    // 2. Count inquiries per date for auto-calculation (exclude cancelled)
    $inquiries = Inquiry::selectRaw('date, COUNT(*) as total')
        ->where('status', '!=', 'Cancelled')
        ->groupBy('date')
        ->get();

    // 3. Gather all unique dates from both sources (all as strings)
    $allDates = collect();
    foreach ($overrides as $date => $_) {
        $allDates->push($date);
    }
    foreach ($inquiries as $inquiry) {
        $allDates->push($inquiry->date instanceof \Carbon\Carbon ? $inquiry->date->format('Y-m-d') : $inquiry->date);
    }
    $allDates = $allDates->unique()->sort();

    $availability = [];

    // Build a lookup for inquiry counts keyed by string date
    $inquiryCounts = [];
    foreach ($inquiries as $inquiry) {
        $dateStr = $inquiry->date instanceof \Carbon\Carbon ? $inquiry->date->format('Y-m-d') : $inquiry->date;
        $inquiryCounts[$dateStr] = $inquiry->total;
    }

    foreach ($allDates as $date) {
        // Check override first — admin's manual status takes priority
        if (isset($overrides[$date])) {
            $availability[$date] = $overrides[$date]->status;
            continue;
        }

        // Otherwise auto-calculate from inquiry count
        $count = $inquiryCounts[$date] ?? 0;

        if ($count >= $maxInquiries) {
            $availability[$date] = 'Full';
        } elseif ($count === 3) {
            $availability[$date] = 'Nearly';
        } elseif ($count === 2) {
            $availability[$date] = 'Half';
        } elseif ($count >= 1) {
            $availability[$date] = 'Available';
        }
        // If count is 0 and no inquiry exists, skip (no data to send)
    }

    return response()->json($availability);
});

// Landing Page
Route::view('/', 'index');

// IT Management (super_admin only — inside admin guard)
Route::prefix('admin/it')->name('admin.it.')->middleware(['auth:admin', 'role.admin:super_admin'])->group(function () {
    Route::get('/dashboard', [AdminManagementController::class, 'index'])->name('dashboard');
    Route::post('/store', [AdminManagementController::class, 'store'])->name('store');
    Route::get('/edit/{id}', [AdminManagementController::class, 'edit'])->name('edit');
    Route::post('/update/{id}', [AdminManagementController::class, 'update'])->name('update');
    Route::post('/deactivate/{id}', [AdminManagementController::class, 'deactivate'])->name('deactivate');
    Route::post('/restore/{id}', [AdminManagementController::class, 'restore'])->name('restore');
    Route::delete('/force-delete/{id}', [AdminManagementController::class, 'forceDelete'])->name('force-delete');

    // Permissions Management
    Route::get('/permissions/{id}', [AdminPermissionController::class, 'edit'])->name('permissions');
});

// Form Builder (super_admin only — separate from IT Management)
Route::prefix('admin/forms')->name('admin.forms.')->middleware(['auth:admin', 'role.admin:super_admin'])->group(function () {
    Route::get('/', [FormBuilderController::class, 'index'])->name('index');
    Route::get('/{form}/edit', [FormBuilderController::class, 'edit'])->name('edit');
    Route::post('/{form}/fields', [FormBuilderController::class, 'addField'])->name('fields.store');
    Route::put('/{form}/fields/{field}', [FormBuilderController::class, 'updateField'])->name('fields.update');
    Route::delete('/{form}/fields/{field}', [FormBuilderController::class, 'deleteField'])->name('fields.destroy');
    Route::post('/{form}/fields/reorder', [FormBuilderController::class, 'reorderFields'])->name('fields.reorder');
    Route::get('/{form}/preview', [FormBuilderController::class, 'preview'])->name('preview');
    Route::post('/{form}/publish', [FormBuilderController::class, 'publish'])->name('publish');
});

// Permissions API routes (super_admin only — outside admin it group but still gated by role)
Route::prefix('admin/permissions')->name('admin.permissions.')->middleware(['auth:admin', 'role.admin:super_admin'])->group(function () {
    Route::post('/{id}/update', [AdminPermissionController::class, 'update'])->name('update');
    Route::post('/{id}/preset', [AdminPermissionController::class, 'applyPreset'])->name('preset');
});

// -------------------------------------------------------------------
// IT Management Routes (User model + web guard — for IT administrators)
// -------------------------------------------------------------------
Route::prefix('it')->name('it.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\It\AccessController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\It\AccessController::class, 'login'])->name('login.submit');
});

Route::prefix('it')->name('it.')->middleware(['auth:web', 'role:it'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\It\AccessController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [\App\Http\Controllers\It\AccessController::class, 'logout'])->name('logout');
    Route::post('/admins', [\App\Http\Controllers\It\AccessController::class, 'storeAdmin'])->name('admins.store');
});

// -------------------------------------------------------------------
// Generic fallback route named 'login' — used by Authenticate middleware
// when no specific guard is matched
// -------------------------------------------------------------------
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');
