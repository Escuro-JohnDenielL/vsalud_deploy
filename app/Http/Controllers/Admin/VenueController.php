<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Models\VenuePhoto;
use App\Support\VenueCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Admin management of venue galleries.
 *
 * Photos are stored once per venue (storage/app/public/venues/{id}) and reused
 * by every package bound to that venue, so nothing is uploaded twice.
 */
class VenueController extends Controller
{
    public function index()
    {
        $venues = Venue::with(['photos', 'rates'])
            ->withCount('packages')
            ->ordered()
            ->get();

        return view('admin.venues', compact('venues'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:venues,name',
            'capacity' => 'nullable|integer|min:1|max:9999',
            'description' => 'nullable|string|max:2000',
        ]);

        $venue = Venue::create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'capacity' => $validated['capacity'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort_order' => ((int) Venue::withTrashed()->max('sort_order')) + 1,
            'is_active' => true,
        ]);

        VenueCatalog::clearCache();

        return redirect()
            ->route('admin.venues')
            ->with('success', "\"{$venue->name}\" was added. Upload its photos below.");
    }

    public function update(Request $request, Venue $venue)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:venues,name,' . $venue->id,
            'capacity' => 'nullable|integer|min:1|max:9999',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
            'rates_submitted' => 'sometimes|boolean',
            'rates' => 'nullable|array|max:20',
            'rates.*.label' => 'required|string|max:100',
            'rates.*.period' => 'nullable|in:AM,PM',
            'rates.*.price' => 'required|numeric|min:0|max:99999999.99',
        ]);

        DB::transaction(function () use ($venue, $validated, $request) {
            $venue->update([
                'name' => $validated['name'],
                'capacity' => $validated['capacity'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            if ($request->boolean('rates_submitted')) {
                $this->replaceRates($venue, $validated['rates'] ?? []);
            }
        });

        VenueCatalog::clearCache();

        return redirect()
            ->route('admin.venues')
            ->with('success', "\"{$venue->name}\" was updated.");
    }

    public function destroy(Venue $venue)
    {
        // Refuse while packages still use the venue: silently unbinding them
        // would remove the "View venue" button from live package cards.
        $packageCount = $venue->packages()->count();

        if ($packageCount > 0) {
            return redirect()
                ->route('admin.venues')
                ->with('error', "\"{$venue->name}\" is still used by {$packageCount} package(s). Change those packages to another venue first.");
        }

        foreach ($venue->photos as $photo) {
            $this->deletePhotoFile($photo);
            $photo->delete();
        }

        // Hard delete: the venue is unreachable once gone and a soft-deleted row
        // would still occupy the unique name/slug, blocking a re-add.
        $venue->forceDelete();
        VenueCatalog::clearCache();

        return redirect()
            ->route('admin.venues')
            ->with('success', "\"{$venue->name}\" was deleted.");
    }

    public function storePhotos(Request $request, Venue $venue)
    {
        $request->validate([
            'photos' => 'required|array|min:1',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        $nextOrder = ((int) $venue->photos()->max('sort_order')) + 1;
        $saved = 0;

        foreach ($request->file('photos', []) as $file) {
            // The public disk is configured with 'throw' => false, so store()
            // returns false instead of throwing when a write fails.
            $path = $file->store("venues/{$venue->id}", 'public');

            if (! is_string($path)) {
                continue;
            }

            VenuePhoto::create([
                'venue_id' => $venue->id,
                'path' => "/storage/{$path}",
                'alt_text' => $venue->name,
                'sort_order' => $nextOrder++,
            ]);

            $saved++;
        }

        if ($saved === 0) {
            return redirect()
                ->route('admin.venues')
                ->with('error', 'The photos could not be saved. Please try again.');
        }

        return redirect()
            ->route('admin.venues')
            ->with('success', "{$saved} photo(s) added to {$venue->name}.");
    }

    public function destroyPhoto(VenuePhoto $photo)
    {
        $venueName = $photo->venue?->name ?? 'the venue';

        $this->deletePhotoFile($photo);
        $photo->delete();

        return redirect()
            ->route('admin.venues')
            ->with('success', "A photo was removed from {$venueName}.");
    }

    /**
     * Remove several photos of one venue at once ("Delete selected").
     *
     * Only ids that actually belong to the venue are touched, so a tampered
     * request cannot delete photos from another villa's gallery.
     */
    public function destroyPhotos(Request $request, Venue $venue)
    {
        $validated = $request->validate([
            'photo_ids' => 'required|array|min:1',
            'photo_ids.*' => 'integer',
        ], [
            'photo_ids.required' => 'Select at least one photo to delete.',
            'photo_ids.min' => 'Select at least one photo to delete.',
        ]);

        $photos = $venue->photos()
            ->whereIn('id', $validated['photo_ids'])
            ->get();

        if ($photos->isEmpty()) {
            return redirect()
                ->route('admin.venues')
                ->with('error', 'The selected photos no longer exist.');
        }

        $deleted = 0;

        foreach ($photos as $photo) {
            $this->deletePhotoFile($photo);
            $photo->delete();
            $deleted++;
        }

        $message = $deleted === 1
            ? "1 photo was removed from {$venue->name}."
            : "{$deleted} photos were removed from {$venue->name}.";

        return redirect()
            ->route('admin.venues')
            ->with('success', $message);
    }

    public function makeCover(VenuePhoto $photo)
    {
        $venue = $photo->venue;

        if (! $venue) {
            return redirect()->route('admin.venues');
        }

        $position = 0;
        $photo->update(['sort_order' => $position++]);

        foreach ($venue->photos()->where('id', '!=', $photo->id)->get() as $other) {
            $other->update(['sort_order' => $position++]);
        }

        return redirect()
            ->route('admin.venues')
            ->with('success', "That photo is now the cover for {$venue->name}.");
    }

    private function deletePhotoFile(VenuePhoto $photo): void
    {
        $relative = str_replace('/storage/', '', (string) $photo->path);

        if ($relative !== '' && ! preg_match('#^https?://#i', $relative)) {
            Storage::disk('public')->delete($relative);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'venue';
        $slug = $base;
        $suffix = 2;

        while (Venue::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    /**
     * Replace the admin-managed rate choices. Historical inquiry estimates
     * retain their own label and amount snapshots, so removing a rate is safe.
     *
     * @param  array<int, array{label: string, period?: ?string, price: int|float|string}>  $rates
     */
    private function replaceRates(Venue $venue, array $rates): void
    {
        $venue->rates()->delete();

        foreach (array_values($rates) as $rate) {
            $venue->rates()->create([
                'label' => trim($rate['label']),
                'period' => $rate['period'] ?: null,
                'price' => $rate['price'],
            ]);
        }
    }
}
