@extends('layouts.admin')

@section('title', 'Preview - ' . $form->name)

@push('styles')
<style>
    .preview-container { max-width: 720px; margin: 0 auto; padding: 32px 20px 60px; }
    .preview-container h1 { font-family: Georgia, serif; font-size: 28px; color: var(--color-primary); margin: 0 0 4px; }
    .preview-container .sub { color: #587064; margin-bottom: 24px; }
    .preview-form { background: #fff; border-radius: 16px; border: 1px solid rgba(18,59,38,0.1); box-shadow: 0 10px 30px rgba(18,59,38,0.06); padding: 28px; }
    .pf-group { margin-bottom: 18px; }
    .pf-group label { display: block; font-weight: 600; margin-bottom: 4px; color: var(--color-text); font-size: 14px; }
    .pf-group label .req { color: #dc3545; }
    .pf-group input, .pf-group select, .pf-group textarea { width: 100%; border: 1px solid #d7dfd5; border-radius: 10px; padding: 10px 14px; font-size: 14px; box-sizing: border-box; background: #f9fbf9; }
    .pf-group textarea { min-height: 100px; resize: vertical; }
    .pf-group .other-input { margin-top: 6px; display: none; }
    .pf-group .field-note { font-size: 12px; color: #8aa090; margin-top: 4px; }
    .preview-badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; background: #fff3cd; color: #856404; margin-bottom: 16px; }
    .back-link { display: inline-block; margin-top: 20px; color: #587064; font-size: 14px; text-decoration: none; }
    .back-link:hover { text-decoration: underline; }
</style>
@endpush

@section('content')
<div class="preview-container">
    <div class="preview-badge">Preview Mode — Not Submittable</div>
    <h1>{{ $form->name }} — Preview</h1>
    <p class="sub">{{ $form->description ?? '' }}</p>

    <div class="preview-form">
        @forelse ($form->activeFields as $field)
            <div class="pf-group">
                <label for="preview_{{ $field->name }}">
                    {{ $field->label }}
                    @if($field->required) <span class="req">*</span> @endif
                </label>

                @if(in_array($field->field_type, ['text', 'email', 'tel', 'date', 'time']))
                    <input type="{{ $field->field_type === 'tel' ? 'text' : $field->field_type }}"
                           id="preview_{{ $field->name }}"
                           placeholder="{{ $field->placeholder ?? '' }}"
                           {{ $field->required ? 'required' : '' }}
                           readonly>

                @elseif($field->field_type === 'textarea')
                    <textarea id="preview_{{ $field->name }}"
                              placeholder="{{ $field->placeholder ?? '' }}"
                              {{ $field->required ? 'required' : '' }}
                              readonly></textarea>

                @elseif(in_array($field->field_type, ['select']))
                    <select id="preview_{{ $field->name }}" {{ $field->required ? 'required' : '' }} disabled>
                        <option value="">{{ $field->placeholder ?? 'Select...' }}</option>
                        @if($field->options)
                            @foreach($field->options as $opt)
                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                            @endforeach
                        @endif
                        @if($field->has_other_option)
                            <option value="Others">Others</option>
                        @endif
                    </select>
                    @if($field->has_other_option)
                        <input type="text" class="other-input" id="preview_other_{{ $field->name }}" placeholder="Please specify" readonly>
                    @endif

                @elseif(in_array($field->field_type, ['radio']))
                    @if($field->options)
                        @foreach($field->options as $opt)
                            <label style="display:block;font-weight:400;margin:4px 0;">
                                <input type="radio" name="preview_{{ $field->name }}" value="{{ $opt['value'] }}" disabled>
                                {{ $opt['label'] }}
                            </label>
                        @endforeach
                    @endif
                    @if($field->has_other_option)
                        <label style="display:block;font-weight:400;margin:4px 0;">
                            <input type="radio" name="preview_{{ $field->name }}" value="Others" disabled>
                            Others
                        </label>
                        <input type="text" class="other-input" placeholder="Please specify" readonly>
                    @endif

                @elseif(in_array($field->field_type, ['checkbox']))
                    @if($field->options)
                        @foreach($field->options as $opt)
                            <label style="display:block;font-weight:400;margin:4px 0;">
                                <input type="checkbox" value="{{ $opt['value'] }}" disabled>
                                {{ $opt['label'] }}
                            </label>
                        @endforeach
                    @else
                        <label style="display:block;font-weight:400;margin:4px 0;">
                            <input type="checkbox" name="preview_{{ $field->name }}" value="1" disabled>
                            {{ $field->label }}
                        </label>
                    @endif
                @endif

                @if($field->placeholder)
                    <div class="field-note">Placeholder: {{ $field->placeholder }}</div>
                @endif
            </div>
        @empty
            <p style="color:#587064;text-align:center;padding:40px 0;">No active fields in this form.</p>
        @endforelse
    </div>

    <a href="{{ route('admin.forms.edit', $form) }}" class="back-link">&larr; Back to editor</a>
</div>
@endpush
