<?php

// Patron Controller Related
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Patron\HomeController;
use App\Http\Controllers\Patron\FeedbackController;
use App\Http\Controllers\Patron\PaymentController;

// Unified
use App\Http\Controllers\ReservationController;

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
use App\Http\Controllers\It\AccessController as ItAccessController;
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
    Route::post('/guest-consent/agree', [ReservationController::class, 'acceptGuestConsent'])->name('guest.consent.agree');
    Route::get('/guest/renew', [ReservationController::class, 'renewGuest'])->name('guest.renew');

    // Payment (proof of receipt upload)
    Route::get('/payment', [PaymentController::class, 'index'])->name('payment');
    // Route::post('/payment', [PaymentController::class, 'store'])->name('payment.store');

    // Placeholder views for other patron pages
    Route::get('/vreserve', [ReservationController::class, 'fetch_vreserve'])->name('vreserve');
    // Route::view('/vreserve', 'patron.vreserve')->name('vreserve');
    Route::view('/faq', 'patron.faq')->name('faq');
    Route::view('/guidelines', 'patron.guidelines')->name('guidelines');
});

// Admin Authentication Routes (NO MIDDLEWARE - accessible to non-authenticated users)
Route::prefix('admin')->name('admin.')->group(function () {
    // Signup
    Route::get('/signup', [AuthController::class, 'showSignupForm'])->name('signup');
    Route::post('/signup', [AuthController::class, 'signup'])->name('signup.submit');

    // Login
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

// Protected Admin Routes (WITH MIDDLEWARE - requires authentication)
Route::prefix('admin')->name('admin.')->middleware(['auth:admin'])->group(function () {
    // Admin Homepage
    Route::get('/home', [AdminHomeController::class, 'index'])->name('home');

    // Packages on Admin homepage
    Route::get('/packages', [PackageController::class, 'index']);
    Route::post('/packages', [PackageController::class, 'store']);
    Route::put('/packages/{package}', [PackageController::class, 'update']);
    Route::delete('/admin/packages/{package}', [PackageController::class, 'destroy'])->name('admin.packages.destroy');
    Route::post('/admin/packages', [PackageController::class, 'store'])->name('admin.packages.store');

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

    // Admin creates reservation (inquiry form)
    Route::get('/reserve', [ReservationController::class, 'create'])->name('reserve.create');
    Route::post('/reserve', [ReservationController::class, 'store'])->name('reserve.store');

    // Activity Log for Admin Profile
    Route::get('/activities', [AdminActivityController::class, 'index'])->name('activities');
    Route::post('activities/store', [AdminActivityController::class, 'store'])->name('activities.store');

    // Profile editing and saving
    Route::view('/profile', 'admin.profile')->name('profile');
    Route::post('profile/update', [AdminProfileController::class, 'update'])->name('profile.update');

    // For change password
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

    // Report page
    Route::view('/report', 'admin.report')->name('report');

    Route::post('/send-reply', [ReservationController::class, 'sendReply']);
});

// Logout Route (accessible to authenticated users)
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

Route::post('/fetch-reservation', [ReservationController::class, 'fetchReservation'])->name('fetch.reservation');

Route::post('/submit-payment', [PaymentController::class, 'store'])->name('payment.store');

Route::get('/calendar/availability', function () {
    $inquiries = Inquiry::selectRaw('date, COUNT(*) as total')
        ->groupBy('date')
        ->get();

    $availability = [];

    Log::info($inquiries);

    foreach ($inquiries as $inquiry) {
        $count = $inquiry->total;
        $date = $inquiry->date;

        if ($count >= 4) {
            $availability[$date] = 'Full';
        } elseif ($count === 3) {
            $availability[$date] = 'Nearly';
        } elseif ($count === 2) {
            $availability[$date] = 'Half';
        } elseif ($count === 1) {
            $availability[$date] = 'Available';
        } else {
            $availability[$date] = 'Available'; // Just in case
        }
    }

    return response()->json($availability);
});

// Landing Page
Route::view('/', 'index');

// IT Access
Route::prefix('it')->name('it.')->group(function () {
    Route::get('/login', [ItAccessController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [ItAccessController::class, 'login'])->name('login.submit');

    Route::middleware(['auth', 'role:it'])->group(function () {
        Route::get('/dashboard', [ItAccessController::class, 'dashboard'])->name('dashboard');
        Route::post('/logout', [ItAccessController::class, 'logout'])->name('logout');
        Route::post('/admins', [ItAccessController::class, 'storeAdmin'])->name('admins.store');
        Route::post('/guests', [ItAccessController::class, 'storeGuest'])->name('guests.store');
    });
});
