@extends('layouts.admin')

@section('title', 'IT Management - Villa Salud')

@push('styles')
<style>
    .it-container { padding: 28px 32px; }
    .table-wrap { overflow-x: auto; }
    table.it-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    table.it-table th { text-align: left; padding: 12px 16px; background: var(--color-primary); color: white; font-weight: 600; font-size: 13px; white-space: nowrap; }
    table.it-table td { padding: 12px 16px; border-bottom: 1px solid var(--color-border); vertical-align: middle; font-size: 14px; color: var(--color-text); }
    table.it-table tbody tr:hover { background: var(--color-primary-50); }
    table.it-table tr.deleted td { color: #9ca3af; font-style: italic; }
    /* .badge-role — now using .badge-modern from app.css */
    .field { margin-bottom: 12px; }
    .field label { display: block; font-weight: 600; margin-bottom: 4px; color: var(--color-text); font-size: 13px; }
    .field input, .field select { width: 100%; border: 1px solid var(--color-border); border-radius: var(--radius-sm); padding: 9px 12px; font-size: 14px; }
    .pagination-info { font-size: 13px; color: var(--color-text-muted); margin-top: 12px; }
</style>
@endpush

@section('content')
<div class="it-container">
    <div class="admin-page-header">
        <h1>IT Management</h1>
        <p>System administration — manage admin accounts.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success" id="page-success">{{ session('success') }}</div>
    @endif

    {{-- Stats --}}
    <div class="admin-stats-row">
        <div class="admin-stat-card">
            <strong>{{ $activeAdminCount }}</strong>
            <span>Active Admins</span>
        </div>
        <div class="admin-stat-card">
            <strong>{{ $deletedAdminCount }}</strong>
            <span>Deactivated</span>
        </div>
        <div class="admin-stat-card">
            <strong>{{ $adminCount }}</strong>
            <span>Total Accounts</span>
        </div>
    </div>

    {{-- Admin Accounts List --}}
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>All Admin Accounts</h2>
            <button class="admin-btn admin-btn-primary" data-bs-toggle="modal" data-bs-target="#createAdminModal">+ Create New Admin</button>
        </div>
        <div class="table-wrap">
            <table class="it-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($admins as $admin)
                        <tr class="{{ $admin->trashed() ? 'deleted' : '' }}">
                            <td>{{ $admin->admin_id }}</td>
                            <td>{{ $admin->f_name }} {{ $admin->l_name }}</td>
                            <td>{{ $admin->email }}</td>
                            <td>{{ $admin->phone }}</td>
                            <td>
                                @if ($admin->role === 'super_admin')
                                    <span class="badge-modern success">Super Admin</span>
                                @else
                                    <span class="badge-modern warning">Admin</span>
                                @endif
                            </td>
                            <td>
                                @if ($admin->trashed())
                                    <span class="badge-modern danger">Deactivated</span>
                                @else
                                    <span class="badge-modern success">Active</span>
                                @endif
                            </td>
                            <td>
                                <div class="admin-action-group">
                                    @if (!$admin->trashed())
                                        <a href="{{ route('admin.it.edit', $admin->admin_id) }}" class="admin-btn admin-btn-primary admin-btn-sm">Edit</a>
                                        @if ($admin->role !== 'super_admin')
                                            <a href="{{ route('admin.it.permissions', $admin->admin_id) }}" class="admin-btn admin-btn-primary admin-btn-sm">Permissions</a>
                                        @endif
                                        @if ($admin->role !== 'super_admin' && (int) $admin->admin_id !== (int) auth('admin')->id())
                                            <form method="POST" action="{{ route('admin.it.deactivate', $admin->admin_id) }}" style="display:inline;" id="deactivate-form-{{ $admin->admin_id }}">
                                                @csrf
                                                <button type="button" class="admin-btn admin-btn-danger admin-btn-sm" onclick="showConfirmModal('Deactivate Admin', 'Deactivate {{ $admin->f_name }} {{ $admin->l_name }}?', 'deactivate-form-{{ $admin->admin_id }}')">Deactivate</button>
                                            </form>
                                        @endif
                                    @else
                                        <form method="POST" action="{{ route('admin.it.restore', $admin->admin_id) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm">Restore</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.it.force-delete', $admin->admin_id) }}" style="display:inline;" id="force-delete-form-{{ $admin->admin_id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="admin-btn admin-btn-danger admin-btn-sm" onclick="showConfirmModal('Delete Forever', 'Permanently delete this account? This cannot be undone.', 'force-delete-form-{{ $admin->admin_id }}')">Delete Forever</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted" style="padding: 20px; text-align: center;">No admin accounts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-info">
            Showing {{ $admins->firstItem() ?? 0 }} – {{ $admins->lastItem() ?? 0 }} of {{ $admins->total() }} admins
        </div>
        <div style="margin-top: 12px;">
            {{ $admins->links() }}
        </div>
    </div>
</div>

{{-- Create Admin Modal --}}
<div class="modal fade" id="createAdminModal" tabindex="-1" aria-labelledby="createAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createAdminModalLabel">Create New Admin Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.it.store') }}">
                @csrf
                <div class="modal-body">
                    @if (session('error'))
                        <div class="alert alert-error" style="margin-bottom: 16px;">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-error" style="margin-bottom: 16px;">{{ $errors->first() }}</div>
                    @endif
                    <div class="field">
                        <label>First Name</label>
                        <input type="text" name="f_name" value="{{ old('f_name') }}" required>
                    </div>
                    <div class="field">
                        <label>Last Name</label>
                        <input type="text" name="l_name" value="{{ old('l_name') }}" required>
                    </div>
                    <div class="field">
                        <label>Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required>
                    </div>
                    <div class="field">
                        <label>Phone</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" maxlength="11" required>
                    </div>
                    <div class="field">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="field">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirmation" required>
                    </div>
                    {{-- PROFILE PICTURE: commented out for now — re-enable later
                    <div class="field">
                        <label>Profile Picture</label>
                        <select name="profile_picture">
                            <option value="default.png" {{ old('profile_picture') === 'default.png' ? 'selected' : '' }}>Default</option>
                            <option value="boy.png" {{ old('profile_picture') === 'boy.png' ? 'selected' : '' }}>Boy</option>
                            <option value="boy1.png" {{ old('profile_picture') === 'boy1.png' ? 'selected' : '' }}>Boy 1</option>
                            <option value="boy2.png" {{ old('profile_picture') === 'boy2.png' ? 'selected' : '' }}>Boy 2</option>
                            <option value="girl.png" {{ old('profile_picture') === 'girl.png' ? 'selected' : '' }}>Girl</option>
                            <option value="girl1.png" {{ old('profile_picture') === 'girl1.png' ? 'selected' : '' }}>Girl 1</option>
                            <option value="girl2.png" {{ old('profile_picture') === 'girl2.png' ? 'selected' : '' }}>Girl 2</option>
                        </select>
                    </div>
                    --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="admin-btn admin-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="admin-btn admin-btn-primary">Create Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>
{{-- Confirm Modal --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalTitle">Confirm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="confirmModalMessage">Are you sure?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="admin-btn admin-btn-ghost" data-bs-dismiss="modal" id="confirmModalNo">Cancel</button>
                <button type="button" class="admin-btn admin-btn-danger" id="confirmModalYes">Confirm</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    @vite('resources/js/admin/it.js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Auto-dismiss success notification after 4 seconds
            var successAlert = document.getElementById('page-success');
            if (successAlert) {
                setTimeout(function () {
                    successAlert.style.transition = 'opacity 0.5s';
                    successAlert.style.opacity = '0';
                    setTimeout(function () {
                        successAlert.remove();
                    }, 500);
                }, 4000);
            }

            // Re-open create modal if there were validation errors
            @if (old('f_name') || old('l_name') || old('email') || old('phone') || $errors->any())
                var modalEl = document.getElementById('createAdminModal');
                if (modalEl) {
                    var modal = new bootstrap.Modal(modalEl);
                    modal.show();
                }
            @endif

            // Confirm modal logic
            var confirmModalEl = document.getElementById('confirmModal');
            var confirmModal = new bootstrap.Modal(confirmModalEl);
            var pendingFormId = null;

            window.showConfirmModal = function(title, message, formId) {
                document.getElementById('confirmModalTitle').textContent = title;
                document.getElementById('confirmModalMessage').textContent = message;
                pendingFormId = formId;
                confirmModal.show();
            };

            document.getElementById('confirmModalYes').addEventListener('click', function() {
                if (pendingFormId) {
                    document.getElementById(pendingFormId).submit();
                }
                confirmModal.hide();
                pendingFormId = null;
            });

            confirmModalEl.addEventListener('hidden.bs.modal', function () {
                pendingFormId = null;
            });
        });
    </script>
@endpush
