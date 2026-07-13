<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminPagePermission extends Model
{
    protected $table = 'admin_page_permissions';

    protected $fillable = [
        'admin_id',
        'page_slug',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }

    /**
     * All available page slugs for the permissions system.
     */
    public static function availablePages(): array
    {
        return [
            'packages'      => 'Packages',
            'reservations'  => 'Reservations',
            'inquiries'     => 'Inquiries',
            'reserve-logs'  => 'Reservation Logs',
            'payment-logs'  => 'Payment Logs',
            'cancellations' => 'Cancellation Requests',
            'waitlist'      => 'Waitlist',
            'reports'       => 'Reports',
            'feedback'      => 'Feedback',
        ];
    }

    /**
     * Define presets with their page slugs.
     */
    public static function presets(): array
    {
        return [
            'full-access' => [
                'label' => 'Full Access',
                'description' => 'All pages (packages, reservations, inquiries, logs, cancellations, waitlist, payment logs, reports, feedback)',
                'pages' => ['packages', 'reservations', 'inquiries', 'reserve-logs', 'payment-logs', 'cancellations', 'waitlist', 'reports', 'feedback'],
            ],
            'view-only' => [
                'label' => 'View Only',
                'description' => 'Read monitoring — inquiries, logs, payment logs, feedback, reports',
                'pages' => ['inquiries', 'reserve-logs', 'payment-logs', 'feedback', 'reports'],
            ],
            'reports-only' => [
                'label' => 'Reports Only',
                'description' => 'Dashboard reports + packages',
                'pages' => ['reports', 'packages'],
            ],
            'reservation-handler' => [
                'label' => 'Reservation Handler',
                'description' => 'Reservations, logs, payment logs, and inquiries',
                'pages' => ['reservations', 'reserve-logs', 'payment-logs', 'inquiries'],
            ],
            'customer-service' => [
                'label' => 'Customer Service',
                'description' => 'Inquiries, feedback, and logs management',
                'pages' => ['inquiries', 'feedback', 'reserve-logs', 'payment-logs'],
            ],
            'no-access' => [
                'label' => 'No Access',
                'description' => 'Profile-only access (new account default)',
                'pages' => [],
            ],
        ];
    }
}
