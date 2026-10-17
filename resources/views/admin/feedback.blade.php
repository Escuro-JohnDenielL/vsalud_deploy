@extends('layouts.admin')

@section('title', 'Feedback')

@push('styles')
    @vite('resources/css/admin/feedback.css')
@endpush

@section('content')
    <div class="vs-page vs-admin-feedback">
        <x-ui.page-header title="Customer Feedback" subtitle="View and manage feedback submitted by patrons." />

        <div class="vs-toolbar">
            <div class="vs-field">
                <label class="vs-label" for="feedbackSearch">Search</label>
                <input type="search" id="feedbackSearch" class="vs-input" placeholder="Name, email or message">
            </div>
            <button type="button" class="admin-btn admin-btn-danger" id="deleteSelectedBtn" disabled>Delete selected</button>
        </div>

        <div class="vs-table-frame">
            <div class="vs-table-wrap" role="region" tabindex="0" aria-label="Customer feedback table">
                <table class="vs-table">
                    <thead>
                        <tr>
                            <th class="vs-cell-select"><input type="checkbox" id="feedbackSelectAll" aria-label="Select all feedback on this page"></th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Message</th>
                            <th>Submitted At</th>
                            <th class="vs-cell-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($feedbacks as $feedback)
                            <tr class="vs-table__row" data-feedback-row>
                                <td class="vs-cell-select"><input type="checkbox" class="feedback-select" value="{{ $feedback->id }}" aria-label="Select feedback from {{ $feedback->name }}"></td>
                                <td>{{ $feedback->name ?? 'None' }}</td>
                                <td class="vs-cell-email"><a href="mailto:{{ $feedback->email }}">{{ $feedback->email }}</a></td>
                                <td class="message-cell">{{ \Illuminate\Support\Str::limit($feedback->message, 120) ?: 'None' }}</td>
                                <td class="vs-cell-nowrap">{{ vs_datetime($feedback->created_at) }}</td>
                                <td class="vs-cell-actions">
                                    <x-ui.row-actions>
                                        <x-slot:view>
                                            <button type="button" class="vs-row-actions__view view-feedback-btn" data-id="{{ $feedback->id }}" data-name="{{ $feedback->name }}" data-email="{{ $feedback->email }}" data-message="{{ $feedback->message }}" data-date="{{ vs_datetime($feedback->created_at) }}">View</button>
                                        </x-slot:view>
                                        <x-slot:menu>
                                            <form action="{{ route('admin.feedback.destroy', $feedback->id) }}" method="POST" id="delete-feedback-form-{{ $feedback->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="vs-overflow__item vs-overflow__item--danger" onclick="confirmDeleteFeedback({{ $feedback->id }})">Delete</button>
                                            </form>
                                        </x-slot:menu>
                                    </x-ui.row-actions>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="6" message="No feedback submitted yet." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $feedbacks->links() }}
    </div>

    {{-- View Modal --}}
    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeModalBtn">&times;</span>
            <h2>Feedback Details</h2>
            <div class="feedback-detail">
                <div class="detail-section">
                    <div class="detail-section-title">Sender</div>
                    <div class="detail-row">
                        <span class="detail-label">Name</span>
                        <span class="detail-value" id="detail-name"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email</span>
                        <span class="detail-value" id="detail-email"></span>
                    </div>
                    <div class="detail-row" style="border:none;">
                        <span class="detail-label">Submitted</span>
                        <span class="detail-value" id="detail-date"></span>
                    </div>
                </div>
                <div class="detail-section">
                    <div class="detail-section-title">Message</div>
                    <div id="detail-message" class="detail-message-block"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirm Delete Modal --}}
    <div id="confirmDeleteModal" class="modal">
        <div class="modal-content modal-sm">
            <span class="close-btn" id="closeConfirmModal">&times;</span>
            <h3>Confirm Delete</h3>
            <p id="confirmDeleteMessage" style="font-size: 15px; margin: 12px 0;">Are you sure you want to delete this feedback?</p>
            <div class="modal-footer">
                <button id="confirmDeleteNo" class="admin-btn admin-btn-ghost">Cancel</button>
                <button id="confirmDeleteYes" class="admin-btn admin-btn-danger">Delete</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('feedbackModal');
        const closeBtn = document.getElementById('closeModalBtn');

        document.querySelectorAll('.view-feedback-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('detail-name').textContent = this.dataset.name;
                const emailEl = document.getElementById('detail-email');
                const email = this.dataset.email || '';
                // Build the mailto link with textContent, never innerHTML, so a
                // crafted address cannot inject markup.
                emailEl.textContent = '';
                const emailLink = document.createElement('a');
                emailLink.href = 'mailto:' + encodeURIComponent(email);
                emailLink.textContent = email;
                emailEl.appendChild(emailLink);
                document.getElementById('detail-date').textContent = this.dataset.date;
                document.getElementById('detail-message').textContent = this.dataset.message;
                window.vsModalPrepareOpen(modal);
                modal.style.display = 'flex';
                modal.classList.add('open');
            });
        });

        const closeFeedbackModal = () => window.vsModalCloseAfterFade(modal, () => {
            modal.style.display = 'none';
            modal.classList.remove('open');
        });
        closeBtn.addEventListener('click', closeFeedbackModal);
        window.addEventListener('click', (e) => { if (e.target === modal) closeFeedbackModal(); });

        // Confirm delete modal logic (shared by single and bulk delete)
        const confirmModal = document.getElementById('confirmDeleteModal');
        const confirmMessage = document.getElementById('confirmDeleteMessage');
        const closeConfirm = document.getElementById('closeConfirmModal');
        const confirmNo = document.getElementById('confirmDeleteNo');
        const confirmYes = document.getElementById('confirmDeleteYes');
        let pendingAction = null;

        function openConfirm(message, action) {
            pendingAction = action;
            if (confirmMessage) confirmMessage.textContent = message;
            window.vsModalPrepareOpen(confirmModal);
            confirmModal.style.display = 'flex';
            confirmModal.classList.add('open');
        }

        function closeConfirmModal(afterClose) {
            window.vsModalCloseAfterFade(confirmModal, () => {
                confirmModal.style.display = 'none';
                confirmModal.classList.remove('open');
                pendingAction = null;
                afterClose?.();
            });
        }

        // Existing single-delete behavior (submits the existing per-item form).
        window.confirmDeleteFeedback = function(id) {
            openConfirm('Are you sure you want to delete this feedback?', function () {
                const form = document.getElementById('delete-feedback-form-' + id);
                if (form) form.submit();
            });
        };

        closeConfirm.addEventListener('click', closeConfirmModal);
        confirmNo.addEventListener('click', closeConfirmModal);
        window.addEventListener('click', (e) => { if (e.target === confirmModal) closeConfirmModal(); });

        confirmYes.addEventListener('click', function() {
            const action = pendingAction;
            closeConfirmModal(() => { if (action) action(); });
        });

        // ---- Search (filters the rows on the current page) ----
        const search = document.getElementById('feedbackSearch');
        const dataRows = () => Array.from(document.querySelectorAll('[data-feedback-row]'));
        if (search) {
            search.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                dataRows().forEach(row => {
                    row.hidden = q !== '' && !row.textContent.toLowerCase().includes(q);
                });
                updateBulkState();
            });
        }

        // ---- Bulk select + "Delete selected" ----
        // One request to the bulk route, carrying only the selected IDs.
        const bulkDeleteUrl = @json(route('admin.feedback.bulk-destroy'));
        const selectAll = document.getElementById('feedbackSelectAll');
        const deleteBtn = document.getElementById('deleteSelectedBtn');
        const boxes = () => Array.from(document.querySelectorAll('.feedback-select'));

        function visibleBoxes() {
            return boxes().filter(b => !b.closest('tr').hidden);
        }

        function updateBulkState() {
            const checked = boxes().filter(b => b.checked).length;
            if (deleteBtn) deleteBtn.disabled = checked === 0;
            if (selectAll) {
                const visible = visibleBoxes();
                selectAll.checked = visible.length > 0 && visible.every(b => b.checked);
            }
        }

        boxes().forEach(b => b.addEventListener('change', updateBulkState));

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                visibleBoxes().forEach(b => { b.checked = selectAll.checked; });
                updateBulkState();
            });
        }

        if (deleteBtn) {
            deleteBtn.addEventListener('click', function () {
                const ids = boxes().filter(b => b.checked).map(b => b.value);
                if (!ids.length) return;

                const label = ids.length === 1 ? 'entry' : 'entries';
                openConfirm('Delete ' + ids.length + ' selected feedback ' + label + '? This cannot be undone.', function () {
                    const tokenInput = document.querySelector('#delete-feedback-form-' + ids[0] + ' input[name="_token"]');
                    const token = tokenInput ? tokenInput.value : '';

                    const body = new URLSearchParams();
                    body.append('_method', 'DELETE');
                    body.append('_token', token);
                    ids.forEach(function (id) { body.append('ids[]', id); });

                    fetch(bulkDeleteUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: body.toString()
                    }).finally(function () {
                        window.location.reload();
                    });
                });
            });
        }

        updateBulkState();
    });
</script>
@endpush
