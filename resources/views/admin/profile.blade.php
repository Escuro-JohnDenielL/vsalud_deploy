@extends('layouts.admin')

@section('title', 'Admin Profile')

@push('styles')
  @vite('resources/css/admin/profile.css')
@endpush

@section('content')
@php
$user = Auth::guard('admin')->user() ?? (object)[
'name' => 'Admin User',
'email' => 'admin@villasalud.com',
'phone' => 'N/A'
];
$isSuperAdmin = $user && $user->role === 'super_admin';
$grantedPageCount = 0;
if ($user && !$isSuperAdmin) {
    $grantedPageCount = \App\Models\AdminPagePermission::where('admin_id', $user->admin_id)->count();
}
@endphp

@if($user && !$isSuperAdmin && $grantedPageCount === 0)
<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:flex-start;gap:12px;">
    <span style="font-size:22px;flex-shrink:0;">🔒</span>
    <div>
        <strong style="color:#2d2d2d;font-size:15px;display:block;margin-bottom:4px;">Limited Access</strong>
        <p style="color:#6b7280;margin:0;font-size:14px;line-height:1.5;">
            You don't have access to any pages yet. Please contact a Super Administrator to request access to the pages you need.
        </p>
    </div>
</div>
@elseif($user && !$isSuperAdmin && $grantedPageCount > 0 && $grantedPageCount < count(\App\Models\AdminPagePermission::availablePages()))
<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:flex-start;gap:12px;">
    <span style="font-size:22px;flex-shrink:0;">ℹ️</span>
    <div>
        <strong style="color:#2d2d2d;font-size:15px;display:block;margin-bottom:4px;">Partial Access</strong>
        <p style="color:#6b7280;margin:0;font-size:14px;line-height:1.5;">
            You have access to some pages. If you need access to additional modules, please contact a Super Administrator.
        </p>
    </div>
</div>
@endif

<section class="dashboard-container">
  <div class="admin-profile">
    <div class="profile-header">
      {{-- PROFILE PICTURE: commented out for now — re-enable later
      <div class="profile-pic-container">
        <img src="{{ asset('images/default.png') }}" alt="Admin Profile Picture" class="profile-pic" id="profile-pic">
        <button class="change-pic-btn" id="change-pic-btn">📷</button>
        <input type="file" id="profile-pic-input" accept="image/*" style="display: none;">
      </div>
      --}}
      <h2 id="admin-name" style="color:var(--color-primary);font-weight:600;">{{ $user->name ?? ($user->f_name . ' ' . $user->l_name) }}</h2>
      <p style="color:#6b7280;">System Administrator</p>
    </div>
    <div class="profile-info">
      <h3>Profile Information</h3>
      <p><strong>Email:</strong> <span id="admin-email">{{ $user->email }}</span></p>
      <p><strong>Phone:</strong> <span id="admin-phone">{{ $user->phone ?? 'N/A' }}</span></p>
      <p><strong>Username:</strong> <span id="admin-username">{{ explode('@', $user->email)[0] }}</span></p>
      <p><strong>Password:</strong> •••••••• <a href="#" class="change-password" id="change-password-link">Change</a></p>
    </div>
    <div class="buttons">
      <button class="admin-btn admin-btn-primary" id="edit-profile-btn">Edit Profile</button>
    </div>
  </div>

  {{-- MFA Section --}}
  <div class="profile-info" style="margin-top: 24px;">
    <h3>Two-Factor Authentication (2FA) <span id="mfa-status-badge" style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;vertical-align:middle;margin-left:8px;
      {{ $user->hasMfaEnabled() ? 'background:#f0fdf4;color:#0d7a3e;' : 'background:#fef2f2;color:#dc2626;' }}">
      {{ $user->hasMfaEnabled() ? 'Enabled' : 'Not Set Up' }}
    </span></h3>
    <div id="mfa-status-section" style="padding:16px;background:#f9fafb;border-radius:10px;border:1px solid #e5e7eb;">
      @if ($user->hasMfaEnabled())
        <p style="margin-bottom:8px;"><strong>Method:</strong> <span id="mfa-method-display">{{ $user->mfa_method_display }}</span></p>
        <p style="margin-bottom:8px;font-size:13px;color:#6b7280;">
          Each MFA verification lasts for <strong>6 hours</strong>. After that, you'll need to enter a new code.
        </p>
        <p style="margin-bottom:12px;font-size:13px;color:#6b7280;">
          You will be prompted for a verification code on every login.
        </p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <button class="admin-btn admin-btn-danger" id="disable-mfa-btn" style="font-size:13px;padding:8px 16px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;">Disable 2FA</button>
        </div>
      @else
        <p style="font-size:14px;color:#6b7280;margin-bottom:12px;">
          Two-factor authentication adds an extra layer of security to your account.
        </p>
        <a href="{{ route('admin.mfa.setup') }}" class="admin-btn admin-btn-primary" style="display:inline-block;padding:10px 20px;font-size:14px;text-decoration:none;">Set Up Two-Factor Authentication</a>
      @endif
    </div>
  </div>

  {{-- Disable MFA Confirmation Modal --}}
  <div id="disable-mfa-modal" class="modal">
    <div class="modal-content modal-sm">
      <span class="close-btn" id="close-disable-modal">&times;</span>
      <h3>Disable Two-Factor Authentication</h3>
      <p style="font-size:14px;color:#6b7280;margin-bottom:16px;">
        Disabling 2FA will make your account less secure. Please confirm your password to proceed.
      </p>
      <form id="disable-mfa-form">
        @csrf
        <div class="form-group">
          <label for="mfa-disable-password">Current Password:</label>
          <input type="password" id="mfa-disable-password" name="current_password" required style="width:100%;padding:10px 14px;border:2px solid #e5e7eb;border-radius:8px;font-size:14px;">
        </div>
        <div class="modal-footer">
          <button type="button" id="cancel-disable-mfa" class="admin-btn admin-btn-ghost">Cancel</button>
          <button type="submit" class="admin-btn admin-btn-danger" style="background:#dc2626;color:#fff;">Disable 2FA</button>
        </div>
      </form>
    </div>
  </div>

  <div class="admin-history">
    <h3>Admin History</h3>

    <div class="history-controls">
      <select id="history-filter" class="form-select">
        <option value="all">All Activities</option>
        <option value="login">Login/Logout</option>
        <option value="profile">Profile Changes</option>
        <option value="system">System Actions</option>
        <option value="security">Security</option>
      </select>
      <button class="btn btn-danger btn-sm" id="clear-history-btn">Clear History</button>
    </div>

    <div id="activityLogStats" class="history-stats">
      <div class="stat-item">
        <span class="stat-number" id="total-activities">0</span>
        <span class="stat-label">Total Activities</span>
      </div>
      <div class="stat-item">
        <span class="stat-number" id="today-activities">0</span>
        <span class="stat-label">Today</span>
      </div>
      <div class="stat-item">
        <span class="stat-number" id="this-week-activities">0</span>
        <span class="stat-label">This Week</span>
      </div>
    </div>

    <div class="history-list-container">
      <ul id="history-list" class="history-list"></ul>
      <div id="history-empty" class="history-empty" style="display: none;">
        <p>No history found.</p>
      </div>
    </div>
    
    <div class="history-pagination">
      <button id="load-more-btn" class="load-more-btn" style="display: none;">Load More</button>
    </div>
  </div>
</section>

{{-- Edit Profile Modal --}}
<div id="edit-modal" class="modal">
  <div class="modal-content">
    <span class="close-btn" id="close-modal">&times;</span>
    <h3>Edit Profile</h3>
    <form id="edit-form">
      @csrf
      <div class="form-group">
        <label for="edit-name">Full Name:</label>
        <input type="text" id="edit-name" name="name" required>
      </div>
      <div class="form-group">
        <label for="edit-email">Email:</label>
        <input type="email" id="edit-email" name="email" required>
      </div>
      <div class="form-group">
        <label for="edit-phone">Phone:</label>
        <input type="text" id="edit-phone" name="phone">
      </div>
      <div class="form-group">
        <label for="edit-username">Username:</label>
        <input type="text" id="edit-username" name="username" readonly style="background-color: #f5f5f5;">
        <small style="color: #6b7280; font-size: 12px;">Username is automatically generated from email</small>
      </div>
      <div class="modal-footer">
        <button type="button" id="cancel-edit" class="admin-btn admin-btn-ghost">Cancel</button>
        <button type="submit" class="admin-btn admin-btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

{{-- Change Password Modal --}}
<div id="password-modal" class="modal">
  <div class="modal-content">
    <span class="close-btn" id="close-password-modal">&times;</span>
    <h3>Change Password</h3>
    {{-- Step 1: Verify current password & send code --}}
    <div id="password-step-1">
      <div id="step-1-message" style="display: none;" class="info-message"></div>
      <div class="form-group">
        <label for="current-password">Current Password:</label>
        <input type="password" id="current-password" name="current_password" required autocomplete="off">
      </div>
      <div class="modal-footer">
        <button type="button" id="cancel-password" class="admin-btn admin-btn-ghost">Cancel</button>
        <button type="button" id="send-code-btn" class="admin-btn admin-btn-primary">Send Code to Email</button>
      </div>
    </div>

    {{-- Step 2: Enter code & new password --}}
    <div id="password-step-2" style="display: none;">
      <div class="form-group">
        <label for="reset-code">Verification Code:</label>
        <input type="text" id="reset-code" name="reset_code" maxlength="6" placeholder="Enter 6-digit code" required autocomplete="off">
        <small style="color: #6b7280; font-size: 12px;">Enter the code sent to your email</small>
      </div>
      <div class="form-group">
        <label for="new-password">New Password:</label>
        <input type="password" id="new-password" name="new_password" required autocomplete="off">
        <small style="color: #6b7280; font-size: 12px;">Min 8 characters, 1 uppercase, 1 number, 1 special character</small>
        <div class="password-strength-container" style="margin-top:8px;">
          <div style="height:6px; background:#e0e0e0; border-radius:4px; overflow:hidden;">
            <div id="strength-bar" style="width:0%; height:100%; background:#dc3545; border-radius:4px; transition:all 0.3s ease;"></div>
          </div>
          <span id="strength-text" style="font-size:12px; color:#999;"></span>
        </div>
        <div id="password-requirements" style="font-size:12px; margin-top:6px; line-height:2;">
          <div id="req-length" style="color:#999;">✗ At least 8 characters</div>
          <div id="req-uppercase" style="color:#999;">✗ At least 1 uppercase letter</div>
          <div id="req-lowercase" style="color:#999;">✗ At least 1 lowercase letter</div>
          <div id="req-number" style="color:#999;">✗ At least 1 number</div>
          <div id="req-special" style="color:#999;">✗ At least 1 special character</div>
        </div>
      </div>
      <div class="form-group">
        <label for="confirm-password">Confirm New Password:</label>
        <input type="password" id="confirm-password" name="confirm_password" required autocomplete="off">
      </div>
      <div class="form-group">
        <label>
          <input type="checkbox" id="show-password-fields"> Show Passwords
        </label>
      </div>
      <div class="modal-footer">
        <button type="button" id="back-to-step1" class="admin-btn admin-btn-ghost">Back</button>
        <button type="button" id="verify-code-btn" class="admin-btn admin-btn-primary">Change Password</button>
      </div>
    </div>
  </div>
</div>

{{-- Confirm Modal --}}
<div id="confirm-modal" class="modal">
  <div class="modal-content modal-sm">
    <span class="close-btn" id="confirm-cancel">&times;</span>
    <h3>Confirm Logout</h3>
    <p id="confirm-message" style="font-size: 16px; margin-bottom: 20px;">
      Are you sure you want to logout?
    </p>
    <div class="modal-footer">
      <button id="confirm-no" class="admin-btn admin-btn-ghost">No</button>
      <button id="confirm-ok" class="admin-btn admin-btn-primary">Yes</button>
    </div>
  </div>
</div>

{{-- Success Modal --}}
<div id="success-modal" class="modal">
  <div class="modal-content modal-sm">
    <h3>Success</h3>
    <p id="success-message-text">Action successful.</p>
    <div class="modal-footer">
      <button id="success-ok-btn" class="admin-btn admin-btn-primary">OK</button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
  @vite('resources/js/admin/profile.js')
  <script>
    // MFA Trusted Devices Management
    document.addEventListener('DOMContentLoaded', function() {
      const disableMfaBtn = document.getElementById('disable-mfa-btn');
      const disableMfaModal = document.getElementById('disable-mfa-modal');
      const closeDisableModal = document.getElementById('close-disable-modal');
      const cancelDisableMfa = document.getElementById('cancel-disable-mfa');
      const disableMfaForm = document.getElementById('disable-mfa-form');

      if (disableMfaBtn) {
        disableMfaBtn.addEventListener('click', function() {
          disableMfaModal.style.display = 'flex';
        });
      }

      if (closeDisableModal) {
        closeDisableModal.addEventListener('click', function() {
          disableMfaModal.style.display = 'none';
        });
      }

      if (cancelDisableMfa) {
        cancelDisableMfa.addEventListener('click', function() {
          disableMfaModal.style.display = 'none';
        });
      }

      if (disableMfaForm) {
        disableMfaForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const password = document.getElementById('mfa-disable-password').value;
          const btn = this.querySelector('button[type="submit"]');
          btn.disabled = true;
          btn.textContent = 'Disabling...';

          fetch('{{ route("admin.mfa.disable") }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
            },
            body: JSON.stringify({ current_password: password })
          })
          .then(r => r.json())
          .then(data => {
            btn.disabled = false;
            btn.textContent = 'Disable 2FA';
            if (data.success) {
              disableMfaModal.style.display = 'none';
              document.getElementById('mfa-status-section').innerHTML = `
                <p style="font-size:14px;color:#6b7280;margin-bottom:12px;">
                  Two-factor authentication adds an extra layer of security to your account.
                </p>
                <a href="{{ route('admin.mfa.setup') }}" class="admin-btn admin-btn-primary" style="display:inline-block;padding:10px 20px;font-size:14px;text-decoration:none;">Set Up Two-Factor Authentication</a>
              `;
              const badge = document.getElementById('mfa-status-badge');
              if (badge) {
                badge.textContent = 'Not Set Up';
                badge.style.background = '#fef2f2';
                badge.style.color = '#dc2626';
              }
              showSuccessMessage('Two-factor authentication has been disabled.');
            } else {
              alert(data.message || 'Failed to disable 2FA.');
            }
          })
          .catch(err => {
            btn.disabled = false;
            btn.textContent = 'Disable 2FA';
            alert('An error occurred. Please try again.');
          });
        });
      }

      function showSuccessMessage(message) {
        const modal = document.getElementById('success-modal');
        const text = document.getElementById('success-message-text');
        if (modal && text) {
          text.textContent = message;
          modal.style.display = 'flex';
          setTimeout(() => { modal.style.display = 'none'; }, 3000);
        }
      }
    });
  </script>
@endpush