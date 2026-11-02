<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Package;
use Illuminate\Support\Facades\Storage;

class PackageController extends Controller
{
    public function index()
    {
        return response()->json(Package::with('venues')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image3' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'inclusions' => 'nullable|array',
            // Blank inclusion rows are submitted as "" and Laravel's
            // ConvertEmptyStringsToNull middleware turns those into null,
            // so the rule must allow null (cleanInclusions() strips them later).
            'inclusions.*' => 'nullable|string|max:255',
            'venues' => 'nullable|array',
            'venues.*' => 'integer|exists:venues,id',
        ]);

        $imagePath = $request->file('image')->store('packages', 'public');
        $image2Path = $request->file('image2')?->store('packages', 'public');
        $image3Path = $request->file('image3')?->store('packages', 'public');

        $package = Package::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'image_path' => "/storage/$imagePath",
            'image_2_path' => $image2Path ? "/storage/$image2Path" : null,
            'image_3_path' => $image3Path ? "/storage/$image3Path" : null,
            'inclusions' => $this->cleanInclusions($request->input('inclusions', [])),
        ]);

        $package->venues()->sync($this->cleanVenueIds($request->input('venues', [])));

        // Include the venues so the admin card shows them immediately instead of
        // "No venue linked" until the next page load.
        $package->load('venues');

        return response()->json($package, 201);
    }

    public function show(Package $package)
    {
        return response()->json($package);
    }

    public function update(Request $request, Package $package)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image3' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'inclusions' => 'nullable|array',
            // See store(): blank rows arrive as null, so allow null here too.
            'inclusions.*' => 'nullable|string|max:255',
            'venues' => 'nullable|array',
            'venues.*' => 'integer|exists:venues,id',
        ]);

        $data = $request->only(['name', 'description', 'price']);

        // Only sync when the form submitted the field, so an edit that omits it
        // does not silently unlink every venue.
        if ($request->has('venues')) {
            $package->venues()->sync($this->cleanVenueIds($request->input('venues', [])));
        }

        // Only touch inclusions when the form actually submitted them, otherwise an
        // edit that doesn't include the field would silently wipe the existing list.
        if ($request->has('inclusions')) {
            $data['inclusions'] = $this->cleanInclusions($request->input('inclusions', []));
        }

        if ($request->hasFile('image')) {
            // Delete old image
            if ($package->image_path) {
                $oldPath = str_replace('/storage/', '', $package->image_path);
                Storage::disk('public')->delete($oldPath);
            }
            $imagePath = $request->file('image')->store('packages', 'public');
            $data['image_path'] = "/storage/$imagePath";
        }

        if ($request->hasFile('image2')) {
            // Delete old image
            if ($package->image_2_path) {
                $oldPath = str_replace('/storage/', '', $package->image_2_path);
                Storage::disk('public')->delete($oldPath);
            }
            $image2Path = $request->file('image2')->store('packages', 'public');
            $data['image_2_path'] = "/storage/$image2Path";
        }

        if ($request->hasFile('image3')) {
            // Delete old image
            if ($package->image_3_path) {
                $oldPath = str_replace('/storage/', '', $package->image_3_path);
                Storage::disk('public')->delete($oldPath);
            }
            $image3Path = $request->file('image3')->store('packages', 'public');
            $data['image_3_path'] = "/storage/$image3Path";
        }

        $package->update($data);

        // Keep the response shape identical to index() (which eager-loads venues).
        $package->load('venues');

        return response()->json($package);
    }

    public function destroy(Package $package)
    {
        // Delete associated images
        if ($package->image_path) {
            $imagePath = str_replace('/storage/', '', $package->image_path);
            Storage::disk('public')->delete($imagePath);
        }
        if ($package->image_2_path) {
            $imagePath = str_replace('/storage/', '', $package->image_2_path);
            Storage::disk('public')->delete($imagePath);
        }
        if ($package->image_3_path) {
            $imagePath = str_replace('/storage/', '', $package->image_3_path);
            Storage::disk('public')->delete($imagePath);
        }

        $package->delete();

        return response()->json(['message' => 'Package deleted successfully']);
    }

    /**
     * Normalise the submitted inclusions list: keep non-empty strings only,
     * trimmed and re-indexed so it stores as a clean JSON array.
     *
     * @param  mixed  $inclusions
     * @return array<int, string>
     */
    private function cleanInclusions($inclusions): array
    {
        return collect(is_array($inclusions) ? $inclusions : [])
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim($value))
            ->values()
            ->all();
    }

    /**
     * Venue ids from the checkbox list, de-duplicated and cast to int so the
     * pivot sync receives exactly what the venue table expects.
     *
     * @param  mixed  $venues
     * @return array<int, int>
     */
    private function cleanVenueIds($venues): array
    {
        return collect(is_array($venues) ? $venues : [])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();
    }
}