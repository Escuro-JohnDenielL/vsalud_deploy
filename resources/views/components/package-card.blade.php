@props([
    'name' => '',
    'description' => '',
    'imagePath' => '',
    'inclusions' => [],
    'venues' => [],
])

@php
    // A stored path can point at a photo that was never uploaded (or was deleted),
    // so only keep the image when it really exists locally. Remote URLs are kept
    // as-is; the modal script drops any of those that fail to load.
    $photo = is_string($imagePath) && \App\Support\PublicImage::exists($imagePath)
        ? \App\Support\PublicImage::url($imagePath)
        : null;

    $hasPhoto = $photo !== null;
    $photo = $photo ?? asset('images/package_placeholder.svg');

    $inclusions = collect(is_array($inclusions) ? $inclusions : [])
        ->filter(fn ($item) => is_string($item) && trim($item) !== '')
        ->map(fn ($item) => trim($item))
        ->values()
        ->all();

    // Everything the "View Package" modal needs, handed to JS as JSON on the button.
    $payload = [
        'name' => $name,
        'description' => $description ?: 'No description available.',
        'inclusions' => $inclusions,
        'image' => $photo,
    ];

    // Venues behind this package. The dialog only needs names/capacity for the
    // "Venue" row; the photos are shown by the single page-level villa gallery,
    // so they are not duplicated into every card's JSON.
    $venuePayloads = collect($venues ?? [])
        ->filter(fn ($venue) => $venue && $venue->name)
        ->map(fn ($venue) => [
            'id' => $venue->id,
            'name' => $venue->name,
            'capacity' => $venue->capacity,
            'rates' => collect($venue->rates ?? [])
                ->map(fn ($rate) => [
                    'label' => $rate->label,
                    'period' => $rate->period,
                    'price' => (float) $rate->price,
                ])
                ->values()
                ->all(),
        ])
        ->values()
        ->all();

    $payload['venues'] = $venuePayloads;

    $allPrices = collect($venuePayloads)
        ->flatMap(fn ($venue) => collect($venue['rates'])->pluck('price'))
        ->map(fn ($price) => (float) $price)
        ->values();
    $minimumPrice = $allPrices->isNotEmpty() ? $allPrices->min() : null;
    $maximumPrice = $allPrices->isNotEmpty() ? $allPrices->max() : null;
    $unpricedVenues = collect($venuePayloads)
        ->filter(fn ($venue) => empty($venue['rates']))
        ->pluck('name')
        ->values()
        ->all();

    $formatPrice = fn ($price) => '₱' . number_format(
        (float) $price,
        floor((float) $price) === (float) $price ? 0 : 2
    );
    $priceRange = $minimumPrice === null
        ? null
        : ($minimumPrice === $maximumPrice
            ? $formatPrice($minimumPrice)
            : $formatPrice($minimumPrice) . '–' . $formatPrice($maximumPrice));
@endphp

<div class="package-card">
    <div class="package-card__media">
        <img src="{{ $photo }}" alt="{{ $name }}" loading="lazy"
             @if (! $hasPhoto) class="is-fallback" @endif
             onerror="this.onerror=null;this.src='{{ asset('images/package_placeholder.svg') }}';this.classList.add('is-fallback');">
    </div>
    <div class="package-card__body">
        <div class="package-card__title-area">
            <h3 class="package-card__title">{{ $name }}</h3>
        </div>
        <div class="package-card__desc-area">
            <p class="package-card__desc">{{ $description }}</p>
        </div>
        @if (! empty($inclusions))
            <ul class="package-card__inclusions">
                @foreach (array_slice($inclusions, 0, 3) as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @endif
        <div class="package-card__pricing">
            <span class="package-card__pricing-label">Venue rental estimate</span>
            @if ($priceRange !== null)
                <strong>{{ $priceRange }}</strong>
                @if (! empty($unpricedVenues))
                    <span class="package-card__pricing-note">Contact us for rates at {{ implode(', ', $unpricedVenues) }}.</span>
                @endif
            @else
                <strong class="package-card__pricing-unavailable">Contact us for venue pricing</strong>
            @endif
        </div>
        <button type="button" class="package-card__view view-package" data-package='@json($payload)'>
            View Package
        </button>
    </div>
</div>
