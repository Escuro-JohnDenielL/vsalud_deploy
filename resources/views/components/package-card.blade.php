@props([
    'name' => '',
    'description' => '',
    'price' => 0,
    'imagePath' => '',
    'inclusions' => [],
    'image2Path' => null,
    'image3Path' => null,
])

@php
    // Build the gallery from whichever image paths this package actually has.
    // Stored paths look like "/images/wedding_package.jpg" or "/storage/packages/xxx.jpg".
    $gallery = collect([$imagePath, $image2Path, $image3Path])
        ->filter(fn ($path) => is_string($path) && trim($path) !== '')
        ->map(fn ($path) => asset(ltrim(trim($path), '/')))
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
