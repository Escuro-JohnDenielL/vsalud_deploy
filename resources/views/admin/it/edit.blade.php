@extends('layouts.admin')

@section('title', 'Edit Admin - IT Management')

@push('styles')
<style>
    .edit-container { max-width: 600px; margin: 32px auto; padding: 0 20px 60px; }
    .edit-card { background: #fff; border-radius: 16px; border: 1px solid rgba(18,59,38,0.1); box-shadow: 0 10px 30px rgba(18,59,38,0.06); padding: 28px; }
    .edit-card h1 { font-family: Georgia, serif; font-size: 26px; color: #123b26; margin: 0 0 6px; }
    .edit-card .back-link { display: inline-block; margin-bottom: 16px; color: #165c34; font-weight: 600; text-decoration: none; font-size: 14px; }
    .edit-card .back-link:hover { text-decoration: underline; }
    .field { margin-bottom: 14px; }
    .field label { display: block; font-weight: 600; margin-bottom: 4px; color: #22332a; font-size: 13px; }
    .field input, .field select { width: 100%; border: 1px solid #d7dfd5; border-radius: 10px; padding: 9px 12px; font-size: 14px; }
    .field .hint { font-size: 12px; color: #6b7d71; margin-top: 4px; }
    .btn-group { display: flex; gap: 10px; margin-top: 20px; }
    .btn-primary, .btn-ghost { border: none; border-radius: 999px; font-weight: 600; cursor: pointer; display: inline-block; text-decoration: none; padding: 10px 20px; font-size: 14px; }
    .btn-primary { background: #165c34; color: #fff; }
    .btn-ghost { background: #edf4ec; color: #165c34; }
    .alert { padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; }
    .alert-success { background: #f0f8f2; border: 1px solid #d4e7d8; color: #155724; }
    .alert-error { background: #fff6f6; border: 1px solid rgba(160,30,30,0.25); color: #721c24; }
    .role-badge { display: inline-block; padding: 3px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; background: #e2ece0; color: #165c34; margin-top: 4px; }
</style>
@endpush

@section('content')
<div class="edit-container">
    <div class="edit-card">
        <a href="{{ route('admin.it.dashboard') }}" class="back-link">&larr; Back to IT Dashboard</a>
        <h1>Edit Admin Account</h1>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

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
                <label>New Password <span class="hint">(leave blank to keep current)</span></label>
                <input type="password" name="password">
            </div>
            <div class="field">
                <label>Confirm New Password</label>
                <input type="password" name="password_confirmation">
            </div>

            <div style="margin: 6px 0 0; font-size: 13px; color: #587064;">
                Role: <span class="role-badge">{{ $admin->role === 'super_admin' ? 'Super Admin' : 'Admin' }}</span>
                &middot; Status: {{ $admin->trashed() ? 'Deactivated' : 'Active' }}
            </div>

            <div class="btn-group">
                <button type="submit" class="btn-primary">Save Changes</button>
                <a href="{{ route('admin.it.dashboard') }}" class="btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
