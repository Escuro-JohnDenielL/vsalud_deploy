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
@endphp

<section class="dashboard-container">
  <div class="admin-profile">
    <div class="profile-header">
      <div class="profile-pic-container">
        <img src="{{ asset('images/default.png') }}" alt="Admin Profile Picture" class="profile-pic" id="profile-pic">
        <button class="change-pic-btn" id="change-pic-btn">📷</button>
        <input type="file" id="profile-pic-input" accept="image/*" style="display: none;">
      </div>
      <h2 id="admin-name">{{ $user->name ?? ($user->f_name . ' ' . $user->l_name) }}</h2>
      <p>System Administrator</p>
    </div>
    <div class="profile-info">
      <h3>Profile Information</h3>
      <p><strong>Email:</strong> <span id="admin-email">{{ $user->email }}</span></p>
      <p><strong>Phone:</strong> <span id="admin-phone">{{ $user->phone ?? 'N/A' }}</span></p>
      <p><strong>Username:</strong> <span id="admin-username">{{ explode('@', $user->email)[0] }}</span></p>
      <p><strong>Password:</strong> •••••••• <a href="#" class="change-password" id="change-password-link">Change</a></p>
    </div>
    <div class="buttons">
      <button class="edit-btn" id="edit-profile-btn">Edit Profile</button>
      <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="button" id="logout-btn" class="logout-btn">Logout</button>
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
        <div class="empty-icon">📋</div>
        <p>No history found.</p>
      </div>
    </div>
    
    <div class="history-pagination">
      <button id="load-more-btn" class="load-more-btn" style="display: none;">Load More</button>
    </div>
  </div>
</section>

{{-- Edit Profile Modal --}}
<div id="edit-modal" class="modal" style="display: none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Edit Profile</h3>
      <span class="close" id="close-modal">&times;</span>
    </div>
    <div class="modal-body">
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
          <small style="color: #666; font-size: 12px;">Username is automatically generated from email</small>
        </div>
        <div class="modal-buttons">
          <button type="submit" class="btn btn-primary">Save Changes</button>
          <button type="button" id="cancel-edit" class="btn btn-secondary">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Change Password Modal --}}
<div id="password-modal" class="modal" style="display: none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Change Password</h3>
      <span class="close" id="close-password-modal">&times;</span>
    </div>
    <div class="modal-body">
      <form id="password-form">
        @csrf
        <div class="form-group">
          <label for="current-password">Current Password:</label>
          <input type="password" id="current-password" name="current_password" required>
        </div>
        <div class="form-group">
          <label for="new-password">New Password:</label>
          <input type="password" id="new-password" name="new_password" required>
        </div>
        <div class="form-group">
          <label for="confirm-password">Confirm New Password:</label>
          <input type="password" id="confirm-password" name="confirm_password" required>
        </div>
        <div class="modal-buttons">
          <button type="submit" class="btn btn-primary">Change Password</button>
          <button type="button" id="cancel-password" class="btn btn-secondary">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Confirm Modal --}}
<div id="confirm-modal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Confirm Logout</h3>
      <span class="close" id="confirm-cancel" style="cursor: pointer;">&times;</span>
    </div>
    <div class="modal-body">
      <p id="confirm-message" style="font-size: 16px; margin-bottom: 20px;">
        Are you sure you want to logout?
      </p>
      <div class="modal-buttons">
        <button id="confirm-ok" class="btn btn-primary">Yes</button>
        <button id="confirm-cancel" class="btn btn-secondary">No</button>
      </div>
    </div>
  </div>
</div>

{{-- Success Modal --}}
<div id="success-modal" class="modal" style="display: none;">
  <div class="modal-content">
    <h3>Success</h3>
    <p id="success-message-text">Action successful.</p>
    <div class="modal-buttons">
      <button id="success-ok-btn" class="btn btn-success">OK</button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
  @vite('resources/js/admin/profile.js')
@endpush