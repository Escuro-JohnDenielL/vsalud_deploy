@extends('layouts.admin')

@section('title', 'Incident Logs')

@push('styles')
    @vite('resources/css/admin/incident-logs.css')
@endpush

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>Incident Logs</h1>
            <p>Record, track, and manage security incidents and system issues.</p>
        </div>

        {{-- Stats --}}
        <div class="incident-stats-row">
            <div class="incident-stat-card open">
                <strong>{{ $stats['open'] }}</strong>
                <span>Open / Investigating</span>
            </div>
            <div class="incident-stat-card resolved">
                <strong>{{ $stats['resolved'] }}</strong>
                <span>Resolved / Closed</span>
            </div>
            <div class="incident-stat-card critical">
                <strong>{{ $stats['critical'] }}</strong>
                <span>Critical (Unresolved)</span>
            </div>
            <div class="incident-stat-card total">
                <strong>{{ $stats['total'] }}</strong>
                <span>Total Incidents</span>
            </div>
        </div>

        {{-- Log Incident Button --}}
        <div style="margin-bottom: 20px;">
            <button class="admin-btn admin-btn-primary" id="logIncidentBtn">+ Log Incident</button>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- Table --}}
        <div class="table-container">
            <div class="table-wrapper">
                <table class="incident-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Detected</th>
                            <th>Resolved</th>
                            <th>Reported By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incidents as $incident)
                            <tr>
                                <td>#{{ $incident->id }}</td>
                                <td class="incident-title">{{ \Illuminate\Support\Str::limit($incident->title, 60) }}</td>
                                <td>
                                    @php
                                        $severityClass = match($incident->severity) {
                                            'critical' => 'danger',
                                            'high'     => 'warning',
                                            'medium'   => 'info',
                                            'low'      => 'success',
                                            default    => 'info',
                                        };
                                    @endphp
                                    <span class="badge-modern {{ $severityClass }}">
                                        {{ ucfirst($incident->severity) }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $statusClass = match($incident->status) {
                                            'open'           => 'danger',
                                            'investigating'  => 'warning',
                                            'resolved'       => 'success',
                                            'closed'         => 'info',
                                            default          => 'info',
                                        };
                                    @endphp
                                    <span class="badge-modern {{ $statusClass }}">
                                        {{ ucfirst($incident->status) }}
                                    </span>
                                </td>
                                <td>{{ $incident->detected_at->format('M d, Y g:i A') }}</td>
                                <td>{{ $incident->resolved_at ? $incident->resolved_at->format('M d, Y g:i A') : '—' }}</td>
                                <td>{{ $incident->reported_by ?? '—' }}</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="admin-btn admin-btn-primary admin-btn-sm view-incident-btn"
                                            data-id="{{ $incident->id }}">View</button>
                                        @if(!in_array($incident->status, ['resolved', 'closed']))
                                            <button class="admin-btn admin-btn-ghost admin-btn-sm resolve-incident-btn"
                                                data-id="{{ $incident->id }}">Resolve</button>
                                        @endif
                                        <button class="admin-btn admin-btn-danger admin-btn-sm delete-incident-btn"
                                            data-id="{{ $incident->id }}"
                                            data-title="{{ $incident->title }}">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No incidents logged yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 16px;">
                {{ $incidents->links() }}
            </div>
        </div>
    </div>

    {{-- Log Incident Modal --}}
    <div id="logIncidentModal" class="modal">
        <div class="modal-content modal-lg">
            <span class="close-btn" onclick="closeModal('logIncidentModal')">&times;</span>
            <h2>Log Incident</h2>
            <p style="font-size: 14px; color: var(--color-text-muted); margin: -8px 0 16px;">Record a security or system incident.</p>

            <form id="logIncidentForm">
                @csrf
                <div class="form-group">
                    <label for="incidentTitle">Title <span class="text-danger">*</span></label>
                    <input type="text" id="incidentTitle" name="title" class="form-control" required maxlength="255" placeholder="e.g., Unauthorized login attempt detected">
                </div>

                <div class="form-group">
                    <label for="incidentDescription">Description <span class="text-danger">*</span></label>
                    <textarea id="incidentDescription" name="description" class="form-control" rows="4" required placeholder="Describe what happened, how it was detected, and any immediate actions taken."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="incidentSeverity">Severity <span class="text-danger">*</span></label>
                        <select id="incidentSeverity" name="severity" class="form-control" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="incidentDetectedAt">Detected At <span class="text-danger">*</span></label>
                        <input type="datetime-local" id="incidentDetectedAt" name="detected_at" class="form-control" required value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label for="incidentReportedBy">Reported By</label>
                    <input type="text" id="incidentReportedBy" name="reported_by" class="form-control" maxlength="100" placeholder="e.g., John (admin), walk-in patron, system alert">
                </div>

                <div class="modal-footer">
                    <button type="button" class="admin-btn admin-btn-ghost" onclick="closeModal('logIncidentModal')">Cancel</button>
                    <button type="submit" class="admin-btn admin-btn-primary">Log Incident</button>
                </div>
            </form>
        </div>
    </div>

    {{-- View Incident Modal --}}
    <div id="viewIncidentModal" class="modal">
        <div class="modal-content modal-lg">
            <span class="close-btn" onclick="closeModal('viewIncidentModal')">&times;</span>
            <h2>Incident Details</h2>
            <div id="incidentDetailBody">
                <p class="text-center text-muted">Loading...</p>
            </div>
        </div>
    </div>

    {{-- Resolve Incident Modal --}}
    <div id="resolveIncidentModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('resolveIncidentModal')">&times;</span>
            <h3>Resolve Incident</h3>
            <p style="font-size: 14px; color: var(--color-text-muted); margin: -8px 0 16px;">Update the status and add resolution notes.</p>

            <form id="resolveIncidentForm">
                @csrf
                <input type="hidden" id="resolveIncidentId" name="incident_id">

                <div class="form-group">
                    <label for="resolveStatus">Status <span class="text-danger">*</span></label>
                    <select id="resolveStatus" name="status" class="form-control" required>
                        <option value="open">Open</option>
                        <option value="investigating">Investigating</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="resolveResolution">Resolution Notes</label>
                    <textarea id="resolveResolution" name="resolution" class="form-control" rows="3" placeholder="Describe how this was resolved or any remediation steps taken."></textarea>
                </div>

                <div class="form-group" id="resolvedAtGroup">
                    <label for="resolveResolvedAt">Resolved At</label>
                    <input type="datetime-local" id="resolveResolvedAt" name="resolved_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                </div>

                <div class="modal-footer">
                    <button type="button" class="admin-btn admin-btn-ghost" onclick="closeModal('resolveIncidentModal')">Cancel</button>
                    <button type="submit" class="admin-btn admin-btn-primary">Update Incident</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Confirm Delete Modal --}}
    <div id="confirmDeleteModal" class="modal">
        <div class="modal-content modal-sm">
            <span class="close-btn" onclick="closeModal('confirmDeleteModal')">&times;</span>
            <h3>Confirm Delete</h3>
            <p id="confirmDeleteMessage" style="font-size: 15px; margin: 12px 0;">Are you sure you want to delete this incident?</p>
            <div class="modal-footer">
                <button class="admin-btn admin-btn-ghost" onclick="closeModal('confirmDeleteModal')">Cancel</button>
                <button class="admin-btn admin-btn-danger" id="confirmDeleteYes">Delete</button>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="toast" class="toast-notification" style="display:none;"></div>
@endsection

@push('scripts')
    <script>
        window.incidentLogRoutes = {
            store: '{{ route('admin.incident-logs.store') }}',
            show: '{{ url('admin/incident-logs') }}',
            update: '{{ url('admin/incident-logs') }}',
            destroy: '{{ url('admin/incident-logs') }}',
        };
    </script>
    @vite('resources/js/admin/incident-logs.js')
@endpush
