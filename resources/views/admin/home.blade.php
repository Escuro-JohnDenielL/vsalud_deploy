@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')

{{-- Promotional Banner --}}
<section class="banner-container">
    <div class="banner text-center text-white fw-bold py-4">
        ✨ Big events, bigger savings! Grab our special deals for weddings, birthdays, and more! ✨
    </div>
</section>


{{-- Package Cards --}}
<section class="packages-grid" id="packagesContainer">
    @forelse($packages as $package)
    <div class="package-card" data-package="{{ $package->name }}" data-id="{{ $package->id }}">
        <img src="{{ asset(ltrim($package->image_path ?? 'images/default_package.jpg', '/')) }}" alt="{{ $package->name }}">
        <div class="package-content">
            <h3>{{ $package->name }}</h3>
            <div class="package-details"></div>
            <div class="buttons-row">
                <button class="btn btn-success btn-sm view-package" data-bs-toggle="modal" data-bs-target="#packageModal">View Package</button>
                <button class="btn btn-outline-primary btn-sm edit-btn" data-bs-toggle="modal" data-bs-target="#editPackageModal">Edit</button>
                <button class="btn btn-danger btn-sm delete-btn">Delete</button>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5">
        <p class="text-muted">No packages found. Click "Add New Package" to create one.</p>
    </div>
    @endforelse
</section>

{{-- Add New Package Button --}}
<div class="add-package-container text-center mt-4 mb-4">
    <button id="addPackageBtn" class="add-package-btn btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#addPackageModal">+ Add New Package</button>
</div>

{{-- View Package Modal --}}
<div class="modal fade" id="packageModal" tabindex="-1" aria-labelledby="packageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="packageModalLabel">Package Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                            <ul id="modalInclusions">
                                <li>Professional coordination</li>
                                <li>Event setup and decoration</li>
                                <li>Photography coverage</li>
                                <li>Catering service</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Package Modal --}}
<div class="modal fade" id="editPackageModal" tabindex="-1" aria-labelledby="editPackageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPackageModalLabel">Edit Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPackageForm">
                <div class="modal-body">
                    <input type="hidden" id="editPackageId">
                    <div class="mb-3">
                        <label class="form-label">Package Name</label>
                        <input type="text" id="editPackageName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea id="editPackageDescription" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="number" id="editPackagePrice" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Main Image</label>
                        <img id="currentMainImage" src="" alt="Current Image" class="img-fluid rounded mb-2" style="max-height: 150px;">
                        <input type="file" id="editPackageImage" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Package Modal --}}
<div class="modal fade" id="addPackageModal" tabindex="-1" aria-labelledby="addPackageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPackageModalLabel">Add New Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addPackageForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Package Name</label>
                        <input type="text" name="name" id="newPackageName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="newPackageDescription" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="number" name="price" id="newPackagePrice" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Package Image</label>
                        <input type="file" name="image" id="newPackageImage" class="form-control" accept="image/*" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Package Image 2 (Optional)</label>
                        <input type="file" name="image2" id="newPackageImage2" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Package Image 3 (Optional)</label>
                        <input type="file" name="image3" id="newPackageImage3" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Package Inclusions</label>
                        <div id="inclusionsContainer">
                            <div class="input-group mb-2">
                                <input type="text" name="inclusions[]" class="form-control" placeholder="Enter inclusion">
                                <button type="button" class="btn btn-outline-danger remove-inclusion" style="display: none;">Remove</button>
                            </div>
                        </div>
                        <button type="button" id="addInclusion" class="btn btn-outline-success btn-sm">+ Add Inclusion</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Package</button>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="confirmDeleteMessage">Are you sure you want to delete this package?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteYes">Delete</button>
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
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
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