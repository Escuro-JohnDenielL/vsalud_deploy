@extends('layouts.admin')

@section('title', 'Waitlist')

@push('styles')
<style>
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

    .page-header h1 {
        color: var(--color-primary);
    }

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
    <div class="vs-page vs-admin-waitlist">
        <x-ui.page-header title="Waitlist" subtitle="Track patrons waiting for available dates. Entries are managed automatically." />

        <x-ui.table-search
            :action="route('admin.waitlist')"
            :value="$search"
            label="Search waitlist"
            placeholder="Patron name or email"
            input-id="waitlistSearch" />

        <div class="vs-stat-grid">
            <x-ui.stat-card :value="$stats['waiting']" label="Waiting" dot="warning" />
            <x-ui.stat-card :value="$stats['notified']" label="Notified" dot="info" />
            <x-ui.stat-card :value="$stats['claimed']" label="Claimed" dot="success" />
            <x-ui.stat-card :value="$stats['expired']" label="Expired" dot="danger" />
            <x-ui.stat-card :value="$stats['total']" label="Total" />
        </div>

        <!-- Waitlist entries grouped by date -->
        <div class="vs-table-frame">
            @if($grouped->isEmpty())
                <div class="vs-empty">
                    <p class="vs-empty__msg">No waitlist entries yet.</p>
                </div>
            @else
                @foreach($grouped as $date => $waitEntries)
                    <div class="date-group">
                        <div class="date-header">{{ vs_date($date) }}</div>
                        <div class="vs-table-wrap" role="region" tabindex="0" aria-label="Waitlist table for {{ $date }}">
                        <table class="vs-table">
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
                                @foreach($waitEntries as $entry)
                                    <tr class="vs-table__row">
                                        <td>{{ $entry->patron_name ?? 'None' }}</td>
                                        <td class="vs-cell-email">{{ $entry->patron_email ?? 'None' }}</td>
                                        <td>
                                            @if($entry->status === 'waiting')
                                                <x-ui.badge variant="warning">Waiting</x-ui.badge>
                                            @elseif($entry->status === 'notified')
                                                <x-ui.badge variant="info">Notified</x-ui.badge>
                                            @elseif($entry->status === 'claimed')
                                                <x-ui.badge variant="success">Claimed</x-ui.badge>
                                            @elseif($entry->status === 'expired')
                                                <x-ui.badge variant="danger">Expired</x-ui.badge>
                                            @else
                                                <span class="vs-none">None</span>
                                            @endif
                                        </td>
                                        <td class="vs-cell-nowrap">{{ vs_datetime($entry->created_at) }}</td>
                                        <td class="vs-cell-nowrap">{{ $entry->notified_at ? vs_datetime($entry->notified_at) : 'None' }}</td>
                                        <td class="vs-cell-nowrap">{{ $entry->claimed_at ? vs_datetime($entry->claimed_at) : 'None' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{ $entries->links() }}
    </div>
@endsection
