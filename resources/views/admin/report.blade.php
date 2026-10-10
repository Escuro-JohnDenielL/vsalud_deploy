@extends('layouts.admin')

@section('title', 'Report Dashboard')

@push('styles')
@vite('resources/css/admin/report.css')
<link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
@endpush

@section('content')

{{-- Header image --}}
<div class="header-image"></div>

{{-- Start content container --}}
<div class="report-container vs-admin-report">

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Loading Dashboard...</p>
        </div>
    </div>

    <x-ui.page-header title="Report Dashboard" subtitle="Monitor inquiries, reservations, and event trends.">
        <x-slot:action>
            <button type="button" class="refresh-btn" onclick="window.dashboardManager.refreshDashboard()">
                <i class='bx bx-refresh'></i>
                Refresh Data
            </button>
        </x-slot:action>
    </x-ui.page-header>

    <p class="report-last-updated">Last updated: <span id="last-updated-time">--</span></p>

    <div class="report-controls">
        <label class="vs-label" for="report-period">Period</label>
        <select id="report-period" class="vs-input">
            <option value="week">This Week</option>
            <option value="month" selected>This Month</option>
            <option value="3months">Last 3 Months</option>
            <option value="year">This Year</option>
            <option value="all">All Time</option>
        </select>
    </div>

    <div class="vs-stat-grid">
        <div class="vs-stat-card">
            <strong class="vs-stat-card__value" id="total-inquiries-overview">0</strong>
            <span class="vs-stat-card__label"><span class="vs-stat-card__dot vs-stat-card__dot--info" aria-hidden="true"></span>Total Inquiries</span>
            <div class="metric-change positive" id="inquiry-change">
                <i class='bx bx-trending-up'></i>
                <span>0% vs last period</span>
            </div>
        </div>

        <div class="vs-stat-card">
            <strong class="vs-stat-card__value" id="total-reservations-overview">0</strong>
            <span class="vs-stat-card__label"><span class="vs-stat-card__dot vs-stat-card__dot--success" aria-hidden="true"></span>Total Reservations</span>
            <div class="metric-change positive" id="reservation-change">
                <i class='bx bx-trending-up'></i>
                <span>0% vs last period</span>
            </div>
        </div>
    </div>

    <!-- Chart Panels -->
    <div class="chart-grid">
        @foreach ([
        ['id' => 'inquiryChart', 'title' => 'Inquiries by date submitted', 'desc' => 'Track inquiry patterns over time', 'stats' => [['Peak', 'inquiry-peak-day'], ['Average Daily', 'inquiry-avg-daily']], 'filter' => 'inquiry-filter'],
        ['id' => 'reservation-chart', 'title' => 'Reservations by event date', 'desc' => 'Monitor bookings by their event date', 'stats' => [['Best period', 'reservation-best-month'], ['Growth Rate', 'reservation-growth']], 'filter' => 'reservation-filter'],
        ['id' => 'themeChart', 'title' => 'Theme Popularity', 'desc' => 'Most requested themes and packages', 'stats' => [['Most Popular', 'most-popular-theme'], ['Total Themes', 'total-themes']], 'filter' => 'theme-filter'],
        ['id' => 'inquiry-type-chart', 'title' => 'Inquiry Categories', 'desc' => 'Breakdown by inquiry type', 'stats' => [['Top Category', 'top-inquiry-category'], ['Total Categories', 'inquiry-response-rate']], 'filter' => 'inquiry-type-chart-filter'],
        ] as $chart)
        <div class="chart-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>{{ $chart['title'] }}</h3>
                    <p>{{ $chart['desc'] }}</p>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="{{ $chart['id'] }}"></canvas>
            </div>
            <div class="chart-footer">
                <div class="chart-stats">
                    @foreach ($chart['stats'] as [$label, $id])
                    <div class="stat-item">
                        <span class="stat-label" id="{{ $id }}-label">{{ $label }}:</span>
                        <span class="stat-value" id="{{ $id }}">--</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Activity Feed -->
    <div class="activity-section">
        <div class="activity-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>Recent Activity</h3>
                    <p>Latest administrative actions and system events</p>
                </div>
                <div class="panel-controls">
                    <button class="icon-btn" onclick="window.dashboardManager.loadActivityLog()" title="Refresh Activity">
                        <i class='bx bx-refresh'></i>
                    </button>
                </div>
            </div>
            <div class="activity-content" id="activity-log">
                <div class="activity-loading">
                    <div class="loading-dots">
                        <span></span><span></span><span></span>
                    </div>
                    <p>Loading recent activity...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Dummy hidden elements for compatibility -->
    <span id="total-inquiries" style="display:none;"></span>
    <span id="total-reservations" style="display:none;"></span>

</div> {{-- End .report-container --}}

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
@vite('resources/js/admin/report.js')
@endpush