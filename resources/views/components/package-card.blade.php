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

    if (empty($gallery)) {
        $gallery = [asset('images/default_package.jpg')];
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
    <img src="{{ $gallery[0] }}" alt="{{ $name }}">
    <div class="package-card__body">
        <div class="package-card__title-area">
            <h3 class="package-card__title">{{ $name }}</h3>
        </div>
        <div class="package-card__desc-area">
            <p class="package-card__desc">{{ $description }}</p>
        </div>
        <strong class="package-card__price">₱{{ number_format($price, 2) }}</strong>
        <button type="button" class="package-card__view view-package" data-package='@json($payload)'>
            View Package
        </button>
    </div>
</div>
