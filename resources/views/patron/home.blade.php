@extends('layouts.patron')

@section('title', 'Patron Home')

@push('styles')
    @vite('resources/css/patron/home.css')
@endpush

@section('content')
    <div class="banner-container">
        <div class="banner">
            Welcome to Villa Salud Catering! Your go-to destination for elegant events and hassle-free reservations.
        </div>
    </div>

    <div class="packages">
        @foreach ($packages as $package)
            <x-package-card
                :name="$package->name"
                :description="$package->description"
                :price="$package->price"
                :imagePath="$package->image_path"
            />
        @endforeach

        {{-- Optionally, fallback if no packages exist --}}
        @if($packages->isEmpty())
            <p style="text-align: center;">No packages available at the moment. Please check back later.</p>
        @endif
    </div>
@endsection

@push('scripts')
    @vite('resources/js/patron/home.js')
@endpush
