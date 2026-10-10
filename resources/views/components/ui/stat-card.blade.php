{{--
    Shared stat card (white card, large number, small muted label).
    Optional coloured dot next to the label: success | warning | danger | info
    Usage: <x-ui.stat-card :value="$count" label="Active Admins" />
--}}
@props(['value', 'label', 'dot' => null, 'stat' => null])

<div class="vs-stat-card">
    <strong class="vs-stat-card__value" @if ($stat) data-stat="{{ $stat }}" @endif>{{ $value }}</strong>
    <span class="vs-stat-card__label">
        @if ($dot)
            <span class="vs-stat-card__dot vs-stat-card__dot--{{ $dot }}" aria-hidden="true"></span>
        @endif
        {{ $label }}
    </span>
</div>
