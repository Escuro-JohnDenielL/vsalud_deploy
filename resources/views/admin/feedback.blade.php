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
                        <div class="head">
                            <h3>All Feedback</h3>
                        </div>

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
                                                <button class="view-feedback-btn" data-id="{{ $feedback->id }}" data-name="{{ $feedback->name }}" data-email="{{ $feedback->email }}" data-message="{{ $feedback->message }}" data-date="{{ $feedback->created_at ? $feedback->created_at->format('M d, Y h:i A') : '-' }}">View</button>
                                                <form action="{{ route('admin.feedback.destroy', $feedback->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this feedback?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="delete-btn">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
                <p><strong>Name:</strong> <span id="detail-name"></span></p>
                <p><strong>Email:</strong> <span id="detail-email"></span></p>
                <p><strong>Submitted:</strong> <span id="detail-date"></span></p>
                <p><strong>Message:</strong></p>
                <div id="detail-message" class="detail-message"></div>
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
                document.getElementById('detail-email').textContent = this.dataset.email;
                document.getElementById('detail-date').textContent = this.dataset.date;
                document.getElementById('detail-message').textContent = this.dataset.message;
                modal.style.display = 'flex';
            });
        });

        closeBtn.addEventListener('click', () => modal.style.display = 'none');
        window.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });
    });
</script>
@endpush
