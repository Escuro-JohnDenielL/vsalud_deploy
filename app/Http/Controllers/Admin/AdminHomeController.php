<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inquiry;
use Carbon\Carbon;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

class AdminHomeController extends Controller
{
    public function getInquiryData(Request $request)
    {
        $filter = $request->query('filter', 'month'); // Default: month

        $now = Carbon::now();
        $labels = [];
        $data = [];
        $total = 0;
        $previousTotal = 0;

        switch ($filter) {
            case 'week':
                // Current week (7 days)
                for ($i = 6; $i >= 0; $i--) {
                    $day = $now->copy()->subDays($i);
                    $count = Inquiry::whereDate('created_at', $day->toDateString())->count();
                    $data[] = $count;
                    $labels[] = $day->format('D');
                    $total += $count;
                }

                // Previous week
                for ($i = 13; $i >= 7; $i--) {
                    $day = $now->copy()->subDays($i);
                    $previousTotal += Inquiry::whereDate('created_at', $day->toDateString())->count();
                }
                break;

            case 'month':
            default:
                // Current month, grouped by week
                $start = $now->copy()->startOfMonth();
                for ($i = 0; $i < 4; $i++) {
                    $startOfWeek = $start->copy()->addWeeks($i);
                    $endOfWeek = $startOfWeek->copy()->addDays(6);
                    $count = Inquiry::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
                    $data[] = $count;
                    $labels[] = 'Week ' . ($i + 1);
                    $total += $count;
                }

                // Previous month
                $previousStart = $now->copy()->subMonth()->startOfMonth();
                $previousEnd = $previousStart->copy()->endOfMonth();
                $previousTotal = Inquiry::whereBetween('created_at', [$previousStart, $previousEnd])->count();
                break;
        }

        $change = $previousTotal > 0
            ? round((($total - $previousTotal) / $previousTotal) * 100)
            : 0;


        // Calculate average inquiries per day (based on data array)
        $averagePerDay = count($data) > 0
            ? round(array_sum($data) / count($data), 1)
            : 0;

        // Get peak day
        $peakIndex = array_keys($data, max($data))[0] ?? null;
        $peakDay = $labels[$peakIndex] ?? 'N/A';

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'total' => $total,
            'change_percentage' => $change,
            'average_per_day' => $averagePerDay,
            'peak_day' => $peakDay,
        ]);
    }

    public function getReservationData(Request $request)
    {
        $filter = $request->query('filter', 'month');
        $now = Carbon::now();
        $labels = [];
        $data = [];

        $total = Reservation::count(); // all-time for metric card
        $previousTotal = 0;

        switch ($filter) {
            case 'year':
                for ($i = 0; $i < 12; $i++) {
                    $month = $now->copy()->startOfYear()->addMonths($i);
                    $count = Reservation::whereMonth('created_at', $month->month)
                        ->whereYear('created_at', $month->year)
                        ->count();
                    $labels[] = $month->format('M');
                    $data[] = $count;
                }

                // Previous year
                $previousTotal = Reservation::whereYear('created_at', $now->year - 1)->count();
                break;

            case 'week':
                for ($i = 6; $i >= 0; $i--) {
                    $day = $now->copy()->subDays($i);
                    $labels[] = $day->format('D');
                    $data[] = Reservation::whereDate('created_at', $day->toDateString())->count();
                }

                // Previous week
                for ($i = 13; $i >= 7; $i--) {
                    $day = $now->copy()->subDays($i);
                    $previousTotal += Reservation::whereDate('created_at', $day->toDateString())->count();
                }
                break;

            case 'month':
            default:
                $start = $now->copy()->startOfMonth();
                for ($i = 0; $i < 4; $i++) {
                    $startOfWeek = $start->copy()->addWeeks($i);
                    $endOfWeek = $startOfWeek->copy()->addDays(6);
                    $labels[] = 'Week ' . ($i + 1);
                    $count = Reservation::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
                    $data[] = $count;
                }

                // Previous month
                $previousStart = $now->copy()->subMonth()->startOfMonth();
                $previousEnd = $previousStart->copy()->endOfMonth();
                $previousTotal = Reservation::whereBetween('created_at', [$previousStart, $previousEnd])->count();
                break;
        }

        $growth = $previousTotal > 0
            ? round((($total - $previousTotal) / $previousTotal) * 100)
            : 0;

        // Best month = index of max count
        $bestIndex = array_keys($data, max($data))[0] ?? null;
        $bestLabel = $labels[$bestIndex] ?? 'N/A';

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'total' => $total,
            'growth_rate' => $growth,
            'best_period' => $bestLabel
        ]);
    }

    public function getThemeData(Request $request)
    {
        $filter = $request->query('filter', 'month');
        $now = now();
        $startDate = $now->copy()->startOfMonth();
        $endDate = $now->copy()->endOfMonth();

        if ($filter === 'week') {
            $startDate = $now->copy()->startOfWeek();
            $endDate = $now->copy()->endOfWeek();
        } elseif ($filter === 'year') {
            $startDate = $now->copy()->startOfYear();
            $endDate = $now->copy()->endOfYear();
        }

        // Get counts by theme_motif
        $themes = Inquiry::select('theme_motif', DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('theme_motif')
            ->orderByDesc('total')
            ->get();

        $labels = $themes->pluck('theme_motif')->toArray();
        $data = $themes->pluck('total')->toArray();

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'most_popular' => $labels[0] ?? 'N/A',
            'total_themes' => count($labels)
        ]);
    }

    public function getEventTypeData(Request $request)
    {
        $filter = $request->query('filter', 'month');
        $now = now();
        $startDate = $now->copy()->startOfMonth();
        $endDate = $now->copy()->endOfMonth();

        if ($filter === 'week') {
            $startDate = $now->copy()->startOfWeek();
            $endDate = $now->copy()->endOfWeek();
        } elseif ($filter === 'year') {
            $startDate = $now->copy()->startOfYear();
            $endDate = $now->copy()->endOfYear();
        }

        $types = Inquiry::select('event_type', DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('event_type')
            ->orderByDesc('total')
            ->get();

        $labels = $types->pluck('event_type')->toArray();
        $data = $types->pluck('total')->toArray();

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'most_common' => $labels[0] ?? 'N/A',
            'total_types' => count($labels)
        ]);
    }

    public function index()
    {
        return view('admin.home');
    }
}
