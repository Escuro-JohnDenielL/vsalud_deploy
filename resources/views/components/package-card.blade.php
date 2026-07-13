@props([
    'name' => '',
    'description' => '',
    'price' => 0,
    'imagePath' => '',
])

<div class="package-card">
    <img src="{{ asset(ltrim($imagePath ?? 'images/default_package.jpg', '/')) }}" alt="{{ $name }}">
    <div class="package-card__body">
        <div class="package-card__title-area">
            <h3 class="package-card__title">{{ $name }}</h3>
        </div>
        <div class="package-card__desc-area">
            <p class="package-card__desc">{{ $description }}</p>
        </div>
        <strong class="package-card__price">₱{{ number_format($price, 2) }}</strong>
    </div>
</div>
