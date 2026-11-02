@extends('layouts.admin')

@section('title', 'Venues')

@push('styles')
    @vite('resources/css/admin/venues.css')
@endpush

@section('content')
<div class="vs-page vs-admin-venues">
    <x-ui.page-header
        title="Venues"
        subtitle="Manage the villa galleries that packages show on the patron Packages page.">
        <x-slot:action>
            <x-ui.page-info id="venuesInfo" title="How venue galleries work">
                <ol>
                    <li>Each venue owns one photo gallery. It is uploaded here, once.</li>
                    <li>Every package bound to the venue shows the same gallery — no second upload.</li>
                    <li>Bind a package to a venue on the Packages page (Add or Edit, "Venue" field).</li>
                    <li>The first photo is the cover. Use "Make cover" on another photo to change it.</li>
                    <li>A venue that still has packages cannot be deleted; move those packages first.</li>
                </ol>
            </x-ui.page-info>
        </x-slot:action>
    </x-ui.page-header>

    @if ($errors->any())
        <div class="vs-admin-venues__alert" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- Add venue --}}
    <section class="vs-admin-venues__panel">
        <h2 class="vs-admin-venues__panel-title">Add a venue</h2>
        <form method="POST" action="{{ route('admin.venues.store') }}" class="vs-admin-venues__form">
            @csrf
            <div class="vs-admin-venues__field">
                <label for="venueName">Venue name</label>
                <input type="text" id="venueName" name="name" value="{{ old('name') }}" maxlength="100" required>
            </div>
            <div class="vs-admin-venues__field vs-admin-venues__field--narrow">
                <label for="venueCapacity">Capacity (pax)</label>
                <input type="number" id="venueCapacity" name="capacity" value="{{ old('capacity') }}" min="1" max="9999">
            </div>
            <div class="vs-admin-venues__field vs-admin-venues__field--wide">
                <label for="venueDescription">Description</label>
                <textarea id="venueDescription" name="description" rows="2" maxlength="2000">{{ old('description') }}</textarea>
            </div>
            <div class="vs-admin-venues__actions">
                <button type="submit" class="admin-btn admin-btn-primary">Add venue</button>
            </div>
        </form>
    </section>

    <div class="vs-admin-venues__list">
        @forelse ($venues as $venue)
            <section class="venue-card">
                <header class="venue-card__head">
                    <div>
                        <h2 class="venue-card__title">
                            {{ $venue->name }}
                            @if (! $venue->is_active)
                                <span class="venue-card__badge">Hidden</span>
                            @endif
                        </h2>
                        <p class="venue-card__meta">
                            {{ $venue->capacity ? $venue->capacity . ' pax' : 'Capacity not set' }}
                            &middot; {{ $venue->photos->count() }} photo(s)
                            &middot; {{ $venue->packages_count }} package(s)
                        </p>
                    </div>
                    <form method="POST" action="{{ route('admin.venues.destroy', $venue) }}"
                          onsubmit="return confirm('Delete &quot;{{ $venue->name }}&quot; and its photo gallery? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm">Delete venue</button>
                    </form>
                </header>

                {{-- Bulk delete target. The checkboxes below join this form with
                     form="..." so the per-photo Make cover/Remove forms can stay
                     their own (no nested forms). --}}
                <form id="venueBulkForm{{ $venue->id }}" method="POST" hidden
                      action="{{ route('admin.venues.photos.bulk-destroy', $venue) }}"
                      onsubmit="return confirm('Delete the selected photos from {{ $venue->name }}? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                </form>

                @if ($venue->photos->isNotEmpty())
                    <div class="venue-card__toolbar">
                        <label class="venue-card__checkall">
                            <input type="checkbox" data-venue-select-all="{{ $venue->id }}">
                            <span>Select all</span>
                        </label>
                        <span class="venue-card__selection" data-venue-selection-for="{{ $venue->id }}">No photos selected</span>
                        <button type="submit" form="venueBulkForm{{ $venue->id }}"
                                class="admin-btn admin-btn-danger admin-btn-sm"
                                data-venue-bulk-submit="{{ $venue->id }}" disabled>
                            Delete selected
                        </button>
                    </div>
                @endif

                <div class="venue-card__photos">
                    @php $hiddenPhotos = 0; @endphp
                    @foreach ($venue->photos as $index => $photo)
                        @php $fileExists = \App\Support\PublicImage::exists($photo->path); @endphp
                        @unless ($fileExists)
                            @php $hiddenPhotos++; @endphp
                        @endunless
                        <figure class="venue-photo{{ $fileExists ? '' : ' venue-photo--missing' }}">
                            <input type="checkbox" class="venue-photo__check"
                                   name="photo_ids[]" value="{{ $photo->id }}"
                                   form="venueBulkForm{{ $venue->id }}"
                                   data-venue-photo="{{ $venue->id }}"
                                   aria-label="Select photo {{ $index + 1 }} of {{ $venue->name }}">
                            @if ($fileExists)
                                <img src="{{ $photo->url() }}" alt="{{ $photo->alt_text ?: $venue->name }}" loading="lazy">
                            @else
                                <div class="venue-photo__missing">
                                    File missing
                                    <span>{{ basename($photo->path) }}</span>
                                </div>
                            @endif
                            <figcaption class="venue-photo__actions">
                                @if ($index === 0 && $fileExists)
                                    <span class="venue-photo__cover">Cover</span>
                                @elseif ($fileExists)
                                    <form method="POST" action="{{ route('admin.venues.photos.cover', $photo) }}">
                                        @csrf
                                        <button type="submit" class="admin-btn admin-btn-ghost admin-btn-sm">Make cover</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.venues.photos.destroy', $photo) }}"
                                      onsubmit="return confirm('Remove this photo from {{ $venue->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm">Remove</button>
                                </form>
                            </figcaption>
                        </figure>
                    @endforeach

                    @if ($venue->photos->isEmpty())
                        <p class="venue-card__empty">No photos yet. Upload the first {{ $venue->name }} photos below.</p>
                    @elseif ($hiddenPhotos > 0)
                        <p class="venue-card__empty">{{ $hiddenPhotos }} saved photo(s) are missing from the server. Tick them and use "Delete selected" to clean up the broken records.</p>
                    @endif
                </div>

                <form method="POST" action="{{ route('admin.venues.photos.store', $venue) }}"
                      enctype="multipart/form-data" class="venue-card__upload">
                    @csrf
                    <label for="venuePhotos{{ $venue->id }}" class="venue-card__upload-label">
                        Add photos
                        <span>JPEG, PNG or WebP &middot; up to 4 MB each &middot; select several at once</span>
                    </label>
                    <input type="file" id="venuePhotos{{ $venue->id }}" name="photos[]"
                           accept="image/jpeg,image/png,image/webp" multiple required>
                    <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm">Upload</button>
                </form>

                <details class="venue-card__edit">
                    <summary>Edit venue details</summary>
                    <form method="POST" action="{{ route('admin.venues.update', $venue) }}" class="vs-admin-venues__form">
                        @csrf
                        @method('PUT')
                        <div class="vs-admin-venues__field">
                            <label for="venueName{{ $venue->id }}">Venue name</label>
                            <input type="text" id="venueName{{ $venue->id }}" name="name" value="{{ $venue->name }}" maxlength="100" required>
                        </div>
                        <div class="vs-admin-venues__field vs-admin-venues__field--narrow">
                            <label for="venueCapacity{{ $venue->id }}">Capacity (pax)</label>
                            <input type="number" id="venueCapacity{{ $venue->id }}" name="capacity" value="{{ $venue->capacity }}" min="1" max="9999">
                        </div>
                        <div class="vs-admin-venues__field vs-admin-venues__field--wide">
                            <label for="venueDescription{{ $venue->id }}">Description</label>
                            <textarea id="venueDescription{{ $venue->id }}" name="description" rows="2" maxlength="2000">{{ $venue->description }}</textarea>
                        </div>
                        <div class="vs-admin-venues__actions">
                            <label class="vs-admin-venues__check">
                                <input type="checkbox" name="is_active" value="1" @checked($venue->is_active)>
                                Show this venue's gallery to patrons
                            </label>
                            <button type="submit" class="admin-btn admin-btn-primary">Save changes</button>
                        </div>
                    </form>
                </details>
            </section>
        @empty
            <p class="vs-admin-venues__empty">No venues yet. Add the first one above.</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Bulk photo selection: the "Select all" box, the running count, and the
    // "Delete selected" button all reflect the checkboxes of one venue card.
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll("[data-venue-select-all]").forEach(function (master) {
            const venueId = master.dataset.venueSelectAll;
            const boxes = Array.from(document.querySelectorAll('[data-venue-photo="' + venueId + '"]'));
            const submit = document.querySelector('[data-venue-bulk-submit="' + venueId + '"]');
            const label = document.querySelector('[data-venue-selection-for="' + venueId + '"]');

            if (!boxes.length) {
                master.disabled = true;
                master.closest(".venue-card__checkall")?.setAttribute("hidden", "");
            }

            const sync = function () {
                const checked = boxes.filter(function (box) { return box.checked; });
                master.checked = boxes.length > 0 && checked.length === boxes.length;
                master.indeterminate = checked.length > 0 && checked.length < boxes.length;
                if (submit) submit.disabled = checked.length === 0;
                if (label) {
                    label.textContent = checked.length
                        ? checked.length + " selected"
                        : "No photos selected";
                }
            };

            master.addEventListener("change", function () {
                boxes.forEach(function (box) { box.checked = master.checked; });
                sync();
            });

            boxes.forEach(function (box) {
                box.addEventListener("change", sync);
            });

            sync();
        });
    });
</script>
@endpush
