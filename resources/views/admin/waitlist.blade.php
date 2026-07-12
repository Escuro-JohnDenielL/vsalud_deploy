@extends('layouts.admin')

@section('title', 'Waitlist')

@push('styles')
<style>
    .waitlist-container {
        padding: 28px 32px;
    }

    /* Stats cards */
    .stats-row {
        display: flex;
        gap: 16px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .stat-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: 16px 24px;
        flex: 1;
        min-width: 120px;
        text-align: center;
    }

    .stat-card .number {
        font-size: 28px;
        font-weight: 700;
        color: var(--color-primary);
        display: block;
    }

    .stat-card .label {
        font-size: 12px;
        color: var(--color-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 4px;
    }

    .stat-card.waiting { border-top: 3px solid var(--color-border); }
    .stat-card.notified { border-top: 3px solid var(--color-border); }
    .stat-card.claimed { border-top: 3px solid var(--color-primary); }
    .stat-card.expired { border-top: 3px solid var(--color-border); }

    /* Date grouping */
    .date-group {
        border-bottom: 1px solid var(--color-border);
    }

    .date-group:last-child {
        border-bottom: none;
    }

    .date-header {
        background: #f9fafb;
        padding: 12px 20px;
        font-weight: 600;
        color: var(--color-text);
        font-size: 15px;
        border-bottom: 1px solid var(--color-border);
    }

    .waitlist-table {
        width: 100%;
        border-collapse: collapse;
    }

    .waitlist-table th {
        text-align: left;
        padding: 12px 16px;
        font-size: 13px;
        font-weight: 600;
        color: white;
        background: var(--color-primary);
        white-space: nowrap;
    }

    .waitlist-table td {
        padding: 12px 16px;
        font-size: 14px;
        border-bottom: 1px solid var(--color-border);
        color: var(--color-text);
    }

    .waitlist-table tbody tr:hover {
        background: var(--color-primary-50);
    }

    .waitlist-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Badges */
    .badge-waiting,
    .badge-notified,
    .badge-claimed,
    .badge-expired {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-waiting { background: #f5f5f5; color: #616161; }
    .badge-notified { background: #e8f5e9; color: #2e7d32; }
    .badge-claimed { background: #e8f5e9; color: #2e7d32; }
    .badge-expired { background: #f5f5f5; color: #9ca3af; }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--color-text-muted);
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
        <div style="margin-top: 16px;">
            {{ $entries->links() }}
        </div>
    </div>
@endsection
