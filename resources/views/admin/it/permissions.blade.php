@extends('layouts.admin')

@section('title', 'Permissions - ' . $admin->f_name . ' ' . $admin->l_name)

@push('styles')
<style>
    .perm-back {
        display: inline-flex;
        align-items: center;
        gap: var(--vs-space-1);
        margin-bottom: var(--vs-space-4);
        color: var(--vs-color-text-secondary);
        font-family: var(--vs-font-ui);
        font-size: var(--vs-text-body-min);
        text-decoration: none;
    }

    .perm-back:hover {
        color: var(--vs-color-dark-green);
        text-decoration: underline;
    }

    .perm-back:focus-visible {
        outline: var(--vs-focus-width) solid var(--vs-focus-on-light);
        outline-offset: var(--vs-focus-offset);
    }

    .perm-admin {
        display: flex;
        align-items: center;
        gap: var(--vs-space-3);
        padding: var(--vs-space-3) var(--vs-space-4);
        border: 1px solid var(--vs-color-surface-pale-green);
        border-radius: var(--vs-radius-card);
        background: var(--vs-color-surface);
    }

    .perm-admin__avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        flex-shrink: 0;
        border-radius: var(--vs-radius-pill);
        background: var(--vs-color-dark-green);
        color: #fff;
        font-family: var(--vs-font-ui);
        font-size: 16px;
        font-weight: var(--vs-weight-bold);
    }

    .perm-admin__name {
        display: block;
        color: var(--vs-color-text);
        font-family: var(--vs-font-ui);
        font-size: var(--vs-text-body-min);
        font-weight: var(--vs-weight-semibold);
        line-height: 1.3;
    }

    .perm-admin__meta {
        display: block;
        color: var(--vs-color-text-secondary);
        font-family: var(--vs-font-ui);
        font-size: var(--vs-text-secondary-min);
    }

    .perm-section-title {
        margin: 0 0 var(--vs-space-3);
        color: var(--vs-color-deep-green);
        font-family: var(--vs-font-display);
        font-size: 20px;
        font-weight: var(--vs-weight-bold);
    }

    .perm-presets {
        margin-bottom: var(--vs-space-6);
    }

    .preset-grid {
        display: flex;
        flex-wrap: wrap;
        gap: var(--vs-space-3);
    }

    .preset-btn {
        display: flex;
        flex: 1 1 220px;
        min-width: 0;
        flex-direction: column;
        gap: 2px;
        padding: var(--vs-space-3) var(--vs-space-4);
        border: 1px solid var(--vs-button-secondary-border);
        border-radius: var(--vs-radius-control);
        background: var(--vs-color-surface);
        color: var(--vs-color-text);
        font-family: var(--vs-font-ui);
        text-align: left;
        cursor: pointer;
        transition: background-color .15s, border-color .15s, box-shadow .15s;
    }

    .preset-btn:hover {
        background: var(--vs-button-secondary-hover-bg);
        border-color: var(--vs-button-secondary-hover-border);
    }

    .preset-btn:focus-visible {
        outline: var(--vs-focus-width) solid var(--vs-focus-on-light);
        outline-offset: var(--vs-focus-offset);
    }

    .preset-btn[aria-pressed="true"] {
        border-color: var(--vs-color-dark-green);
        background: var(--vs-color-surface-pale-green);
        box-shadow: inset 0 0 0 1px var(--vs-color-dark-green);
    }

    .preset-btn__label {
        font-size: var(--vs-text-body-min);
        font-weight: var(--vs-weight-semibold);
    }

    .preset-btn__desc {
        color: var(--vs-color-text-secondary);
        font-size: var(--vs-text-secondary-min);
        line-height: var(--vs-leading-normal);
    }

    .perm-panel {
        padding: var(--vs-space-5);
        border: 1px solid var(--vs-color-control-border);
        border-radius: var(--vs-radius-card);
        background: var(--vs-color-surface);
        box-shadow: var(--vs-shadow-card);
    }

    .perm-item {
        display: flex;
        align-items: center;
        gap: var(--vs-space-3);
        min-height: 44px;
        padding: var(--vs-space-3) 0;
        border-bottom: 1px solid var(--vs-color-surface-pale-green);
        cursor: pointer;
    }

    .perm-item:last-child {
        border-bottom: 0;
    }

    .perm-item__input {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
        accent-color: var(--vs-color-dark-green);
        cursor: pointer;
    }

    .perm-item__input:focus-visible {
        outline: var(--vs-focus-width) solid var(--vs-focus-on-light);
        outline-offset: var(--vs-focus-offset);
    }

    .perm-item__label {
        color: var(--vs-color-text);
        font-family: var(--vs-font-ui);
        font-size: var(--vs-text-body-min);
        font-weight: var(--vs-weight-medium);
    }

    .perm-note {
        margin: var(--vs-space-4) 0 0;
        color: var(--vs-color-text-secondary);
        font-family: var(--vs-font-ui);
        font-size: var(--vs-text-secondary-min);
    }

    .perm-note strong {
        color: var(--vs-color-text);
    }

    .perm-actions {
        display: flex;
        flex-wrap: wrap;
        gap: var(--vs-space-3);
        margin-top: var(--vs-space-5);
    }

    .perm-dialog {
        width: min(460px, calc(100% - 32px));
        padding: var(--vs-space-6);
        border: 1px solid var(--vs-color-control-border);
        border-radius: var(--vs-radius-card);
        background: var(--vs-color-surface);
        color: var(--vs-color-text);
        font-family: var(--vs-font-ui);
        box-shadow: var(--vs-shadow-card);
    }

    .perm-dialog::backdrop {
        background: rgba(0, 76, 58, 0.45);
    }

    .perm-dialog__title {
        margin: 0 0 var(--vs-space-2);
        color: var(--vs-color-deep-green);
        font-family: var(--vs-font-display);
        font-size: 22px;
        font-weight: var(--vs-weight-bold);
    }

    .perm-dialog__body {
        margin: 0 0 var(--vs-space-5);
        color: var(--vs-color-text-secondary);
        font-size: var(--vs-text-body-min);
        line-height: var(--vs-leading-normal);
    }

    .perm-dialog__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: var(--vs-space-3);
    }

    @media (max-width: 480px) {
        .perm-dialog__actions {
            flex-direction: column-reverse;
        }

        .perm-dialog__actions > * {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<div class="vs-page">
    <a href="{{ route('admin.it.dashboard') }}" class="perm-back">&larr; Back to IT Dashboard</a>

    <x-ui.page-header
        title="Page Permissions"
        subtitle="Choose which pages this admin can access. Unchecked pages are hidden from their navigation and blocked on the server.">
        <x-slot:action>
            <div class="perm-admin">
                <span class="perm-admin__avatar" aria-hidden="true">{{ strtoupper(substr($admin->f_name, 0, 1)) }}{{ strtoupper(substr($admin->l_name, 0, 1)) }}</span>
                <span>
                    <span class="perm-admin__name">{{ $admin->f_name }} {{ $admin->l_name }}</span>
                    <span class="perm-admin__meta">{{ $admin->email }} &middot; {{ ucfirst($admin->role) }}</span>
                </span>
            </div>
        </x-slot:action>
    </x-ui.page-header>

    <section class="perm-presets" aria-labelledby="presetsHeading">
        <h2 class="perm-section-title" id="presetsHeading">Presets</h2>
        <div class="preset-grid" role="group" aria-labelledby="presetsHeading">
            @foreach ($presets as $key => $preset)
                <button type="button" class="preset-btn" data-preset="{{ $key }}" aria-pressed="false" onclick="applyPreset('{{ $key }}')">
                    <span class="preset-btn__label">{{ $preset['label'] }}</span>
                    <span class="preset-btn__desc">{{ $preset['description'] }}</span>
                </button>
            @endforeach
        </div>
    </section>

    <div class="perm-panel">
        <h2 class="perm-section-title">Available pages</h2>
        <div id="permissionsList">
            @foreach ($availablePages as $slug => $label)
                <label class="perm-item">
                    <input type="checkbox"
                           class="perm-item__input"
                           value="{{ $slug }}"
                           {{ in_array($slug, $grantedPages) ? 'checked' : '' }}>
                    <span class="perm-item__label">{{ $label }}</span>
                </label>
            @endforeach
        </div>

        <p class="perm-note">
            <strong>Always accessible:</strong> Admin Profile, Logout
            @if(auth('admin')->user()?->role === 'super_admin')
                &middot; IT Management
            @endif
        </p>

        <div class="perm-actions">
            <button type="button" class="admin-btn admin-btn-primary" id="savePermsBtn">Save Permissions</button>
        </div>
    </div>
</div>

<dialog id="presetDialog" class="perm-dialog" aria-labelledby="presetDialogTitle">
    <h2 class="perm-dialog__title" id="presetDialogTitle">Apply preset</h2>
    <p class="perm-dialog__body" id="presetDialogBody"></p>
    <div class="perm-dialog__actions">
        <button type="button" class="admin-btn admin-btn-ghost" id="presetCancelBtn">Cancel</button>
        <button type="button" class="admin-btn admin-btn-primary" id="presetConfirmBtn">Apply preset</button>
    </div>
</dialog>

@push('scripts')
<script>
    const adminId = {{ $admin->admin_id }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const PRESET_LABELS = @json(collect($presets)->map(fn ($preset) => $preset['label'])->all());
    const PRESET_DESCRIPTIONS = @json(collect($presets)->map(fn ($preset) => $preset['description'])->all());
    const PRESET_PAGES = @json(collect($presets)->map(fn ($preset) => $preset['pages'])->all());

    function getCheckedPages() {
        return [...document.querySelectorAll('#permissionsList input[type="checkbox"]:checked')]
            .map(cb => cb.value);
    }

    function sameSet(a, b) {
        if (a.length !== b.length) return false;
        return a.every(item => b.indexOf(item) !== -1);
    }

    // Marks the preset whose pages match the current selection.
    function syncPresetState() {
        const checked = getCheckedPages();
        document.querySelectorAll('.preset-btn').forEach(btn => {
            const pages = PRESET_PAGES[btn.dataset.preset] || [];
            btn.setAttribute('aria-pressed', sameSet(pages, checked) ? 'true' : 'false');
        });
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
                window.showAdminToast(d.message || 'Permissions saved.', 'success');
            } else {
                window.showAdminToast(d.message || 'Error saving permissions', 'error');
            }
        })
        .catch(() => window.showAdminToast('Network error', 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Save Permissions';
        });
    }

    // Presets use the shared dialog instead of window.confirm().
    let pendingPreset = null;
    const presetDialog = document.getElementById('presetDialog');
    const presetDialogTitle = document.getElementById('presetDialogTitle');
    const presetDialogBody = document.getElementById('presetDialogBody');

    function applyPreset(presetKey) {
        pendingPreset = presetKey;
        const label = PRESET_LABELS[presetKey] || presetKey;
        presetDialogTitle.textContent = 'Apply the ' + label + ' preset?';
        const description = PRESET_DESCRIPTIONS[presetKey] || '';
        presetDialogBody.textContent = (description ? description + '. ' : '')
            + (presetKey === 'no-access'
                ? 'This removes access to every page for this admin.'
                : 'This replaces all current permissions for this admin.');
        if (typeof presetDialog.showModal === 'function') {
            presetDialog.showModal();
        } else {
            submitPreset(presetKey);
        }
    }

    function submitPreset(presetKey) {
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
                syncPresetState();
                window.showAdminToast(d.message || 'Preset applied.', 'success');
            } else {
                window.showAdminToast(d.message || 'Error applying preset', 'error');
            }
        })
        .catch(() => window.showAdminToast('Network error', 'error'));
    }

    document.getElementById('presetConfirmBtn').addEventListener('click', () => {
        const presetKey = pendingPreset;
        presetDialog.close();
        if (presetKey) submitPreset(presetKey);
    });

    document.getElementById('presetCancelBtn').addEventListener('click', () => presetDialog.close());

    document.getElementById('savePermsBtn').addEventListener('click', savePermissions);

    document.getElementById('permissionsList').addEventListener('change', syncPresetState);

    syncPresetState();
</script>
@endpush
@endsection
