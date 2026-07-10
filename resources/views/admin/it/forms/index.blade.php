@extends('layouts.admin')

@section('title', 'Form Builder')

@push('styles')
<style>
    .fb-container { padding: 32px 20px 60px; }
    .fb-title { font-family: Georgia, serif; font-size: 28px; color: #0d7a3e; margin: 0 0 6px; }
    .fb-subtitle { color: #6b7280; margin: 0 0 24px; }
    .form-card { background: #fff; border-radius: 16px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 22px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; }
    .form-card .info h3 { margin: 0 0 4px; color: #2d2d2d; }
    .form-card .info p { margin: 0; color: #6b7280; font-size: 14px; }
    .form-card .info .badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; margin-top: 6px; }
    .badge-published { background: #e8f5e9; color: #2e7d32; }
    .badge-draft { background: #f5f5f5; color: #616161; }
    .btn-edit-form { background: #0d7a3e; color: #fff; border: none; border-radius: 999px; padding: 8px 20px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
    .btn-edit-form:hover { background: #0a5e2f; }
    .empty-state { text-align: center; padding: 60px 20px; color: #6b7280; }
</style>
@endpush

@section('content')
<div class="fb-container">
    <h1 class="fb-title">Form Builder</h1>
    <p class="fb-subtitle">Manage dynamic form structures. Changes only affect published forms.</p>

    @if (session('success'))
        <div class="alert alert-success" style="padding:12px 16px;border-radius:12px;margin-bottom:20px;background:#e8f5e9;border:1px solid #c8e6c9;color:#2e7d32;">{{ session('success') }}</div>
    @endif

    @forelse ($forms as $form)
        <div class="form-card">
            <div class="info">
                <h3>{{ $form->name }}</h3>
                <p>{{ $form->description ?? 'No description' }} &middot; {{ $form->fields_count }} field(s)</p>
                <span class="badge {{ $form->is_published ? 'badge-published' : 'badge-draft' }}">
                    {{ $form->is_published ? 'Published' : 'Draft (unpublished changes)' }}
                </span>
            </div>
            <a href="{{ route('admin.forms.edit', $form) }}" class="btn-edit-form">Edit Fields</a>
        </div>
    @empty
        <div class="empty-state">
            <p>No forms created yet.</p>
        </div>
    @endforelse

    <div style="margin-top: 12px;">
        <a href="{{ route('admin.home') }}" style="color:#6b7280;font-size:14px;">&larr; Back to Dashboard</a>
    </div>
</div>
@endsection
