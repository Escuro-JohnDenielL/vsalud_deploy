@extends('layouts.admin')

@section('title', 'Permissions - ' . $admin->f_name . ' ' . $admin->l_name)

@push('styles')
<style>
    .perm-container { max-width: 780px; margin: 0 auto; padding: 32px 20px 60px; }
    .perm-title { font-family: var(--font-heading); font-size: 28px; color: var(--color-primary); margin: 0 0 4px; }
    .perm-subtitle { color: var(--color-text-muted); margin: 0 0 24px; }
    .perm-admin-card { background: #f9fafb; border-radius: 14px; padding: 18px 22px; border: 1px solid var(--color-border); margin-bottom: 24px; display: flex; align-items: center; gap: 16px; }
    .perm-admin-card .avatar { width: 48px; height: 48px; border-radius: 50%; background: var(--color-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; flex-shrink: 0; }
    .perm-admin-card .info h3 { margin: 0; font-size: 16px; color: #2d2d2d; }
    .perm-admin-card .info p { margin: 2px 0 0; color: var(--color-text-muted); font-size: 13px; }

    /* Presets */
    .presets-row { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; }
    .preset-btn { background: #fff; border: 1px solid var(--color-border); border-radius: 999px; padding: 8px 18px; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.15s; color: var(--color-text); }
    .preset-btn:hover { border-color: var(--color-primary); background: var(--color-primary-light); }
    .preset-btn.active { background: var(--color-primary); border-color: var(--color-primary); color: #fff; }
    .preset-btn .preset-desc { display: none; }

    /* Checklist */
    .perm-panel { background: #fff; border-radius: 16px; border: 1px solid var(--color-border); box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 22px; }
    .perm-panel h2 { font-size: 18px; color: #2d2d2d; margin: 0 0 16px; font-weight: 600; }
    .perm-item { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
    .perm-item:last-child { border-bottom: none; }
    .perm-item input[type="checkbox"] { width: 20px; height: 20px; accent-color: var(--color-primary); cursor: pointer; flex-shrink: 0; }
    .perm-item .perm-label { flex: 1; }
    .perm-item .perm-label strong { display: block; font-size: 14px; color: #2d2d2d; }
    .perm-item .perm-label span { font-size: 12px; color: #9ca3af; }
    .perm-item .perm-icon { font-size: 22px; width: 32px; text-align: center; flex-shrink: 0; }

    .perm-actions { margin-top: 20px; display: flex; gap: 10px; }
    .btn-save-perms { background: #0d7a3e; color: #fff; border: none; border-radius: var(--radius-sm); padding: 12px 32px; font-weight: 600; font-size: 15px; cursor: pointer; }
    .btn-save-perms:hover { background: #0a5e2f; }
    .btn-save-perms:disabled { opacity: 0.5; cursor: not-allowed; }
    .back-link { display: inline-block; margin-top: 16px; color: var(--color-text-muted); font-size: 14px; text-decoration: none; }
    .back-link:hover { text-decoration: underline; color: var(--color-primary); }

    .toast { position: fixed; bottom: 24px; right: 24px; background: var(--color-primary); color: #fff; padding: 12px 24px; border-radius: 12px; font-size: 14px; font-weight: 500; box-shadow: 0 4px 16px rgba(0,0,0,0.15); z-index: 2000; opacity: 0; transform: translateY(20px); transition: all 0.3s; pointer-events: none; }
    .toast.show { opacity: 1; transform: translateY(0); }
    .toast.error { background: #dc3545; }

    .page-slug-hint { font-size: 11px; color: #9ca3af; font-family: monospace; }
</style>
@endpush

@section('content')
<div class="perm-container">
    <a href="{{ route('admin.it.dashboard') }}" class="back-link">&larr; Back to IT Dashboard</a>

    <div class="perm-admin-card">
        <div class="avatar">{{ strtoupper(substr($admin->f_name, 0, 1)) }}{{ strtoupper(substr($admin->l_name, 0, 1)) }}</div>
        <div class="info">
            <h3>{{ $admin->f_name }} {{ $admin->l_name }}</h3>
            <p>{{ $admin->email }} &middot; {{ ucfirst($admin->role) }}</p>
        </div>
    </div>

    <h1 class="perm-title">Page Permissions</h1>
    <p class="perm-subtitle">Check the pages this admin can access. Unchecked pages are hidden from nav and blocked server-side.</p>

    {{-- Presets --}}
    <div class="presets-row">
        @foreach ($presets as $key => $preset)
            <button type="button" class="preset-btn" onclick="applyPreset('{{ $key }}')" title="{{ $preset['description'] }}">
                {{ $preset['label'] }}
            </button>
        @endforeach
    </div>

    {{-- Checklist --}}
    <div class="perm-panel">
        <h2>Available Pages</h2>
        <div id="permissionsList">
            @foreach ($availablePages as $slug => $label)
                <div class="perm-item">
                    <input type="checkbox"
                           id="perm_{{ $slug }}"
                           value="{{ $slug }}"
                           {{ in_array($slug, $grantedPages) ? 'checked' : '' }}>
                    <span class="perm-icon">{{ pageIcon($slug) }}</span>
                    <div class="perm-label">
                        <strong>{{ $label }}</strong>
                        <span class="page-slug-hint">{{ $slug }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <p style="font-size:13px;color:var(--color-text-muted);margin-top:16px;">
            <strong>Always accessible:</strong> Admin Profile, Logout
            @if(auth('admin')->user()?->role === 'super_admin')
                &middot; IT Management
            @endif
        </p>

        <div class="perm-actions">
            <button class="admin-btn admin-btn-primary" id="savePermsBtn" onclick="savePermissions()">Save Permissions</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

@push('scripts')
<script>
    const adminId = {{ $admin->admin_id }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    function getCheckedPages() {
        return [...document.querySelectorAll('#permissionsList input[type="checkbox"]:checked')]
            .map(cb => cb.value);
    }

    function savePermissions() {
        const btn = document.getElementById('savePermsBtn');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        fetch('/admin/permissions/' + adminId + '/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ pages: getCheckedPages() })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showToast(d.message);
            } else {
                showToast(d.message || 'Error saving permissions', true);
            }
        })
        .catch(() => showToast('Network error', true))
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Save Permissions';
        });
    }

    function applyPreset(presetKey) {
        if (!confirm('Apply "' + presetKey.replace('-', ' ') + '" preset? This will replace all current permissions for this admin.')) return;

        fetch('/admin/permissions/' + adminId + '/preset', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ preset: presetKey })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                // Update checkboxes to match preset pages
                document.querySelectorAll('#permissionsList input[type="checkbox"]').forEach(cb => {
                    cb.checked = d.pages.includes(cb.value);
                });
                showToast(d.message);
            } else {
                showToast(d.message || 'Error applying preset', true);
            }
        })
        .catch(() => showToast('Network error', true));
    }

    function showToast(msg, isError = false) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className = 'toast' + (isError ? ' error' : '');
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }
</script>
@endpush
@endsection

@php
function pageIcon($slug) {
    return match($slug) {
        'packages' => '📦',
        'reservations' => '📋',
        'inquiries' => '✉️',
        'reserve-logs' => '📊',
        'reports' => '📈',
        'feedback' => '💬',
        default => '📄',
    };
}
@endphp
