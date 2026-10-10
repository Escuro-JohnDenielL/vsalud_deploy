{{--
    Shared status select (same height/colour variants as badges).
    Renders a <select> that keeps any extra attributes (name, data-*, class)
    you pass in. Provide $options as value => label and $variants as
    value => variant so JS can colour the closed control.

    Usage:
        <x-ui.status-select name="status" :selected="$row->status"
            :options="['Pending' => 'Pending', 'Completed' => 'Completed']"
            :variants="['Pending' => 'warning', 'Completed' => 'success']"
            data-index="{{ $index }}" />
--}}
@props(['options' => [], 'selected' => null, 'placeholder' => null, 'variants' => []])

<select {{ $attributes->merge(['class' => 'vs-status-select']) }}
        data-vs-status-select
        data-vs-variants="{{ json_encode($variants) }}"
        data-vs-value="{{ $selected }}">
    @if ($placeholder)
        <option value="" @if ($selected === null || $selected === '') selected hidden @endif>{{ $placeholder }}</option>
    @endif
    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @if ((string) $selected === (string) $value) selected @endif>{{ $label }}</option>
    @endforeach
</select>
