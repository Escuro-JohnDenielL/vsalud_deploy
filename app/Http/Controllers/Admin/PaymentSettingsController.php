<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;

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
            'gcash_qr_path'        => 'nullable|string|max:255',
            'bank_name'            => 'nullable|string|max:255',
            'bank_account_name'    => 'nullable|string|max:255',
            'bank_account_number'  => 'nullable|string|max:100',
            'cash_instructions'    => 'nullable|string|max:500',
            'contact_phone'        => 'nullable|string|max:50',
            'contact_email'        => 'nullable|email|max:255',
        ]);

        foreach ($validated as $key => $value) {
            PaymentSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '']
            );
        }

        return redirect()->back()->with('success', 'Payment settings updated successfully.');
    }
}
