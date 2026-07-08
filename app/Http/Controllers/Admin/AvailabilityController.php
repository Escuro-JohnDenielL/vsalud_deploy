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
            return [$item->date->format('Y-n-j') => [
                'status' => $item->status,
                'is_override' => $item->is_override,
            ]];
        });

        return response()->json($records);
    }


    public function toggle(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'status' => 'required|in:Available,Half,Nearly,Full,Closed',
            'override' => 'sometimes|boolean',
        ]);

        $override = $request->boolean('override');

        $availability = \App\Models\Availability::updateOrCreate(
            ['date' => $request->date],
            [
                'status' => $request->status,
                'is_override' => $override,
            ]
        );

        return response()->json([
            'success' => true,
            'date' => $availability->date,
            'status' => $availability->status,
            'is_override' => $availability->is_override,
        ]);
    }


    public function removeOverride(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $availability = \App\Models\Availability::where('date', $request->date)->first();

        if ($availability) {
            $availability->update(['is_override' => false]);
        }

        return response()->json([
            'success' => true,
            'date' => $request->date,
            'is_override' => false,
        ]);
    }
}
