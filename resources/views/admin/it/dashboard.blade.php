@extends('layouts.admin')

@section('title', 'IT Management - Villa Salud')

@push('styles')
<style>
    .it-container { padding: 32px 20px 60px; }
    .it-title { font-family: Georgia, serif; font-size: 30px; color: #123b26; margin: 0 0 6px; }
    .it-subtitle { color: #587064; margin: 0 0 24px; }
    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 28px; }
    .stat-card { background: #f6faf5; border-radius: 14px; padding: 18px; border: 1px solid #e2ece0; text-align: center; }
    .stat-card strong { display: block; font-size: 28px; color: #165c34; }
    .stat-card span { color: #5d6f63; font-size: 13px; }
    .panel { background: #fff; border-radius: 16px; border: 1px solid rgba(18,59,38,0.1); box-shadow: 0 10px 30px rgba(18,59,38,0.06); padding: 22px; margin-bottom: 24px; }
    .panel h2 { font-size: 20px; margin: 0 0 16px; color: #123b26; }
    .panel-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; border-bottom: 1px solid #eef2ed; padding-bottom: 10px; }
    .panel-header h2 { margin: 0; border-bottom: none; padding-bottom: 0; }
    .table-wrap { overflow-x: auto; }
    table.it-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    table.it-table th { text-align: left; padding: 10px 8px; border-bottom: 2px solid #e2ece0; color: #22332a; font-weight: 600; white-space: nowrap; }
    table.it-table td { padding: 10px 8px; border-bottom: 1px solid #eef2ed; vertical-align: middle; }
    table.it-table tr.deleted td { color: #999; font-style: italic; }
    .badge-role { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    .badge-super { background: #fff3cd; color: #856404; }
    .badge-admin { background: #e2ece0; color: #165c34; }
    .badge-deleted { background: #f8d7da; color: #721c24; }
    .field { margin-bottom: 12px; }
    .field label { display: block; font-weight: 600; margin-bottom: 4px; color: #22332a; font-size: 13px; }
    .field input, .field select { width: 100%; border: 1px solid #d7dfd5; border-radius: 10px; padding: 9px 12px; font-size: 14px; }
    .btn-primary, .btn-sm { border: none; border-radius: 999px; font-weight: 600; cursor: pointer; display: inline-block; text-decoration: none; }
    .btn-primary { background: #165c34; color: #fff; padding: 10px 20px; }
    .btn-sm { padding: 6px 14px; font-size: 12px; }
    .btn-success { background: #28a745; color: #fff; }
    .btn-warning { background: #ffc107; color: #212529; }
    .btn-danger { background: #dc3545; color: #fff; }
    .alert { padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; }
    .alert-success { background: #f0f8f2; border: 1px solid #d4e7d8; color: #155724; }
    .alert-error { background: #fff6f6; border: 1px solid rgba(160,30,30,0.25); color: #721c24; }
    .muted { color: #6b7d71; font-size: 13px; }
    .pagination-info { font-size: 13px; color: #587064; margin-top: 12px; }
    .action-group { display: flex; gap: 6px; flex-wrap: wrap; }
</style>
@endpush

@section('content')
<div class="it-container">
    <h1 class="it-title">IT Management</h1>
    <p class="it-subtitle">System administration — manage admin accounts.</p>

    @if (session('success'))
        <div class="alert alert-success" id="page-success">{{ session('success') }}</div>
    @endif

    {{-- Stats --}}
    <div class="stats-row">
        <div class="stat-card">
            <strong>{{ $activeAdminCount }}</strong>
            <span>Active Admins</span>
        </div>
        <div class="stat-card">
            <strong>{{ $deletedAdminCount }}</strong>
            <span>Deactivated</span>
        </div>
        <div class="stat-card">
            <strong>{{ $adminCount }}</strong>
            <span>Total Accounts</span>
        </div>
    </div>

    {{-- Quick Links --}}
    <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
        <a href="{{ route('admin.forms.index') }}"
           style="background:#165c34;color:#fff;border:none;border-radius:999px;padding:10px 24px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;">
           🏗️ Form Builder
        </a>
    </div>

    {{-- Admin Accounts List --}}
    <div class="panel">
        <div class="panel-header">
            <h2>All Admin Accounts</h2>
            <button class="btn-primary" data-bs-toggle="modal" data-bs-target="#createAdminModal">+ Create New Admin</button>
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
                                    <span class="badge-role badge-super">Super Admin</span>
                                @else
                                    <span class="badge-role badge-admin">Admin</span>
                                @endif
                            </td>
                            <td>
                                @if ($admin->trashed())
                                    <span class="badge-role badge-deleted">Deactivated</span>
                                @else
                                    <span class="badge-role badge-admin">Active</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-group">
                                    @if (!$admin->trashed())
                                        <a href="{{ route('admin.it.edit', $admin->admin_id) }}" class="btn-sm btn-warning">Edit</a>
                                        @if ($admin->role !== 'super_admin')
                                            <a href="{{ route('admin.it.permissions', $admin->admin_id) }}" class="btn-sm" style="background:#6f42c1;color:#fff;">Permissions</a>
                                        @endif
                                        @if ($admin->role !== 'super_admin' && (int) $admin->admin_id !== (int) auth('admin')->id())
                                            <form method="POST" action="{{ route('admin.it.deactivate', $admin->admin_id) }}" style="display:inline;" id="deactivate-form-{{ $admin->admin_id }}">
                                                @csrf
                                                <button type="button" class="btn-sm btn-danger" onclick="showConfirmModal('Deactivate Admin', 'Deactivate {{ $admin->f_name }} {{ $admin->l_name }}?', 'deactivate-form-{{ $admin->admin_id }}')">Deactivate</button>
                                            </form>
                                        @endif
                                    @else
                                        <form method="POST" action="{{ route('admin.it.restore', $admin->admin_id) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn-sm btn-success">Restore</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.it.force-delete', $admin->admin_id) }}" style="display:inline;" id="force-delete-form-{{ $admin->admin_id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn-sm btn-danger" onclick="showConfirmModal('Delete Forever', 'Permanently delete this account? This cannot be undone.', 'force-delete-form-{{ $admin->admin_id }}')">Delete Forever</button>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Admin</button>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="confirmModalNo">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmModalYes">Confirm</button>
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
