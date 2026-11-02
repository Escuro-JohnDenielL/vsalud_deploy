@props([
    'name' => '',
    'description' => '',
    'price' => 0,
    'imagePath' => '',
    'inclusions' => [],
    'image2Path' => null,
    'image3Path' => null,
    'venues' => [],
])

@php
    // A stored path can point at a photo that was never uploaded (or was deleted),
    // so only keep images that really exist locally. Remote URLs are kept as-is;
    // the modal script drops any of those that fail to load.
    $gallery = collect([$imagePath, $image2Path, $image3Path])
        ->filter(fn ($path) => is_string($path) && \App\Support\PublicImage::exists($path))
        ->map(fn ($path) => \App\Support\PublicImage::url($path))
        ->values()
        ->all();

    $hasPhoto = ! empty($gallery);

    if (! $hasPhoto) {
        $gallery = [asset('images/package_placeholder.svg')];
    }

    $inclusions = collect(is_array($inclusions) ? $inclusions : [])
        ->filter(fn ($item) => is_string($item) && trim($item) !== '')
        ->map(fn ($item) => trim($item))
        ->values()
        ->all();

    // Everything the "View Package" modal needs, handed to JS as JSON on the button.
    $payload = [
        'name' => $name,
        'description' => $description ?: 'No description available.',
        'price' => (float) $price,
        'inclusions' => $inclusions,
        'images' => $gallery,
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
        ])
        ->values()
        ->all();

    $payload['venues'] = $venuePayloads;
@endphp

<div class="package-card">
    <div class="package-card__media">
        <img src="{{ $gallery[0] }}" alt="{{ $name }}" loading="lazy"
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
        <p class="package-card__price">
            <span class="package-card__price-label">Starting at</span>
            <strong>&#8369;{{ number_format($price, 2) }}</strong>
        </p>
        <button type="button" class="package-card__view view-package" data-package='@json($payload)'>
            View Package
        </button>
    </div>
</div>
