@extends('layouts.admin')

@section('title', 'Audit Logs')

@push('styles')
    @vite('resources/css/admin/audit-logs.css')
@endpush

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>Audit Logs</h1>
            <p>Every action taken inside the admin panel. Entries are append-only and cannot be edited or deleted.</p>
        </div>

        {{-- Summary counters (always over the whole trail, not the current filter) --}}
        <div class="audit-stats-row">
            <div class="audit-stat-card total">
                <strong>{{ number_format($stats['total']) }}</strong>
                <span>Total entries</span>
            </div>
            <div class="audit-stat-card today">
                <strong>{{ number_format($stats['today']) }}</strong>
                <span>Recorded today</span>
            </div>
            <div class="audit-stat-card security">
                <strong>{{ number_format($stats['sensitive']) }}</strong>
                <span>Auth &amp; security events</span>
            </div>
            <div class="audit-stat-card admins">
                <strong>{{ number_format($stats['admins']) }}</strong>
                <span>Admins on record</span>
            </div>
        </div>

        {{-- Filters (plain GET form — every view has a shareable URL) --}}
        <form class="audit-filters" method="GET" action="{{ route('admin.audit-logs') }}">
            <div class="audit-filter-field audit-filter-search">
                <label for="auditSearch">Search</label>
                <input type="text" id="auditSearch" name="q" maxlength="100"
                    value="{{ $filters['q'] }}"
                    placeholder="Description, action, admin, IP or route…">
            </div>

            <div class="audit-filter-field">
                <label for="auditCategory">Audit type</label>
                <select id="auditCategory" name="category" data-audit-autosubmit>
                    <option value="all">All types</option>
                    @foreach($categories as $slug => $meta)
                        <option value="{{ $slug }}" @selected($filters['category'] === $slug)>{{ $meta['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="audit-filter-field">
                <label for="auditAdmin">Admin</label>
                <select id="auditAdmin" name="admin_id" data-audit-autosubmit>
                    <option value="">All admins</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin['id'] }}" @selected($filters['admin_id'] === $admin['id'])>
                            {{ $admin['label'] }}{{ $admin['deactivated'] ? ' (deactivated)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="audit-filter-field">
                <label for="auditFrom">From</label>
                <input type="date" id="auditFrom" name="from" value="{{ $filters['from'] }}">
            </div>

            <div class="audit-filter-field">
                <label for="auditTo">To</label>
                <input type="date" id="auditTo" name="to" value="{{ $filters['to'] }}">
            </div>

            <div class="audit-filter-actions">
                <button type="submit" class="admin-btn admin-btn-primary">Apply</button>
                <a href="{{ route('admin.audit-logs') }}" class="admin-btn admin-btn-ghost">Reset</a>
                <a href="{{ route('admin.audit-logs.export', request()->query()) }}" class="admin-btn admin-btn-ghost">Export CSV</a>
            </div>
        </form>

        {{-- Result summary --}}
        <div class="audit-meta">
            <span class="audit-meta-count">
                @if($logs->total())
                    Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }}
                    {{ $logs->total() === 1 ? 'entry' : 'entries' }}
                @else
                    No entries
                @endif
            </span>

            @if($filters['category'] !== 'all')
                <span class="audit-filter-chip">Type: {{ $categories[$filters['category']]['label'] }}</span>
            @endif
            @if($filters['q'] !== '')
                <span class="audit-filter-chip">Search: "{{ $filters['q'] }}"</span>
            @endif
            @if($filters['from'] || $filters['to'])
                <span class="audit-filter-chip">
                    Dates: {{ $filters['from'] ?: 'start' }} → {{ $filters['to'] ?: 'now' }}
                </span>
            @endif
        </div>

        {{-- Trail --}}
        <div class="table-container">
            <div class="table-wrapper">
                <table class="audit-table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Admin</th>
                            <th>Type</th>
                            <th>Activity</th>
                            <th>Details</th>
                            <th>IP address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @include('admin.partials.audit-rows', ['logs' => $logs])
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="audit-pagination">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Entry detail modal (filled by resources/js/admin/audit-logs.js) --}}
    <div id="auditDetailModal" class="modal">
        <div class="modal-content modal-lg">
            <span class="close-btn" onclick="closeModal('auditDetailModal')">&times;</span>
            <h2>Audit Entry</h2>
            <p class="audit-modal-subtitle" id="auditDetailSubtitle">Loading entry…</p>

            <div class="detail-section">
                <div class="detail-section-title">Action</div>
                <div class="audit-detail-card">
                    <div class="detail-row">
                        <span class="detail-label">When</span>
                        <span class="detail-value" id="auditDetailWhen">—</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Admin</span>
                        <span class="detail-value" id="auditDetailAdmin">—</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Audit type</span>
                        <span class="detail-value"><span id="auditDetailCategory" class="audit-cat audit-cat-system">—</span></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Activity</span>
                        <span class="detail-value" id="auditDetailActivity">—</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Target</span>
                        <span class="detail-value" id="auditDetailSubject">—</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Request</span>
                        <span class="detail-value" id="auditDetailRequest">—</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">IP address</span>
                        <span class="detail-value" id="auditDetailIp">—</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Record ID</span>
                        <span class="detail-value" id="auditDetailLogId">—</span>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-section-title">Description</div>
                <div class="audit-description-block" id="auditDetailDescription">—</div>
            </div>

            <div class="detail-section">
                <div class="detail-section-title">Device / browser</div>
                <div class="audit-description-block audit-user-agent" id="auditDetailUserAgent">—</div>
            </div>

            <div class="modal-footer">
                <button type="button" class="admin-btn admin-btn-ghost" onclick="closeModal('auditDetailModal')">Close</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.auditLogRoutes = {
            // '__ID__' is replaced client-side with the entry's log_id.
            show: '{{ route('admin.audit-logs.show', ['log' => '__ID__']) }}',
        };
    </script>
    @vite('resources/js/admin/audit-logs.js')
@endpush
