<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentSettingsController extends Controller
{
    public function index()
    {
        $settings = PaymentSetting::all()->keyBy('key');
        return view('admin.payment-settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'gcash_account_name'   => 'nullable|string|max:255',
            'gcash_mobile_number'  => 'nullable|string|max:50',
            'gcash_qr'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'bank_name'            => 'nullable|string|max:255',
            'bank_account_name'    => 'nullable|string|max:255',
            'bank_qr'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'cash_instructions'    => 'nullable|string|max:500',
            'contact_phone'        => 'nullable|string|max:50',
            'contact_email'        => 'nullable|email|max:255',
        ]);

        $qrFile = $request->file('bank_qr');
        $gcashQrFile = $request->file('gcash_qr');
        unset($validated['bank_qr']);
        unset($validated['gcash_qr']);

        foreach ($validated as $key => $value) {
            PaymentSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '']
            );
        }

        if ($qrFile) {
            if (! $this->storeQr($qrFile, 'bank-qr', 'bank_qr_path')) {
                return redirect()->back()->withErrors(['bank_qr' => 'The bank QR image could not be saved.'])->withInput();
            }
        }

        if ($gcashQrFile) {
            if (! $this->storeQr($gcashQrFile, 'gcash-qr', 'gcash_qr_path')) {
                return redirect()->back()->withErrors(['gcash_qr' => 'The GCash QR image could not be saved.'])->withInput();
            }
        }

        return redirect()->back()->with('success', 'Payment settings updated successfully.');
    }

    private function storeQr(\Illuminate\Http\UploadedFile $file, string $directory, string $settingKey): bool
    {
        $disk = Storage::disk('public');
        $oldPath = PaymentSetting::get($settingKey);
        $newPath = $directory . '/' . bin2hex(random_bytes(20)) . '.' . strtolower($file->extension());

        if (! $disk->putFileAs($directory, $file, basename($newPath))) {
            return false;
        }

        PaymentSetting::updateOrCreate(
            ['key' => $settingKey],
            ['value' => $newPath]
        );

        if (is_string($oldPath) && preg_match('#^' . preg_quote($directory, '#') . '/[^/]+$#', $oldPath) && $oldPath !== $newPath) {
            $disk->delete($oldPath);
        }

        return true;
    }
}
