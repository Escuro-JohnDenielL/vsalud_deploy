@extends('layouts.admin')

@section('title', 'Packages')

@section('content')
<div class="vs-page vs-admin-dashboard">

<x-ui.page-header
    title="Packages"
    subtitle="Create and manage the packages shown to patrons.">
    <x-slot:action>
        <x-ui.page-info id="packagesInfo" title="How packages work">
            <ol>
                <li>Select Add New Package to create a package.</li>
                <li>Enter the package name, description, photo, and inclusions. Venue rental pricing is managed separately on the Venues page.</li>
                <li>Upload the main photo. Two more photos are optional.</li>
                <li>Photos must be JPEG, PNG, or GIF, and no larger than 2 MB each.</li>
                <li>Pick the venue this package is held in. Its photo gallery is managed on the Venues page and is shown to patrons from the package card.</li>
                <li>Add inclusions, reorder them with the arrow controls, or remove a row.</li>
                <li>Save the package to show it on the patron Packages page.</li>
                <li>Edit keeps the current photo when you leave a photo field empty.</li>
                <li>Delete removes the package and its photo files. It cannot be undone from this page.</li>
            </ol>
        </x-ui.page-info>
    </x-slot:action>
</x-ui.page-header>

@if ($packages->count() > 1)
<p class="packages-swipe-hint">
    <span aria-hidden="true">&larr;</span>
    Swipe right to see more packages
    <span aria-hidden="true">&rarr;</span>
</p>
@endif

{{-- Package Cards --}}
<section class="packages-grid vs-admin-dashboard__packages-grid" id="packagesContainer">
    @forelse($packages as $package)
    <div class="package-card" data-package="{{ $package->name }}" data-id="{{ $package->id }}">
        <div class="package-card__media">
            <img src="{{ asset(ltrim($package->image_path ?: 'images/package_placeholder.svg', '/')) }}" alt="{{ $package->name }}" data-package-image class="{{ $package->image_path ? '' : 'is-fallback' }}">
        </div>
        <div class="package-content">
            <h3>{{ $package->name }}</h3>
            <div class="package-details">
                <p class="package-description">{{ $package->description ?: 'No description available' }}</p>
            </div>
            <div class="vs-admin-dashboard__card-actions">
                <x-ui.row-actions label="Package actions">
                    <x-slot:view>
                        <button type="button" class="vs-row-actions__view view-package">View Package</button>
                    </x-slot:view>
                    <x-slot:menu>
                        <button type="button" class="vs-overflow__item edit-btn">Edit</button>
                        <div class="vs-overflow__separator"></div>
                        <button type="button" class="vs-overflow__item vs-overflow__item--danger delete-btn">Delete</button>
                    </x-slot:menu>
                </x-ui.row-actions>
            </div>
        </div>
    </div>
    @empty
    <div class="vs-admin-dashboard__empty">
        <p class="vs-empty__msg">No packages yet. Use "Add New Package" below to create the first one.</p>
    </div>
    @endforelse
</section>

{{-- Add New Package Button (below the packages, centered) --}}
<div class="vs-admin-dashboard__add-package">
    <button type="button" id="addPackageBtn" class="admin-btn admin-btn-primary" data-bs-toggle="modal" data-bs-target="#addPackageModal">+ Add New Package</button>
    <a href="{{ route('admin.venues') }}" class="admin-btn admin-btn-ghost">Manage venue photos</a>
</div>

{{-- View Package Modal --}}
<div id="packageModal" class="pkg-modal pkg-modal--package" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="pkg-modal__dialog">
        <button type="button" class="pkg-modal__close close-btn" id="modalPackageClose" aria-label="Close">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true" focusable="false">
                <path d="m6 6 12 12M18 6 6 18"/>
            </svg>
        </button>
        <div class="pkg-modal__body">
            <div class="pkg-modal__media">
                <img id="modalMainImage" class="pkg-modal__main-image" src="" alt="Package Image" data-package-image>
            </div>
            <div class="pkg-modal__info">
                <h2 class="pkg-modal__title" id="modalTitle">Package Name</h2>
                <p id="modalDescription" class="pkg-modal__desc">Package description will appear here...</p>
                <section class="pkg-modal__inclusions" id="modalInclusionsSection" aria-labelledby="modalInclusionsTitle">
                    <h3 id="modalInclusionsTitle">Package inclusions</h3>
                    <ul id="modalInclusions"></ul>
                </section>
                <section class="pkg-modal__pricing" aria-labelledby="modalPricingTitle">
                    <div class="pkg-modal__estimate">
                        <h3 id="modalPricingTitle">Venue rental estimate</h3>
                        <p id="modalPriceRange" class="pkg-modal__pricing-range"></p>
                        <p class="pkg-modal__pricing-disclaimer">Venue rental only. The final quote is confirmed by Villa Salud.</p>
                    </div>
                    <button type="button" class="pkg-modal__rates-trigger admin-btn" id="modalRatesOpen" aria-haspopup="dialog" aria-controls="packageRatesModal" hidden>
                        <span>View venue rates</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                            <path d="M5 12h14m-6-6 6 6-6 6"/>
                        </svg>
                    </button>
                </section>
            </div>
        </div>
    </div>
</div>

<div id="packageRatesModal" class="pkg-modal pkg-modal--rates" role="dialog" aria-modal="true" aria-labelledby="packageRatesHeading" hidden>
    <div class="pkg-modal__dialog pkg-modal__rates-dialog">
        <div class="pkg-modal__rates-header">
            <h2 class="pkg-modal__venue-heading" id="packageRatesHeading">Venue rates</h2>
            <button type="button" class="pkg-modal__rates-close close-btn" id="modalVenueRatesClose" aria-label="Close venue rates">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true" focusable="false">
                    <path d="m6 6 12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>
        <div class="pkg-modal__venue-picker">
            <label class="pkg-modal__venue-picker-label" for="modalVenueSelect">Choose a venue</label>
            <select class="pkg-modal__venue-select" id="modalVenueSelect"></select>
            <ul id="modalVenueRates" class="pkg-modal__admin-rates" aria-live="polite"></ul>
        </div>
        <footer class="pkg-modal__rates-footer">
            <button type="button" class="pkg-modal__cta pkg-modal__cta--secondary admin-btn admin-btn-ghost" id="modalVenueRatesDone">Done</button>
        </footer>
    </div>
</div>
        </div>
    </div>
</div>

{{-- Edit Package Modal --}}
<div class="modal fade vs-admin-dashboard__edit-modal" id="editPackageModal" tabindex="-1" aria-labelledby="editPackageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPackageModalLabel">Edit Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPackageForm">
                <div class="modal-body">
                    <input type="hidden" id="editPackageId">
                    <div class="vs-package-editor-layout">
                        <div class="vs-package-editor__fields">
                            <div class="vs-admin-dashboard__edit-details">
                                <div class="mb-3">
                                    <label class="form-label" for="editPackageName">Package Name</label>
                                    <input type="text" id="editPackageName" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="editPackageDescription">Description</label>
                                    <textarea id="editPackageDescription" class="form-control" rows="5"></textarea>
                                </div>
                            </div>
                            <div class="vs-admin-dashboard__edit-media">
                                <div class="mb-3">
                                    <label class="form-label">Current Main Image</label>
                                    <img id="currentMainImage" src="" alt="Current Image" class="img-fluid rounded mb-2 vs-admin-dashboard__edit-image">
                                    <input type="file" id="editPackageImage" class="form-control" accept="image/*">
                                </div>
                                <p class="vs-admin-dashboard__field-note">Use a landscape photo without text, at least 1200px wide.</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Venues used by this package</label>
                                <div class="vs-venue-checks" id="editPackageVenues">
                                    @forelse ($venues as $venue)
                                        <label class="vs-venue-checks__item">
                                            <input type="checkbox" class="edit-package-venue" value="{{ $venue->id }}">
                                            <span>{{ $venue->name }}</span>
                                        </label>
                                    @empty
                                        <p class="vs-admin-dashboard__field-note mb-0">No venues yet. <a href="{{ route('admin.venues') }}">Create one first</a>.</p>
                                    @endforelse
                                </div>
                                <p class="vs-admin-dashboard__field-note">Patrons get a "View venue pictures" button that lets them switch between these venues. <a href="{{ route('admin.venues') }}">Upload the villa photos on the Venues page</a>.</p>
                            </div>
                            <p class="text-muted mb-0 vs-admin-dashboard__edit-note"><small>Leave the photo field empty to keep the package's current photo.</small></p>
                        </div>
                        <section class="vs-package-inclusions-editor" aria-labelledby="editInclusionsTitle">
                            <label class="form-label" id="editInclusionsTitle">Package inclusions (0)</label>
                            <ul id="editInclusionsContainer" class="vs-inclusion-list" role="list"></ul>
                            <button type="button" id="editAddInclusion" class="admin-btn admin-btn-ghost vs-inclusion-add">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                                <span>Add inclusion</span>
                            </button>
                        </section>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="admin-btn admin-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="admin-btn admin-btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Package Modal --}}
<div class="modal fade vs-admin-dashboard__add-modal" id="addPackageModal" tabindex="-1" aria-labelledby="addPackageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPackageModalLabel">Add New Package</h5>
            </div>
            <form id="addPackageForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="vs-package-editor-layout">
                        <div class="vs-package-editor__fields">
                            <div class="mb-3">
                                <label class="form-label" for="newPackageName">Package Name</label>
                                <input type="text" name="name" id="newPackageName" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="newPackageDescription">Description</label>
                                <textarea name="description" id="newPackageDescription" class="form-control" rows="5"></textarea>
                            </div>
                            <div class="vs-admin-dashboard__add-media">
                                <div class="mb-3">
                                    <label class="form-label" for="newPackageImage">Package Image</label>
                                    <input type="file" name="image" id="newPackageImage" class="form-control" accept="image/*" required>
                                </div>
                                <p class="vs-admin-dashboard__field-note">Use a landscape photo without text, at least 1200px wide.</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Venues used by this package</label>
                                <div class="vs-venue-checks" id="newPackageVenues">
                                    @forelse ($venues as $venue)
                                        <label class="vs-venue-checks__item">
                                            <input type="checkbox" class="new-package-venue" value="{{ $venue->id }}">
                                            <span>{{ $venue->name }}</span>
                                        </label>
                                    @empty
                                        <p class="vs-admin-dashboard__field-note mb-0">No venues yet. <a href="{{ route('admin.venues') }}">Create one first</a>.</p>
                                    @endforelse
                                </div>
                                <p class="vs-admin-dashboard__field-note">Tick every venue this package can be held in. Photos are uploaded once per venue on the Venues page. They are shared, not copied per package.</p>
                            </div>
                        </div>
                        <section class="vs-package-inclusions-editor" aria-labelledby="addInclusionsTitle">
                            <label class="form-label" id="addInclusionsTitle">Package inclusions (0)</label>
                            <ul id="inclusionsContainer" class="vs-inclusion-list" role="list"></ul>
                            <button type="button" id="addInclusion" class="admin-btn admin-btn-ghost vs-inclusion-add">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                                <span>Add inclusion</span>
                            </button>
                        </section>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="admin-btn admin-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="admin-btn admin-btn-primary">Add Package</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Confirm Delete Modal --}}
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteTitle">Confirm Delete</h5>
            </div>
            <div class="modal-body">
                <p id="confirmDeleteMessage">Are you sure you want to delete this package?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="admin-btn admin-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="admin-btn admin-btn-danger" id="confirmDeleteYes">Delete</button>
            </div>
        </div>
    </div>
</div>

{{-- Success Modal --}}
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="successModalLabel">Success</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                    <p id="successMessage">Operation completed successfully!</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="admin-btn admin-btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

</div>
@endsection

@push('scripts')
@vite('resources/js/admin/home.js')
@endpush

@push('styles')
@vite('resources/css/admin/home.css')
@endpush
