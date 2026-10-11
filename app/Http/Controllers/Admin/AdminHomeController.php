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
    /**
     * Display the admin dashboard with packages.
     */
    public function index()
    {
        $packages = \App\Models\Package::all();
        return view('admin.home', compact('packages'));
    }

    /**
     * Resolve the page-level period into current and previous bounds.
     * Returns [start, end, previousStart, previousEnd]; nulls mean "no limit".
     */
    private function periodBounds(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'week' => [
                $now->copy()->subDays(6)->startOfDay(),
                $now->copy()->endOfDay(),
                $now->copy()->subDays(13)->startOfDay(),
                $now->copy()->subDays(7)->endOfDay(),
            ],
            '3months' => [
                $now->copy()->subMonthsNoOverflow(2)->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->copy()->subMonthsNoOverflow(5)->startOfMonth(),
                $now->copy()->subMonthsNoOverflow(3)->endOfMonth(),
            ],
            'year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
                $now->copy()->subYear()->startOfYear(),
                $now->copy()->subYear()->endOfYear(),
            ],
            'all' => [null, null, null, null],
            default => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
        };
    }

    /**
     * Build [labels, data] for a time series.
     * Buckets: week = daily, month = weekly, 3months/year/all = monthly
     * ("all" is capped at 24 monthly buckets).
     */
    private function timeSeries(string $model, string $period, string $column, ?\Closure $scope = null): array
    {
        $now = Carbon::now();
        $labels = [];
        $data = [];
        $count = function ($query) use ($scope) {
            if ($scope) {
                $scope($query);
            }

            return $query->count();
        };

        switch ($period) {
            case 'week':
                for ($i = 6; $i >= 0; $i--) {
                    $day = $now->copy()->subDays($i);
                    $labels[] = $day->format('D');
                    $data[] = $count($model::whereDate($column, $day->toDateString()));
                }
                break;

            case '3months':
                for ($i = 2; $i >= 0; $i--) {
                    $month = $now->copy()->subMonthsNoOverflow($i);
                    $labels[] = $month->format('M Y');
                    $data[] = $count($model::whereYear($column, $month->year)->whereMonth($column, $month->month));
                }
                break;

            case 'year':
                for ($i = 0; $i < 12; $i++) {
                    $month = $now->copy()->startOfYear()->addMonths($i);
                    $labels[] = $month->format('M');
                    $data[] = $count($model::whereYear($column, $month->year)->whereMonth($column, $month->month));
                }
                break;

            case 'all':
                $earliest = $model::min($column);
                $cursor = $earliest ? Carbon::parse($earliest)->startOfMonth() : $now->copy()->startOfMonth();
                $guard = 0;
                while ($cursor->lte($now) && $guard < 24) {
                    $labels[] = $cursor->format('M Y');
                    $data[] = $count($model::whereYear($column, $cursor->year)->whereMonth($column, $cursor->month));
                    $cursor->addMonth();
                    $guard++;
                }
                break;

            case 'month':
            default:
                $start = $now->copy()->startOfMonth();
                $weeks = (int) ceil($start->copy()->endOfMonth()->day / 7);
                for ($i = 0; $i < $weeks; $i++) {
                    $from = $start->copy()->addWeeks($i);
                    $to = ($i === $weeks - 1) ? $start->copy()->endOfMonth() : $from->copy()->addDays(6);
                    $labels[] = 'Week ' . ($i + 1);
                    $data[] = $count($model::whereBetween($column, [$from, $to]));
                }
                break;
        }

        return [$labels, $data];
    }

    /**
     * Count for the previous period of equal length (0 for "all").
     */
    private function previousTotal(string $model, string $period, string $column, ?\Closure $scope = null): int
    {
        [, , $prevStart, $prevEnd] = $this->periodBounds($period);

        if (! $prevStart || ! $prevEnd) {
            return 0;
        }

        $query = $model::whereBetween($column, [$prevStart, $prevEnd]);
        if ($scope) {
            $scope($query);
        }

        return $query->count();
    }

    /**
     * Resolve the requested period from the query string.
     */
    private function resolvePeriod(Request $request): string
    {
        return $request->query('period', $request->query('filter', 'month'));
    }

    /**
     * Inquiry chart + KPI payload.
     */
    private function inquiryPayload(string $period): array
    {
        [$labels, $data] = $this->timeSeries(Inquiry::class, $period, 'created_at');
        $total = array_sum($data);
        $previousTotal = $this->previousTotal(Inquiry::class, $period, 'created_at');

        $change = $previousTotal > 0
            ? round((($total - $previousTotal) / $previousTotal) * 100)
            : 0;

        $averagePerDay = count($data) > 0 ? round($total / count($data), 1) : 0;
        $peakIndex = ! empty($data) ? (array_keys($data, max($data))[0] ?? null) : null;
        $peakDay = $labels[$peakIndex] ?? 'N/A';

        return [
            'labels' => $labels,
            'data' => $data,
            'total' => $total,
            'change_percentage' => $change,
            'average_per_day' => $averagePerDay,
            'peak_day' => $peakDay,
        ];
    }

    /**
     * Reservation chart + KPI payload.
     * Bucketed by EVENT DATE; cancelled bookings are excluded from the KPI,
     * the chart and the trend.
     */
    private function reservationPayload(string $period): array
    {
        $notCancelled = fn ($query) => $query->where('status', '!=', 'cancelled');

        [$labels, $data] = $this->timeSeries(Reservation::class, $period, 'date', $notCancelled);
        $total = array_sum($data);
        $previousTotal = $this->previousTotal(Reservation::class, $period, 'date', $notCancelled);

        $growth = $previousTotal > 0
            ? round((($total - $previousTotal) / $previousTotal) * 100)
            : 0;

        $bestIndex = ! empty($data) ? (array_keys($data, max($data))[0] ?? null) : null;
        $bestLabel = $labels[$bestIndex] ?? 'N/A';
        $bestPeriodLabel = match ($period) {
            'week' => 'Best day',
            'month' => 'Best week',
            '3months', 'year', 'all' => 'Best month',
            default => 'Best period',
        };

        return [
            'labels' => $labels,
            'data' => $data,
            'total' => $total,
            'growth_rate' => $growth,
            'best_period' => $bestLabel,
            'best_period_label' => $bestPeriodLabel,
        ];
    }

    /**
     * Theme popularity payload. Category counts come from inquiries, so the
     * range stays on created_at.
     */
    private function themePayload(string $period): array
    {
        [$startDate, $endDate] = $this->periodBounds($period);

        $themes = Inquiry::select('theme_motif', DB::raw('count(*) as total'))
            ->when($startDate, fn ($query) => $query->whereBetween('created_at', [$startDate, $endDate]))
            ->groupBy('theme_motif')
            ->orderByDesc('total')
            ->get();

        $labels = $themes->pluck('theme_motif')->toArray();
        $data = $themes->pluck('total')->toArray();

        return [
            'labels' => $labels,
            'data' => $data,
            'most_popular' => $labels[0] ?? 'N/A',
            'total_themes' => count($labels),
        ];
    }

    /**
     * Inquiry category payload.
     */
    private function eventTypePayload(string $period): array
    {
        [$startDate, $endDate] = $this->periodBounds($period);

        $types = Inquiry::select('event_type', DB::raw('count(*) as total'))
            ->when($startDate, fn ($query) => $query->whereBetween('created_at', [$startDate, $endDate]))
            ->groupBy('event_type')
            ->orderByDesc('total')
            ->get();

        $labels = $types->pluck('event_type')->toArray();
        $data = $types->pluck('total')->toArray();

        return [
            'labels' => $labels,
            'data' => $data,
            'most_common' => $labels[0] ?? 'N/A',
            'total_types' => count($labels),
        ];
    }

    /**
     * Everything the report dashboard needs, in a single response.
     *
     * The page used to fire four separate AJAX calls, and each one pays the
     * full app-bootstrap cost (~200ms) before touching the database, which
     * only takes a few milliseconds. One request is roughly four times faster
     * on the dashboard's first paint.
     */
    public function getReportData(Request $request)
    {
        $period = $this->resolvePeriod($request);

        return response()->json([
            'period' => $period,
            'inquiries' => $this->inquiryPayload($period),
            'reservations' => $this->reservationPayload($period),
            'themes' => $this->themePayload($period),
            'eventTypes' => $this->eventTypePayload($period),
        ]);
    }

    public function getInquiryData(Request $request)
    {
        return response()->json($this->inquiryPayload($this->resolvePeriod($request)));
    }

    public function getReservationData(Request $request)
    {
        return response()->json($this->reservationPayload($this->resolvePeriod($request)));
    }

    public function getThemeData(Request $request)
    {
        return response()->json($this->themePayload($this->resolvePeriod($request)));
    }

    public function getEventTypeData(Request $request)
    {
        return response()->json($this->eventTypePayload($this->resolvePeriod($request)));
    }
}
