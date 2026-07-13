@extends('layouts.admin')

@section('title', 'Edit Form - ' . $form->name)

@push('styles')
<style>
    .fbe-container { padding: 32px 20px 60px; max-width: 1200px; margin: 0 auto; }
    .fbe-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
    .fbe-header h1 { font-family: var(--font-heading); font-size: 28px; color: var(--color-primary); margin: 0; }
    .fbe-header p { color: var(--color-text-muted); margin: 4px 0 0; }
    /* .fbe-status — now using .badge-modern from app.css */

    .fbe-layout { display: grid; grid-template-columns: 1fr 360px; gap: 24px; align-items: start; }
    @media (max-width: 900px) { .fbe-layout { grid-template-columns: 1fr; } }

    /* Fields list (left column) */
    .fields-panel { background: var(--color-surface); border-radius: 16px; border: 1px solid var(--color-border); box-shadow: var(--shadow-sm); padding: 20px; }
    .fields-panel h2 { font-size: 18px; color: #2d2d2d; margin: 0 0 16px; padding-bottom: 10px; border-bottom: 1px solid #eef2ed; }
    .empty-fields { text-align: center; padding: 40px 20px; color: #6b7280; }

    .field-item { background: var(--color-bg); border: 1px solid var(--color-border); border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; display: flex; align-items: center; gap: 12px; cursor: grab; transition: box-shadow 0.15s; }
    .field-item:hover { box-shadow: 0 2px 8px rgba(18,59,38,0.08); }
    .field-item.dragging { opacity: 0.5; }
    .field-item.drag-over { border-color: var(--color-primary); border-style: dashed; }
    .field-item .drag-handle { color: #9ca3af; cursor: grab; font-size: 18px; flex-shrink: 0; }
    .field-item .field-icon { font-size: 20px; flex-shrink: 0; width: 32px; text-align: center; }
    .field-item .field-info { flex: 1; min-width: 0; }
    .field-item .field-info .field-label { font-weight: 600; color: #2d2d2d; font-size: 14px; }
    .field-item .field-info .field-meta { font-size: 12px; color: #9ca3af; margin-top: 2px; }
    .field-item .field-info .field-meta .req { color: #dc3545; font-weight: 600; }
    .field-item .field-actions { display: flex; gap: 6px; flex-shrink: 0; }
    .field-item .field-actions button { background: none; border: 1px solid #d7dfd5; border-radius: 6px; padding: 4px 10px; font-size: 12px; cursor: pointer; color: #6b7280; transition: all 0.1s; }
    .field-item .field-actions button:hover { background: #eef2ed; }
    .field-item .field-actions .btn-edit-field { color: var(--color-primary); border-color: var(--color-primary); }
    .field-item .field-actions .btn-edit-field:hover { background: var(--color-primary); color: #fff; }
    .field-item .field-actions .btn-del-field { color: #dc3545; border-color: #dc3545; }
    .field-item .field-actions .btn-del-field:hover { background: #dc3545; color: #fff; }
    .field-item.field-inactive { opacity: 0.55; }
    .field-item.field-fixed { background: #f0f5f0; border-color: #c8d8cc; }

    /* Add field panel (right column) */
    .add-panel { background: var(--color-surface); border-radius: 16px; border: 1px solid var(--color-border); box-shadow: var(--shadow-sm); padding: 20px; }
    .add-panel h2 { font-size: 18px; color: #2d2d2d; margin: 0 0 16px; font-weight: 600; }
    .add-panel .form-group { margin-bottom: 14px; }
    .add-panel label { display: block; font-weight: 600; margin-bottom: 4px; color: #2d2d2d; font-size: 13px; }
    .add-panel input, .add-panel select, .add-panel textarea { width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 9px 12px; font-size: 14px; box-sizing: border-box; }
    .add-panel .checkbox-row { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
    .add-panel .checkbox-row input[type="checkbox"] { width: auto; }
    .add-panel .checkbox-row label { margin: 0; }
    .btn-add-field { background: var(--color-primary); color: #fff; border: none; border-radius: var(--radius-sm); padding: 10px 24px; font-weight: 600; cursor: pointer; width: 100%; font-size: 14px; }
    .btn-add-field:hover { background: #0a5e2f; }

    /* Buttons row */
    .fbe-actions { margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap; }
    .btn-publish, .btn-preview, .btn-save-order { border: none; border-radius: var(--radius-sm); padding: 10px 24px; font-weight: 600; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
    .btn-publish { background: #0d7a3e; color: #fff; }
    .btn-publish:hover { background: #0a5e2f; }
    .btn-preview { background: #f3f4f6; color: #2d2d2d; border: 1px solid #e5e7eb; }
    .btn-preview:hover { background: #e5e7eb; }

    /* Modal */
    .modal-overlay-fb { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 1000; justify-content: center; align-items: center; }
    .modal-overlay-fb.active { display: flex; }
    .modal-box-fb { background: #fff; border-radius: 16px; padding: 28px; max-width: 520px; width: 90%; max-height: 90vh; overflow-y: auto; }
    .modal-box-fb h3 { margin: 0 0 16px; color: #2d2d2d; }
    .modal-box-fb .form-group { margin-bottom: 14px; }
    .modal-box-fb label { display: block; font-weight: 600; margin-bottom: 4px; color: #2d2d2d; font-size: 13px; }
    .modal-box-fb input, .modal-box-fb select, .modal-box-fb textarea { width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 9px 12px; font-size: 14px; box-sizing: border-box; }
    .modal-box-fb .checkbox-row { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
    .modal-box-fb .checkbox-row input[type="checkbox"] { width: auto; }
    .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; }
    .modal-actions button { border: none; border-radius: var(--radius-sm); padding: 8px 20px; font-weight: 600; cursor: pointer; }
    .modal-actions .btn-save { background: #0d7a3e; color: #fff; }
    .modal-actions .btn-cancel { background: #f3f4f6; color: #2d2d2d; }

    .options-editor { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px; margin-top: 6px; }
    .option-row { display: flex; gap: 6px; margin-bottom: 6px; align-items: center; }
    .option-row input { flex: 1; border: 1px solid #e5e7eb; border-radius: 6px; padding: 6px 10px; font-size: 13px; }
    .option-row .remove-opt { background: none; border: none; color: #dc3545; cursor: pointer; font-size: 18px; padding: 0 4px; }
    .add-opt-btn { background: none; border: 1px dashed #d1d5db; border-radius: 6px; padding: 6px 12px; font-size: 12px; color: #6b7280; cursor: pointer; width: 100%; margin-top: 4px; }
    .add-opt-btn:hover { background: #f9fafb; }

    .toast { position: fixed; bottom: 24px; right: 24px; background: var(--color-primary); color: #fff; padding: 12px 24px; border-radius: 12px; font-size: 14px; font-weight: 500; box-shadow: 0 4px 16px rgba(0,0,0,0.15); z-index: 2000; opacity: 0; transform: translateY(20px); transition: all 0.3s; pointer-events: none; }
    .toast.show { opacity: 1; transform: translateY(0); }
    .toast.error { background: #dc3545; }
</style>
@endpush

@section('content')
<div class="fbe-container">
    <div class="fbe-header">
        <div>
            <h1>{{ $form->name }}</h1>
            <p>{{ $form->description ?? 'No description' }}</p>
            <span class="badge-modern {{ $form->is_published ? 'success' : 'warning' }}" id="publishStatus">
                {{ $form->is_published ? 'Published' : 'Unpublished changes' }}
            </span>
        </div>
        <div style="text-align:right;">
            <a href="{{ route('admin.forms.index') }}" style="color:#6b7280;font-size:14px;">&larr; Back to Forms</a>
        </div>
    </div>

    <div class="fbe-layout">
        {{-- LEFT: Fields list --}}
        <div class="fields-panel">
            <h2>Form Fields <span style="font-weight:400;color:#9ca3af;font-size:13px;">(drag to reorder)</span></h2>

            <div id="fieldsContainer">
                @forelse ($form->fields as $field)
                    <div class="field-item {{ $field->is_active ? '' : 'field-inactive' }} {{ $field->is_fixed ? 'field-fixed' : '' }}"
                         data-field-id="{{ $field->id }}"
                         data-field-type="{{ $field->field_type }}"
                         draggable="true">
                        <span class="drag-handle">⠿</span>
                        <span class="field-icon">{{ fieldIcon($field->field_type) }}</span>
                        <div class="field-info">
                            <div class="field-label">{{ $field->label }}</div>
                            <div class="field-meta">
                                {{ $field->name }} &middot; {{ $field->field_type }}
                                @if($field->required) <span class="req">*required</span> @endif
                                @if(!$field->is_active) <span style="color:#dc3545;">&middot; hidden</span> @endif
                                @if($field->is_fixed) <span style="color:#9ca3af;">&middot; fixed</span> @endif
                            </div>
                        </div>
                        <div class="field-actions">
                            <button class="btn-edit-field" onclick="openEditModal({{ $field->id }})">Edit</button>
                            @if(!$field->is_fixed)
                                <button class="btn-del-field" onclick="deleteField({{ $field->id }})">Del</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-fields">No fields yet. Add one from the right panel.</div>
                @endforelse
            </div>

            <div class="fbe-actions">
                <button class="admin-btn admin-btn-primary" onclick="togglePublish()">
                    {{ $form->is_published ? 'Unpublish' : 'Publish Changes' }}
                </button>
                <a href="{{ route('admin.forms.preview', $form) }}" class="admin-btn admin-btn-ghost" target="_blank">Preview Form</a>
            </div>
        </div>

        {{-- RIGHT: Add new field --}}
        <div class="add-panel">
            <h2>Add New Field</h2>
            <form id="addFieldForm">
                @csrf
                <div class="form-group">
                    <label for="new_field_type">Field Type</label>
                    <select id="new_field_type" name="field_type" required>
                        <option value="">Select type...</option>
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                        <option value="email">Email</option>
                        <option value="tel">Phone</option>
                        <option value="select">Dropdown</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="radio">Radio</option>
                        <option value="date">Date</option>
                        <option value="time">Time</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="new_label">Label</label>
                    <input type="text" id="new_label" name="label" placeholder="e.g. Guest Count" required>
                </div>
                <div class="form-group">
                    <label for="new_placeholder">Placeholder</label>
                    <input type="text" id="new_placeholder" name="placeholder" placeholder="Optional hint text">
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" id="new_required" name="required" value="1">
                    <label for="new_required">Required field</label>
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" id="new_has_other" name="has_other_option" value="1">
                    <label for="new_has_other">Include "Others" option with text input</label>
                </div>
                <div class="form-group" id="newOptionsGroup" style="display:none;">
                    <label>Options (for dropdown/radio/checkbox)</label>
                    <div class="options-editor" id="newOptionsEditor">
                        <div class="option-row">
                            <input type="text" class="opt-label" placeholder="Label" value="Option 1">
                            <input type="text" class="opt-value" placeholder="Value" value="option_1">
                            <button type="button" class="remove-opt" onclick="this.parentElement.remove()">&times;</button>
                        </div>
                        <button type="button" class="add-opt-btn" onclick="addOptionRow('newOptionsEditor')">+ Add option</button>
                    </div>
                </div>
                <button type="submit" class="admin-btn admin-btn-primary" style="width:100%;">+ Add Field</button>
            </form>
        </div>
    </div>
</div>

{{-- Edit Field Modal --}}
<div class="modal-overlay-fb" id="editFieldModal">
    <div class="modal-box-fb">
        <h3>Edit Field</h3>
        <form id="editFieldForm">
            @csrf
            @method('PUT')
            <input type="hidden" id="edit_field_id" name="field_id">
            <div class="form-group">
                <label for="edit_field_type">Field Type</label>
                <select id="edit_field_type" name="field_type" required>
                    <option value="text">Text</option>
                    <option value="textarea">Textarea</option>
                    <option value="email">Email</option>
                    <option value="tel">Phone</option>
                    <option value="select">Dropdown</option>
                    <option value="checkbox">Checkbox</option>
                    <option value="radio">Radio</option>
                    <option value="date">Date</option>
                    <option value="time">Time</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_label">Label</label>
                <input type="text" id="edit_label" name="label" required>
            </div>
            <div class="form-group">
                <label for="edit_name">Machine Name</label>
                <input type="text" id="edit_name" name="name" required pattern="[a-z_][a-z0-9_]*" title="Lowercase letters, numbers, underscores">
            </div>
            <div class="form-group">
                <label for="edit_placeholder">Placeholder</label>
                <input type="text" id="edit_placeholder" name="placeholder">
            </div>
            <div class="checkbox-row">
                <input type="checkbox" id="edit_required" name="required" value="1">
                <label for="edit_required">Required</label>
            </div>
            <div class="checkbox-row">
                <input type="checkbox" id="edit_is_active" name="is_active" value="1">
                <label for="edit_is_active">Active (visible in form)</label>
            </div>
            <div class="checkbox-row">
                <input type="checkbox" id="edit_has_other" name="has_other_option" value="1">
                <label for="edit_has_other">Include "Others" option</label>
            </div>
            <div class="form-group" id="editOptionsGroup" style="display:none;">
                <label>Options</label>
                <div class="options-editor" id="editOptionsEditor">
                    <button type="button" class="add-opt-btn" onclick="addOptionRow('editOptionsEditor')">+ Add option</button>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-save">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div class="toast" id="toast"></div>

@push('scripts')
<script>
    const formId = {{ $form->id }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    // ===== Drag & Drop Reorder =====
    let dragSrcEl = null;

    document.querySelectorAll('.field-item[draggable]').forEach(item => {
        item.addEventListener('dragstart', e => {
            dragSrcEl = item;
            item.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', item.dataset.fieldId);
        });
        item.addEventListener('dragend', e => {
            item.classList.remove('dragging');
            document.querySelectorAll('.field-item').forEach(i => i.classList.remove('drag-over'));
        });
        item.addEventListener('dragover', e => {
            e.preventDefault();
            if (item !== dragSrcEl) item.classList.add('drag-over');
        });
        item.addEventListener('dragleave', e => {
            item.classList.remove('drag-over');
        });
        item.addEventListener('drop', e => {
            e.preventDefault();
            item.classList.remove('drag-over');
            if (dragSrcEl === item) return;

            const container = document.getElementById('fieldsContainer');
            const items = [...container.querySelectorAll('.field-item')];
            const fromIdx = items.indexOf(dragSrcEl);
            const toIdx = items.indexOf(item);

            if (fromIdx < toIdx) {
                item.parentNode.insertBefore(dragSrcEl, item.nextSibling);
            } else {
                item.parentNode.insertBefore(dragSrcEl, item);
            }

            saveOrder();
        });
    });

    function saveOrder() {
        const fieldIds = [...document.querySelectorAll('.field-item')].map(el => parseInt(el.dataset.fieldId));
        fetch(`/admin/forms/${formId}/fields/reorder`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ field_ids: fieldIds })
        }).then(r => r.json()).then(d => { if (d.success) showToast('Order saved'); });
    }

    // ===== Add Field =====
    document.getElementById('addFieldForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const options = collectOptions('newOptionsEditor');
        if (options.length > 0) formData.set('options', JSON.stringify(options));

        fetch(`/admin/forms/${formId}/fields`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: formData
        }).then(r => r.json()).then(d => {
            if (d.success) { showToast(d.message); setTimeout(() => location.reload(), 800); }
            else showToast(d.message || 'Error adding field', true);
        }).catch(() => showToast('Network error', true));
    });

    // ===== Delete Field =====
    function deleteField(fieldId) {
        if (!confirm('Delete this field? Existing submitted data will not be affected.')) return;
        fetch(`/admin/forms/${formId}/fields/${fieldId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(r => r.json()).then(d => {
            if (d.success) { showToast(d.message); setTimeout(() => location.reload(), 800); }
            else showToast(d.message || 'Error', true);
        });
    }

    // ===== Edit Field Modal =====
    let fieldsData = @json($form->fields);

    function openEditModal(fieldId) {
        const f = fieldsData.find(f => f.id === fieldId);
        if (!f) return;
        document.getElementById('edit_field_id').value = f.id;
        document.getElementById('edit_field_type').value = f.field_type;
        document.getElementById('edit_label').value = f.label;
        document.getElementById('edit_name').value = f.name;
        document.getElementById('edit_placeholder').value = f.placeholder || '';
        document.getElementById('edit_required').checked = !!f.required;
        document.getElementById('edit_is_active').checked = f.is_active !== false;
        document.getElementById('edit_has_other').checked = !!f.has_other_option;

        const optsGroup = document.getElementById('editOptionsGroup');
        const editor = document.getElementById('editOptionsEditor');
        if (['select', 'checkbox', 'radio'].includes(f.field_type)) {
            optsGroup.style.display = 'block';
            renderOptions(editor, f.options || []);
        } else {
            optsGroup.style.display = 'none';
        }

        document.getElementById('editFieldModal').classList.add('active');
    }

    function closeEditModal() {
        document.getElementById('editFieldModal').classList.remove('active');
    }

    document.getElementById('editFieldForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const fieldId = document.getElementById('edit_field_id').value;
        const formData = new FormData(this);
        formData.set('_method', 'PUT');

        const options = collectOptions('editOptionsEditor');
        if (options.length > 0) formData.set('options', JSON.stringify(options));

        fetch(`/admin/forms/${formId}/fields/${fieldId}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: formData
        }).then(r => r.json()).then(d => {
            if (d.success) { showToast(d.message); setTimeout(() => location.reload(), 800); }
            else showToast(d.message || 'Validation error', true);
        }).catch(() => showToast('Network error', true));
    });

    // Close modal on overlay click
    document.getElementById('editFieldModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });

    // ===== Options helpers =====
    function addOptionRow(editorId) {
        const editor = document.getElementById(editorId);
        const row = document.createElement('div');
        row.className = 'option-row';
        row.innerHTML = `
            <input type="text" class="opt-label" placeholder="Label" value="">
            <input type="text" class="opt-value" placeholder="Value" value="">
            <button type="button" class="remove-opt" onclick="this.parentElement.remove()">&times;</button>
        `;
        editor.insertBefore(row, editor.querySelector('.add-opt-btn'));
    }

    function collectOptions(editorId) {
        const editor = document.getElementById(editorId);
        if (!editor) return [];
        const rows = editor.querySelectorAll('.option-row');
        const opts = [];
        rows.forEach(row => {
            const label = row.querySelector('.opt-label')?.value?.trim();
            const value = row.querySelector('.opt-value')?.value?.trim();
            if (label && value) opts.push({ label, value });
        });
        return opts;
    }

    function renderOptions(editor, options) {
        // Clear existing rows (keep the add button)
        editor.querySelectorAll('.option-row').forEach(r => r.remove());
        options.forEach(opt => {
            const row = document.createElement('div');
            row.className = 'option-row';
            row.innerHTML = `
                <input type="text" class="opt-label" placeholder="Label" value="${opt.label}">
                <input type="text" class="opt-value" placeholder="Value" value="${opt.value}">
                <button type="button" class="remove-opt" onclick="this.parentElement.remove()">&times;</button>
            `;
            editor.insertBefore(row, editor.querySelector('.add-opt-btn'));
        });
    }

    // Show/hide options field based on field type
    document.getElementById('new_field_type').addEventListener('change', function() {
        const g = document.getElementById('newOptionsGroup');
        g.style.display = ['select', 'checkbox', 'radio'].includes(this.value) ? 'block' : 'none';
    });
    document.getElementById('edit_field_type').addEventListener('change', function() {
        const g = document.getElementById('editOptionsGroup');
        g.style.display = ['select', 'checkbox', 'radio'].includes(this.value) ? 'block' : 'none';
    });

    // ===== Publish Toggle =====
    function togglePublish() {
        fetch(`/admin/forms/${formId}/publish`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(r => r.json()).then(d => {
            if (d.success) {
                showToast(d.message);
                const badge = document.getElementById('publishStatus');
                badge.textContent = d.is_published ? 'Published' : 'Unpublished changes';
                badge.className = 'badge-modern ' + (d.is_published ? 'success' : 'warning');
                // Update button text
                const btn = document.querySelector('.btn-publish');
                btn.textContent = d.is_published ? 'Unpublish' : 'Publish Changes';
            }
        });
    }

    // ===== Toast =====
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
function fieldIcon($type) {
    return match($type) {
        'text' => '🅰️',
        'textarea' => '📝',
        'email' => '📧',
        'tel' => '📞',
        'select' => '📋',
        'checkbox' => '✅',
        'radio' => '⚪',
        'date' => '📅',
        'time' => '🕐',
        default => '📄',
    };
}
@endphp
