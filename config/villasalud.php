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
    ],

    'social' => [
        'facebook' => 'https://www.facebook.com/villasaludcatering',
        'instagram' => 'https://www.instagram.com/villasaludcatering',
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
