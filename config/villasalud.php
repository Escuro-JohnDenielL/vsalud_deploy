<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Villa Salud public contact details
    |--------------------------------------------------------------------------
    |
    | Single source of truth for the shared site footer and the contact
    | sections, so the details are not repeated in views. Edit the values
    | here. Any line left empty is omitted by the footer and the sections.
    |
    */

    'contact' => [
        'email' => 'villasaludcateringservices@gmail.com',
        'phone' => '8837-68-01 / 8776-68-67',
        'mobile' => '0928-492-5493 / 0906-223-6120',
        'fax' => '8856-21-81',
        'address' => '#229 MLQ St., Purok 3, New Lower Bicutan, Taguig City, Metro Manila',
        'hours' => 'Open daily except holidays, 9:00 AM to 6:00 PM',
    ],

    'social' => [
        'facebook' => 'https://www.facebook.com/villasaludcatering',
        'instagram' => 'https://www.instagram.com/villasaludcatering',
    ],

    /*
    | Version recorded on the patron inquiry audit entry as proof of which
    | notice was agreed to. Change this when the notice text changes.
    */
    'privacy_notice_version' => 'dpa-2026-10',

    /*
    |--------------------------------------------------------------------------
    | Admin idle timeout
    |--------------------------------------------------------------------------
    |
    | Minutes of inactivity after which a signed-in admin is logged out. Kept
    | separate from config/session.php so the patron-facing session can stay
    | longer than the admin one. 0 disables the extra check.
    |
    */

    'admin_idle_minutes' => (int) env('ADMIN_IDLE_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Framing allow-list (CSP frame-ancestors)
    |--------------------------------------------------------------------------
    |
    | Public read-only pages may be embedded in an iframe; admin pages and any
    | signed-in session stay same-origin only. List the origins allowed to host
    | the embed. Google Sites is included because the capstone demo page embeds
    | the landing page. Override with a comma-separated EMBED_ALLOWED_ANCESTORS.
    |
    */

    'embed' => [
        'allowed_ancestors' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env(
                'EMBED_ALLOWED_ANCESTORS',
                'https://sites.google.com,https://*.google.com,https://*.googleusercontent.com'
            ))
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bookable venues
    |--------------------------------------------------------------------------
    |
    | Fallback list only. The reservation form field ("venue") is the source of
    | truth for the venue dropdown and the availability panel; these names are
    | used when that field is missing (for example before the form is seeded).
    |
    */

    'venues' => [
        'Villa I',
        'Villa II',
        'Elizabeth Hall',
        'Private Pool',
    ],

];
