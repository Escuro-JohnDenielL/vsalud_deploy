@extends('layouts.admin')

@section('title', 'Packages')

@section('content')
<div class="vs-page vs-admin-dashboard">

<x-ui.page-header
    title="Packages"
    subtitle="Create and manage the packages shown to patrons." />

{{-- Package Cards --}}
<section class="packages-grid vs-admin-dashboard__packages-grid" id="packagesContainer">
    @forelse($packages as $package)
    <div class="package-card" data-package="{{ $package->name }}" data-id="{{ $package->id }}">
        <div class="package-card__media">
            <img src="{{ asset(ltrim($package->image_path ?: 'images/package_placeholder.svg', '/')) }}" alt="{{ $package->name }}" data-package-image class="{{ $package->image_path ? '' : 'is-fallback' }}">
        </div>
        <div class="package-content">
            <h3>{{ $package->name }}</h3>
            <div class="package-details"></div>
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
</div>

{{-- View Package Modal --}}
<div class="modal fade" id="packageModal" tabindex="-1" aria-labelledby="packageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="packageModalLabel">Package Details</h5>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <img id="modalMainImage" src="" alt="Package Image" class="img-fluid rounded mb-3">
                        <div class="row">
                            <div class="col-4">
                                <img id="modalImg1" src="" alt="Image 1" class="img-fluid rounded thumbnail-img">
                            </div>
                            <div class="col-4">
                                <img id="modalImg2" src="" alt="Image 2" class="img-fluid rounded thumbnail-img">
                            </div>
                            <div class="col-4">
                                <img id="modalImg3" src="" alt="Image 3" class="img-fluid rounded thumbnail-img">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h4 id="modalTitle">Package Name</h4>
                        <p id="modalDescription">Package description will appear here...</p>
                        <h5 class="text-success" id="modalPrice">₱0.00</h5>
                        <div class="package-inclusions mt-3">
                            <h6>Package Inclusions:</h6>
                            <ul id="modalInclusions"></ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="admin-btn admin-btn-ghost" data-bs-dismiss="modal">Close</button>
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
                    <div class="vs-admin-dashboard__edit-details">
                        <div class="mb-3">
                            <label class="form-label">Package Name</label>
                            <input type="text" id="editPackageName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea id="editPackageDescription" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Package Inclusions</label>
                            <div id="editInclusionsContainer"></div>
                            <button type="button" id="editAddInclusion" class="admin-btn admin-btn-ghost admin-btn-sm">+ Add Inclusion</button>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" id="editPackagePrice" class="form-control" step="0.01" required>
                        </div>
                    </div>
                    <div class="vs-admin-dashboard__edit-media">
                        <div class="mb-3">
                            <label class="form-label">Current Main Image</label>
                            <img id="currentMainImage" src="" alt="Current Image" class="img-fluid rounded mb-2 vs-admin-dashboard__edit-image">
                            <input type="file" id="editPackageImage" class="form-control" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image 2 (Optional)</label>
                            <img id="currentImage2" src="" alt="Current Image 2" class="img-fluid rounded mb-2 vs-admin-dashboard__edit-image vs-admin-dashboard__edit-image--optional">
                            <input type="file" id="editPackageImage2" class="form-control" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image 3 (Optional)</label>
                            <img id="currentImage3" src="" alt="Current Image 3" class="img-fluid rounded mb-2 vs-admin-dashboard__edit-image vs-admin-dashboard__edit-image--optional">
                            <input type="file" id="editPackageImage3" class="form-control" accept="image/*">
                        </div>
                        <p class="vs-admin-dashboard__field-note">Use a landscape photo without text, at least 1200px wide.</p>
                    </div>
                    <p class="text-muted mb-0 vs-admin-dashboard__edit-note"><small>Leave a photo field empty to keep the package's current photo. Only the slots where you pick a new file are replaced.</small></p>
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Package Name</label>
                            <input type="text" name="name" id="newPackageName" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" id="newPackagePrice" class="form-control" step="0.01" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="newPackageDescription" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="vs-admin-dashboard__add-media">
                        <div class="mb-3">
                            <label class="form-label" for="newPackageImage">Package Image</label>
                            <input type="file" name="image" id="newPackageImage" class="form-control" accept="image/*" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="newPackageImage2">Image 2 (Optional)</label>
                            <input type="file" name="image2" id="newPackageImage2" class="form-control" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="newPackageImage3">Image 3 (Optional)</label>
                            <input type="file" name="image3" id="newPackageImage3" class="form-control" accept="image/*">
                        </div>
                        <p class="vs-admin-dashboard__field-note">Use a landscape photo without text, at least 1200px wide.</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Package Inclusions</label>
                        <div id="inclusionsContainer">
                            <div class="vs-inclusion-row mb-2">
                                <input type="text" name="inclusions[]" class="form-control" placeholder="Enter inclusion">
                                <button type="button" class="admin-btn admin-btn-danger admin-btn-sm remove-inclusion">Remove</button>
                            </div>
                        </div>
                        <button type="button" id="addInclusion" class="admin-btn admin-btn-ghost admin-btn-sm">+ Add Inclusion</button>
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
