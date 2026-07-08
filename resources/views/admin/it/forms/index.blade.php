@extends('layouts.admin')

@section('title', 'Form Builder')

@push('styles')
<style>
    .fb-container { padding: 32px 20px 60px; }
    .fb-title { font-family: Georgia, serif; font-size: 30px; color: #123b26; margin: 0 0 6px; }
    .fb-subtitle { color: #587064; margin: 0 0 24px; }
    .form-card { background: #fff; border-radius: 16px; border: 1px solid rgba(18,59,38,0.1); box-shadow: 0 10px 30px rgba(18,59,38,0.06); padding: 22px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; }
    .form-card .info h3 { margin: 0 0 4px; color: #123b26; }
    .form-card .info p { margin: 0; color: #587064; font-size: 14px; }
    .form-card .info .badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; margin-top: 6px; }
    .badge-published { background: #d4edda; color: #155724; }
    .badge-draft { background: #fff3cd; color: #856404; }
    .btn-edit-form { background: #165c34; color: #fff; border: none; border-radius: 999px; padding: 8px 20px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
    .btn-edit-form:hover { background: #0f4728; }
    .empty-state { text-align: center; padding: 60px 20px; color: #587064; }
</style>
@endpush

@section('content')
<div class="fb-container">
    <h1 class="fb-title">Form Builder</h1>
    <p class="fb-subtitle">Manage dynamic form structures. Changes only affect published forms.</p>

    @if (session('success'))
        <div class="alert alert-success" style="padding:12px 16px;border-radius:12px;margin-bottom:20px;background:#f0f8f2;border:1px solid #d4e7d8;color:#155724;">{{ session('success') }}</div>
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
        <a href="{{ route('admin.home') }}" style="color:#587064;font-size:14px;">&larr; Back to Dashboard</a>
    </div>
</div>
@endsection
