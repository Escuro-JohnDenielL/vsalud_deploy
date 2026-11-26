<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Venue;

class LandingController extends Controller
{
    public function __invoke()
    {
        $venues = Venue::active()
            ->with('photos')
            ->ordered()
            ->limit(5)
            ->get();

        $venueRatings = Review::approved()
            ->join('inquiry', 'reviews.inquiry_id', '=', 'inquiry.inquiry_id')
            ->whereNotNull('inquiry.venue')
            ->select('inquiry.venue')
            ->selectRaw('AVG(reviews.rating) as average_rating, COUNT(*) as review_count')
            ->groupBy('inquiry.venue')
            ->get()
            ->keyBy('venue');

        $reviews = Review::approved()
            ->latest()
            ->limit(6)
            ->get();

        $reviewCount = Review::approved()->count();
        $averageRating = $reviewCount > 0 ? Review::approved()->avg('rating') : null;

        return view('index', compact('venues', 'venueRatings', 'reviews', 'reviewCount', 'averageRating'));
    }
}
