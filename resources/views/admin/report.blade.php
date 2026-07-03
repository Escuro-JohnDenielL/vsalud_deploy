@extends('layouts.admin')

@section('title', 'Report Dashboard')

@push('styles')
@vite('resources/css/admin/report.css')
<link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
@endpush

@section('content')

{{-- Header image --}}
<div class="header-image"></div>

{{-- ✅ Start content container --}}
<div class="report-container">

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Loading Dashboard...</p>
        </div>
    </div>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="header-content">
            
            <div class="header-left">
                <h1>Report Dashboard</h1>
            </div>

            <div class="refresh-container">
                <button class="refresh-btn" onclick="window.dashboardManager.refreshDashboard()">
                    <i class='bx bx-refresh'></i>
                    Refresh Data
                </button>
                <div class="last-updated">
                    <span>Last updated: <span id="last-updated-time">--</span></span>
                </div>
            </div>

            <!-- Key Metrics Overview - Forced to Single Row -->
            <div class="metrics-overview">
                <div class="metric-card primary">
                    <div class="metric-icon">
                        <i class='bx bxs-message-dots'></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value" id="total-inquiries-overview">0</div>
                        <div class="metric-label">Total Inquiries</div>
                        <div class="metric-change positive" id="inquiry-change">
                            <i class='bx bx-trending-up'></i>
                            <span>+12% vs last period</span>
                        </div>
                    </div>
                </div>

                <div class="metric-card secondary">
                    <div class="metric-icon">
                        <i class='bx bxs-calendar-check'></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value" id="total-reservations-overview">0</div>
                        <div class="metric-label">Total Reservations</div>
                        <div class="metric-change positive" id="reservation-change">
                            <i class='bx bx-trending-up'></i>
                            <span>+8% vs last period</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Chart Panels -->
    <div class="chart-grid">
        @foreach ([
        ['id' => 'inquiryChart', 'title' => 'Inquiries Trend', 'desc' => 'Track inquiry patterns over time', 'stats' => [['Peak', 'inquiry-peak-day'], ['Average Daily', 'inquiry-avg-daily']], 'filter' => 'inquiry-filter'],
        ['id' => 'reservation-chart', 'title' => 'Reservation Analytics', 'desc' => 'Monitor booking performance', 'stats' => [['Best Month', 'reservation-best-month'], ['Growth Rate', 'reservation-growth']], 'filter' => 'reservation-filter'],
        ['id' => 'themeChart', 'title' => 'Theme Popularity', 'desc' => 'Most requested themes and packages', 'stats' => [['Most Popular', 'most-popular-theme'], ['Total Themes', 'total-themes']], 'filter' => 'theme-filter'],
        ['id' => 'inquiry-type-chart', 'title' => 'Inquiry Categories', 'desc' => 'Breakdown by inquiry type', 'stats' => [['Top Category', 'top-inquiry-category'], ['Response Rate', 'inquiry-response-rate']], 'filter' => 'inquiry-type-chart-filter'],
        ] as $chart)
        <div class="chart-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>{{ $chart['title'] }}</h3>
                    <p>{{ $chart['desc'] }}</p>
                </div>
                <div class="panel-controls">
                    <select id="{{ $chart['filter'] }}" class="filter-select">
                        <option value="day">Today</option>
                        <option value="week">This Week</option>
                        <option value="month" selected>This Month</option>
                        <option value="year">This Year</option>
                    </select>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="{{ $chart['id'] }}"></canvas>
            </div>
            <div class="chart-footer">
                <div class="chart-stats">
                    @foreach ($chart['stats'] as [$label, $id])
                    <div class="stat-item">
                        <span class="stat-label">{{ $label }}:</span>
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

</div> {{-- ✅ End .report-container --}}

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
@vite('resources/js/admin/report.js')
@endpush