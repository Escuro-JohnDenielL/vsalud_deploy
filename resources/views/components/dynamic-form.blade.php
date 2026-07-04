@props([
    'form',       // App\Models\Form instance (with activeFields loaded)
    'action',     // Form action URL
    'method' => 'POST',  // Form method
    'values' => [],      // Pre-filled values [field_name => value]
    'errors' => null,    // Validation errors
    'submitLabel' => 'Submit',
    'showCalendar' => false,  // Whether to show the calendar sidebar
])

@php
    $fields = $form->activeFields;
@endphp

<form method="{{ $method === 'GET' ? 'GET' : 'POST' }}" action="{{ $action }}" class="dynamic-form">
    @csrf
    @if(!in_array($method, ['GET', 'POST']))
        @method($method)
    @endif

    @foreach ($fields as $field)
        @php
            $fieldName = $field->name;
            $fieldValue = $values[$fieldName] ?? old($fieldName, '');
            $hasError = $errors && $errors->has($fieldName);
            $isRequired = $field->required;
            $isOthers = isset($values[$fieldName]) && $values[$fieldName] === 'Others';
            $otherFieldName = $fieldName . '_other';
            $otherValue = $values[$otherFieldName] ?? old($otherFieldName, '');
        @endphp

        <div class="form-group {{ $hasError ? 'has-error' : '' }}"
             data-field-type="{{ $field->field_type }}"
             data-has-other="{{ $field->has_other_option ? 'true' : 'false' }}">

            <label for="field_{{ $fieldName }}">
                {{ $field->label }}
                @if($isRequired) <span class="required-star">*</span> @endif
            </label>

            {{-- TEXT / EMAIL / DATE / TIME --}}
            @if(in_array($field->field_type, ['text', 'email', 'date', 'time']))
                <input type="{{ $field->field_type }}"
                       id="field_{{ $fieldName }}"
                       name="{{ $fieldName }}"
                       value="{{ $fieldValue }}"
                       placeholder="{{ $field->placeholder ?? '' }}"
                       {{ $isRequired ? 'required' : '' }}
                       class="form-control">

            {{-- TEL (browsers need type=text to avoid number keypad on desktop) --}}
            @elseif($field->field_type === 'tel')
                <input type="tel"
                       id="field_{{ $fieldName }}"
                       name="{{ $fieldName }}"
                       value="{{ $fieldValue }}"
                       placeholder="{{ $field->placeholder ?? '' }}"
                       {{ $isRequired ? 'required' : '' }}
                       class="form-control">

            {{-- TEXTAREA --}}
            @elseif($field->field_type === 'textarea')
                <textarea id="field_{{ $fieldName }}"
                          name="{{ $fieldName }}"
                          placeholder="{{ $field->placeholder ?? '' }}"
                          {{ $isRequired ? 'required' : '' }}
                          class="form-control">{{ $fieldValue }}</textarea>

            {{-- SELECT / DROPDOWN --}}
            @elseif($field->field_type === 'select')
                <select id="field_{{ $fieldName }}"
                        name="{{ $fieldName }}"
                        {{ $isRequired ? 'required' : '' }}
                        class="form-control"
                        data-has-other="{{ $field->has_other_option ? 'true' : 'false' }}">
                    <option value="">{{ $field->placeholder ?? 'Select...' }}</option>
                    @if($field->options)
                        @foreach($field->options as $opt)
                            <option value="{{ $opt['value'] }}" {{ $fieldValue === $opt['value'] ? 'selected' : '' }}>
                                {{ $opt['label'] }}
                            </option>
                        @endforeach
                    @endif
                    @if($field->has_other_option)
                        <option value="Others" {{ $fieldValue === 'Others' ? 'selected' : '' }}>Others</option>
                    @endif
                </select>
                @if($field->has_other_option)
                    <input type="text"
                           class="other-input form-control"
                           id="field_other_{{ $fieldName }}"
                           name="{{ $otherFieldName }}"
                           value="{{ $otherValue }}"
                           placeholder="Please specify"
                           style="display: {{ $isOthers ? 'block' : 'none' }}; margin-top: 6px;">
                @endif

            {{-- RADIO --}}
            @elseif($field->field_type === 'radio')
                <div class="radio-group">
                    @if($field->options)
                        @foreach($field->options as $opt)
                            <label class="radio-label">
                                <input type="radio"
                                       name="{{ $fieldName }}"
                                       value="{{ $opt['value'] }}"
                                       {{ $fieldValue === $opt['value'] ? 'checked' : '' }}
                                       {{ $isRequired ? 'required' : '' }}>
                                {{ $opt['label'] }}
                            </label>
                        @endforeach
                    @endif
                    @if($field->has_other_option)
                        <label class="radio-label">
                            <input type="radio"
                                   name="{{ $fieldName }}"
                                   value="Others"
                                   {{ $fieldValue === 'Others' ? 'checked' : '' }}
                                   {{ $isRequired ? 'required' : '' }}>
                            Others
                        </label>
                        <input type="text"
                               class="other-input form-control"
                               name="{{ $otherFieldName }}"
                               value="{{ $otherValue }}"
                               placeholder="Please specify"
                               style="display: {{ $isOthers ? 'block' : 'none' }}; margin-top: 6px;">
                    @endif
                </div>

            {{-- CHECKBOX --}}
            @elseif($field->field_type === 'checkbox')
                <div class="checkbox-group">
                    @if($field->options)
                        @foreach($field->options as $opt)
                            <label class="checkbox-label">
                                <input type="checkbox"
                                       name="{{ $fieldName }}[]"
                                       value="{{ $opt['value'] }}"
                                       {{ in_array($opt['value'], (array)($fieldValue)) ? 'checked' : '' }}>
                                {{ $opt['label'] }}
                            </label>
                        @endforeach
                    @else
                        <label class="checkbox-label">
                            <input type="checkbox"
                                   name="{{ $fieldName }}"
                                   value="1"
                                   {{ $fieldValue ? 'checked' : '' }}>
                            {{ $field->label }}
                        </label>
                    @endif
                </div>
            @endif

            {{-- Validation error --}}
            @if($hasError)
                <div class="field-error">{{ $errors->first($fieldName) }}</div>
            @endif
        </div>
    @endforeach

    <div class="form-group">
        <button type="submit" class="btn-submit">{{ $submitLabel }}</button>
    </div>
</form>

{{-- Inline styles --}}
<style>
    .dynamic-form .form-group { margin-bottom: 18px; }
    .dynamic-form .form-group label { display: block; font-weight: 600; margin-bottom: 4px; color: #22332a; font-size: 14px; }
    .dynamic-form .form-group .required-star { color: #dc3545; }
    .dynamic-form .form-control { width: 100%; border: 1px solid #d7dfd5; border-radius: 10px; padding: 10px 14px; font-size: 14px; box-sizing: border-box; transition: border-color 0.15s; }
    .dynamic-form .form-control:focus { border-color: #165c34; outline: none; box-shadow: 0 0 0 2px rgba(22,92,52,0.1); }
    .dynamic-form textarea.form-control { min-height: 100px; resize: vertical; }
    .dynamic-form .form-group.has-error .form-control { border-color: #dc3545; }
    .dynamic-form .field-error { color: #dc3545; font-size: 13px; margin-top: 4px; }
    .dynamic-form .radio-group, .dynamic-form .checkbox-group { display: flex; flex-direction: column; gap: 6px; }
    .dynamic-form .radio-label, .dynamic-form .checkbox-label { font-weight: 400; font-size: 14px; display: flex; align-items: center; gap: 6px; cursor: pointer; }
    .dynamic-form .btn-submit { background: #165c34; color: #fff; border: none; border-radius: 999px; padding: 12px 32px; font-weight: 600; font-size: 15px; cursor: pointer; transition: background 0.15s; }
    .dynamic-form .btn-submit:hover { background: #0f4728; }
</style>

{{-- JS for "Others" toggle --}}
@once
    @push('scripts')
    <script>
    document.addEventListener('change', function(e) {
        // Handle select "Others"
        if (e.target.matches('select[data-has-other="true"]')) {
            const otherInput = e.target.closest('.form-group').querySelector('.other-input');
            if (otherInput) {
                otherInput.style.display = e.target.value === 'Others' ? 'block' : 'none';
                otherInput.required = e.target.value === 'Others';
            }
        }
        // Handle radio "Others"
        if (e.target.matches('input[type="radio"]') && e.target.value === 'Others') {
            const otherInput = e.target.closest('.form-group').querySelector('.other-input');
            if (otherInput) {
                otherInput.style.display = 'block';
                otherInput.required = true;
            }
        }
        if (e.target.matches('input[type="radio"]') && e.target.value !== 'Others') {
            const formGroup = e.target.closest('.form-group');
            const otherInput = formGroup?.querySelector('.other-input');
            if (otherInput && !formGroup.querySelector('input[type="radio"]:checked')?.value === 'Others') {
                // Check if any other radio with "Others" is checked
                const othersChecked = formGroup.querySelector('input[type="radio"][value="Others"]:checked');
                if (!othersChecked) {
                    otherInput.style.display = 'none';
                    otherInput.required = false;
                }
            }
        }
    });
    </script>
    @endpush
@endonce
