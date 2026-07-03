<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Availability;

class AvailabilityController extends Controller
{

    public function index()
    {
        $records = \App\Models\Availability::all()->mapWithKeys(function ($item) {
            if ($item->date === null) {
                return [];
            }
            return [$item->date->format('Y-n-j') => $item->status];
        });

        return response()->json($records);
    }


    public function toggle(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'status' => 'required|in:Available,Half,Nearly,Full,Closed', 
        ]);

        $availability = \App\Models\Availability::updateOrCreate(
            ['date' => $request->date],
            ['status' => $request->status]
        );

        return response()->json([
            'success' => true,
            'date' => $availability->date,
            'status' => $availability->status,
        ]);
    }
}
