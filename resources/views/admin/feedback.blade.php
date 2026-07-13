@extends('layouts.admin')

@section('title', 'Feedback')

@push('styles')
    @vite('resources/css/admin/feedback.css')
@endpush

@section('content')
    <div class="feedback-content">
        <section id="content">
            <main>
                <div class="head-title">
                    <h1>Customer Feedback</h1>
                    <p>View feedback submitted by patrons.</p>
                </div>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="table-data">
                    <div class="order">
                        @if($feedbacks->isEmpty())
                            <p class="no-data">No feedback submitted yet.</p>
                        @else
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Message</th>
                                        <th>Submitted At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($feedbacks as $index => $feedback)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $feedback->name }}</td>
                                            <td><a href="mailto:{{ $feedback->email }}">{{ $feedback->email }}</a></td>
                                            <td class="message-cell">{{ Str::limit($feedback->message, 120) }}</td>
                                            <td>{{ $feedback->created_at ? $feedback->created_at->format('M d, Y h:i A') : '-' }}</td>
                                            <td>
                                                <button class="admin-btn admin-btn-primary admin-btn-sm view-feedback-btn" data-id="{{ $feedback->id }}" data-name="{{ $feedback->name }}" data-email="{{ $feedback->email }}" data-message="{{ $feedback->message }}" data-date="{{ $feedback->created_at ? $feedback->created_at->format('M d, Y h:i A') : '-' }}">View</button>
                                                <form action="{{ route('admin.feedback.destroy', $feedback->id) }}" method="POST" class="d-inline" id="delete-feedback-form-{{ $feedback->id }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="admin-btn admin-btn-danger admin-btn-sm" onclick="confirmDeleteFeedback({{ $feedback->id }})">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div style="margin-top: 16px;">
                                {{ $feedbacks->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </main>
        </section>
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
    function escHtml(str) {
        if (!str) return str;
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('feedbackModal');
        const closeBtn = document.getElementById('closeModalBtn');

        document.querySelectorAll('.view-feedback-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('detail-name').textContent = this.dataset.name;
                const emailEl = document.getElementById('detail-email');
                const email = this.dataset.email;
                emailEl.innerHTML = '<a href="mailto:' + encodeURIComponent(email) + '">' + escHtml(email) + '</a>';
                document.getElementById('detail-date').textContent = this.dataset.date;
                document.getElementById('detail-message').textContent = this.dataset.message;
                modal.style.display = 'flex';
                modal.classList.add('open');
            });
        });

        closeBtn.addEventListener('click', () => { modal.style.display = 'none'; modal.classList.remove('open'); });
        window.addEventListener('click', (e) => { if (e.target === modal) { modal.style.display = 'none'; modal.classList.remove('open'); } });

        // Confirm delete modal logic
        const confirmModal = document.getElementById('confirmDeleteModal');
        const closeConfirm = document.getElementById('closeConfirmModal');
        const confirmNo = document.getElementById('confirmDeleteNo');
        const confirmYes = document.getElementById('confirmDeleteYes');
        let pendingFormId = null;

        window.confirmDeleteFeedback = function(id) {
            pendingFormId = 'delete-feedback-form-' + id;
            confirmModal.style.display = 'flex';
        };

        function closeConfirmModal() {
            confirmModal.style.display = 'none';
            pendingFormId = null;
        }

        closeConfirm.addEventListener('click', closeConfirmModal);
        confirmNo.addEventListener('click', closeConfirmModal);
        window.addEventListener('click', (e) => { if (e.target === confirmModal) closeConfirmModal(); });

        confirmYes.addEventListener('click', function() {
            if (pendingFormId) {
                document.getElementById(pendingFormId).submit();
            }
            closeConfirmModal();
        });
    });
</script>
@endpush
