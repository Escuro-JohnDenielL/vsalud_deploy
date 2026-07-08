@extends('layouts.admin')

@section('title', 'Waitlist')

@push('styles')
<style>
    .waitlist-container {
        padding: 20px;
    }

    .page-header {
        margin-bottom: 24px;
    }

    .page-header h1 {
        font-size: 24px;
        color: #2c3e50;
        margin: 0 0 8px 0;
    }

    .page-header p {
        color: #666;
        margin: 0;
        font-size: 14px;
    }

    /* Stats cards */
    .stats-row {
        display: flex;
        gap: 16px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 16px 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        flex: 1;
        min-width: 120px;
        text-align: center;
    }

    .stat-card .number {
        font-size: 28px;
        font-weight: 700;
        color: #2c3e50;
        display: block;
    }

    .stat-card .label {
        font-size: 12px;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 4px;
    }

    .stat-card.waiting { border-top: 3px solid #f39c12; }
    .stat-card.notified { border-top: 3px solid #3498db; }
    .stat-card.claimed { border-top: 3px solid #27ae60; }
    .stat-card.expired { border-top: 3px solid #95a5a6; }

    /* Table styles (reuse existing pattern) */
    .table-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .date-group {
        border-bottom: 1px solid #eee;
    }

    .date-group:last-child {
        border-bottom: none;
    }

    .date-header {
        background: #f8f9fa;
        padding: 12px 20px;
        font-weight: 600;
        color: #2c3e50;
        font-size: 15px;
        border-bottom: 1px solid #eee;
    }

    .waitlist-table {
        width: 100%;
        border-collapse: collapse;
    }

    .waitlist-table th {
        text-align: left;
        padding: 10px 16px;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #888;
        border-bottom: 1px solid #eee;
        background: #fafafa;
    }

    .waitlist-table td {
        padding: 10px 16px;
        font-size: 14px;
        border-bottom: 1px solid #f0f0f0;
        color: #333;
    }

    .waitlist-table tr:last-child td {
        border-bottom: none;
    }

    .badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }

    .badge-waiting { background: #fef3cd; color: #856404; }
    .badge-notified { background: #cce5ff; color: #004085; }
    .badge-claimed { background: #d4edda; color: #155724; }
    .badge-expired { background: #e2e3e5; color: #383d41; }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #888;
    }

    .empty-state p {
        font-size: 16px;
        margin: 0;
    }
</style>
@endpush

@section('content')
    <div class="waitlist-container">
        <div class="page-header">
            <h1>Waitlist</h1>
            <p>Track patrons waiting for available dates. Read-only — entries are managed automatically.</p>
        </div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card waiting">
                <span class="number">{{ $stats['waiting'] }}</span>
                <span class="label">Waiting</span>
            </div>
            <div class="stat-card notified">
                <span class="number">{{ $stats['notified'] }}</span>
                <span class="label">Notified</span>
            </div>
            <div class="stat-card claimed">
                <span class="number">{{ $stats['claimed'] }}</span>
                <span class="label">Claimed</span>
            </div>
            <div class="stat-card expired">
                <span class="number">{{ $stats['expired'] }}</span>
                <span class="label">Expired</span>
            </div>
            <div class="stat-card">
                <span class="number">{{ $stats['total'] }}</span>
                <span class="label">Total</span>
            </div>
        </div>

        <!-- Waitlist entries grouped by date -->
        <div class="table-container">
            @if($grouped->isEmpty())
                <div class="empty-state">
                    <p>No waitlist entries yet.</p>
                </div>
            @else
                @foreach($grouped as $date => $entries)
                    <div class="date-group">
                        <div class="date-header">{{ $date }}</div>
                        <table class="waitlist-table">
                            <thead>
                                <tr>
                                    <th>Patron Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Notified At</th>
                                    <th>Claimed At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entries as $entry)
                                    <tr>
                                        <td>{{ $entry->patron_name }}</td>
                                        <td>{{ $entry->patron_email }}</td>
                                        <td>
                                            @if($entry->status === 'waiting')
                                                <span class="badge badge-waiting">Waiting</span>
                                            @elseif($entry->status === 'notified')
                                                <span class="badge badge-notified">Notified</span>
                                            @elseif($entry->status === 'claimed')
                                                <span class="badge badge-claimed">Claimed</span>
                                            @elseif($entry->status === 'expired')
                                                <span class="badge badge-expired">Expired</span>
                                            @endif
                                        </td>
                                        <td>{{ $entry->created_at->format('M d, Y g:i A') }}</td>
                                        <td>{{ $entry->notified_at ? $entry->notified_at->format('M d, Y g:i A') : '—' }}</td>
                                        <td>{{ $entry->claimed_at ? $entry->claimed_at->format('M d, Y g:i A') : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endsection
