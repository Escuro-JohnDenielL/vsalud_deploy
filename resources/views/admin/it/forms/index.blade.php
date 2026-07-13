@extends('layouts.admin')

@section('title', 'Form Builder')

@push('styles')
<style>
    .fb-container { padding: 32px 20px 60px; }
    .fb-title { font-family: var(--font-heading); font-size: 28px; color: var(--color-primary); margin: 0 0 6px; }
    .fb-subtitle { color: var(--color-text-muted); margin: 0 0 24px; }
    .form-card { background: var(--color-surface); border-radius: 16px; border: 1px solid var(--color-border); box-shadow: var(--shadow-sm); padding: 22px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; }
    .form-card .info h3 { margin: 0 0 4px; color: #2d2d2d; }
    .form-card .info p { margin: 0; color: var(--color-text-muted); font-size: 14px; }
    /* .badge-published, .badge-draft — now using .badge-modern from app.css */
    .btn-edit-form { background: var(--color-primary); color: #fff; border: none; border-radius: var(--radius-sm); padding: 8px 20px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
    .btn-edit-form:hover { background: #0a5e2f; }
    .empty-state { text-align: center; padding: 60px 20px; color: var(--color-text-muted); }
</style>
@endpush

@section('content')
<div class="fb-container">
    <h1 class="fb-title">Form Builder</h1>
    <p class="fb-subtitle">Manage dynamic form structures. Changes only affect published forms.</p>

    @if (session('success'))
        <div class="alert alert-success" style="padding:12px 16px;border-radius:12px;margin-bottom:20px;background:var(--color-primary-light);border:1px solid var(--color-border);color:var(--color-primary);">{{ session('success') }}</div>
    @endif

    @forelse ($forms as $form)
        <div class="form-card">
            <div class="info">
                <h3>{{ $form->name }}</h3>
                <p>{{ $form->description ?? 'No description' }} &middot; {{ $form->fields_count }} field(s)</p>
                <span class="badge-modern {{ $form->is_published ? 'success' : 'warning' }}">
                    {{ $form->is_published ? 'Published' : 'Draft (unpublished changes)' }}
                </span>
            </div>
            <a href="{{ route('admin.forms.edit', $form) }}" class="admin-btn admin-btn-primary">Edit Fields</a>
        </div>
    @empty
        <div class="empty-state">
            <p>No forms created yet.</p>
        </div>
    @endforelse

    <div style="margin-top: 12px;">
        <a href="{{ route('admin.home') }}" style="color:var(--color-text-muted);font-size:14px;">&larr; Back to Dashboard</a>
    </div>
</div>
@endsection
