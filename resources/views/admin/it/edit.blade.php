@extends('layouts.admin')

@section('title', 'Edit Admin - IT Management')

@push('styles')
<style>
    .edit-container { max-width: 600px; margin: 32px auto; padding: 0 20px 60px; }
    .edit-card { background: #fff; border-radius: 16px; border: 1px solid rgba(18,59,38,0.1); box-shadow: 0 10px 30px rgba(18,59,38,0.06); padding: 28px; }
    .edit-card h1 { font-family: var(--font-heading); font-size: 26px; color: var(--color-primary); margin: 0 0 6px; }
    .edit-card .back-link { display: inline-block; margin-bottom: 16px; color: var(--color-primary); font-weight: 600; text-decoration: none; font-size: 14px; }
    .edit-card .back-link:hover { text-decoration: underline; }
    .field { margin-bottom: 14px; }
    .field label { display: block; font-weight: 600; margin-bottom: 4px; color: #2d2d2d; font-size: 13px; }
    .field input, .field select { width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 9px 12px; font-size: 14px; }
    .field .hint { font-size: 12px; color: #6b7280; margin-top: 4px; }
    .password-field { position: relative; }
    .password-field input { padding-right: 44px; }
    .password-toggle { position: absolute; top: 50%; right: 6px; transform: translateY(-50%); width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 8px; background: transparent; color: #6b7280; cursor: pointer; }
    .password-toggle:hover { background: #f3f4f6; color: var(--color-primary); }
    .password-toggle:focus-visible { outline: 2px solid var(--color-primary); outline-offset: 2px; }
    .password-toggle[aria-pressed="true"] { color: var(--color-primary); }
    .btn-group { display: flex; gap: 10px; margin-top: 20px; }
    .btn-primary, .btn-ghost { border: none; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; display: inline-block; text-decoration: none; padding: 10px 20px; font-size: 14px; }
    .btn-primary { background: var(--color-primary); color: #fff; }
    .btn-primary:hover { background: #0a5e2f; }
    .btn-ghost { background: #f3f4f6; color: var(--color-text); border: 1px solid var(--color-border); }
    .alert { padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; }
    .alert-success { background: #e8f5e9; border: 1px solid #c8e6c9; color: #2e7d32; }
    .alert-error { background: #fce4ec; border: 1px solid #f8bbd0; color: #c62828; }
    .role-badge { display: inline-block; padding: 3px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; background: #e8f5e9; color: #2e7d32; margin-top: 4px; }
</style>
@endpush

@section('content')
<div class="edit-container">
    <div class="edit-card">
        <a href="{{ route('admin.it.dashboard') }}" class="back-link">&larr; Back to IT Dashboard</a>
        <h1>Edit Admin Account</h1>

        <form method="POST" action="{{ route('admin.it.update', $admin->admin_id) }}">
            @csrf

            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $admin->email) }}" required>
            </div>
            <div class="field">
                <label>First Name</label>
                <input type="text" name="f_name" value="{{ old('f_name', $admin->f_name) }}" required>
            </div>
            <div class="field">
                <label>Last Name</label>
                <input type="text" name="l_name" value="{{ old('l_name', $admin->l_name) }}" required>
            </div>
            <div class="field">
                <label>Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $admin->phone) }}" maxlength="11" required>
            </div>
            {{-- PROFILE PICTURE: commented out for now — re-enable later
            <div class="field">
                <label>Profile Picture</label>
                <select name="profile_picture">
                    <option value="default.png" {{ $admin->profile_picture === 'default.png' ? 'selected' : '' }}>Default</option>
                    <option value="boy.png" {{ $admin->profile_picture === 'boy.png' ? 'selected' : '' }}>Boy</option>
                    <option value="boy1.png" {{ $admin->profile_picture === 'boy1.png' ? 'selected' : '' }}>Boy 1</option>
                    <option value="boy2.png" {{ $admin->profile_picture === 'boy2.png' ? 'selected' : '' }}>Boy 2</option>
                    <option value="girl.png" {{ $admin->profile_picture === 'girl.png' ? 'selected' : '' }}>Girl</option>
                    <option value="girl1.png" {{ $admin->profile_picture === 'girl1.png' ? 'selected' : '' }}>Girl 1</option>
                    <option value="girl2.png" {{ $admin->profile_picture === 'girl2.png' ? 'selected' : '' }}>Girl 2</option>
                </select>
            </div>
            --}}
            <div class="field">
                <label for="newAdminPassword">New Password <span class="hint">(leave blank to keep current)</span></label>
                <div class="password-field">
                    <input type="password" name="password" id="newAdminPassword" autocomplete="new-password">
                    <button type="button" class="password-toggle" id="toggleNewAdminPassword"
                            aria-label="Show new password" aria-pressed="false">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <div class="field">
                <label>Confirm New Password</label>
                <input type="password" name="password_confirmation">
            </div>

             <div class="it-help-text">
                Role: <span class="role-badge">{{ $admin->role === 'super_admin' ? 'Super Admin' : 'Admin' }}</span>
                &middot; Status: {{ $admin->trashed() ? 'Deactivated' : 'Active' }}
            </div>

            <div class="btn-group">
                <button type="submit" class="admin-btn admin-btn-primary">Save Changes</button>
                <a href="{{ route('admin.it.dashboard') }}" class="admin-btn admin-btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var input = document.getElementById('newAdminPassword');
        var toggle = document.getElementById('toggleNewAdminPassword');
        if (!input || !toggle) return;

        toggle.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
            toggle.setAttribute('aria-label', show ? 'Hide new password' : 'Show new password');
            input.focus();
        });
    })();
</script>
@endpush
