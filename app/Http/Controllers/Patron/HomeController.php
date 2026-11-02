<?php

namespace App\Http\Controllers\Patron;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Venue;

class HomeController extends Controller
{
    public function index()
    {
        // Package cards only need venue names for the "Venue" row in the dialog.
        $packages = Package::with('venues')->get();

        // One page-level gallery shows every villa (Villa I, Villa II, ...);
        // the dialog chips switch between them.
        $venuePayloads = Venue::active()
            ->with('photos')
            ->ordered()
            ->get()
            ->map(fn (Venue $venue) => $venue->galleryPayload())
            ->values()
            ->all();

        return view('patron.home', compact('packages', 'venuePayloads'));
    }
}
