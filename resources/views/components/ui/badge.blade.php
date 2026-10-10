{{--
    Shared status badge.
    Variants: success | warning | danger | neutral | info
    Usage: <x-ui.badge variant="success">Approved</x-ui.badge>
--}}
@props(['variant' => 'neutral'])

<span {{ $attributes->merge(['class' => 'vs-badge vs-badge--' . $variant]) }}>{{ $slot }}</span>
