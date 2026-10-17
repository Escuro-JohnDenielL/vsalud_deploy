@extends('layouts.admin')

@section('title', 'Payment Settings')

@section('content')
<div style="max-width: 720px; margin: 32px auto; padding: 0 20px;">
    <h1 style="font-family: var(--font-heading); font-size: 28px; color: var(--color-primary); margin: 0 0 6px;">Payment Settings</h1>
    <p style="color: #6b7280; margin: 0 0 24px;">Manage payment instructions shown to patrons. Changes take effect immediately.</p>

    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.payment-settings.update') }}" style="background:#fff;border-radius:16px;border:1px solid #e5e7eb;box-shadow:0 1px 3px rgba(0,0,0,0.05);padding:28px;">
        @csrf

        <h3 style="color:#2d2d2d;margin:0 0 16px;padding-bottom:10px;border-bottom:1px solid #eef2ed;">GCash</h3>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#2d2d2d;font-size:13px;">Account Name</label>
            <input type="text" name="gcash_account_name" value="{{ old('gcash_account_name', $settings['gcash_account_name']->value ?? 'Elizabeth R.') }}" style="width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:9px 12px;font-size:14px;">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#2d2d2d;font-size:13px;">Mobile Number</label>
            <input type="text" name="gcash_mobile_number" value="{{ old('gcash_mobile_number', $settings['gcash_mobile_number']->value ?? '09062236120') }}" style="width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:9px 12px;font-size:14px;">
        </div>

        <div style="margin-bottom:16px;">
            <label for="gcash_qr" style="display:block;font-weight:600;margin-bottom:4px;color:#2d2d2d;font-size:13px;">GCash QR Image (JPG, PNG, or WebP, max 2 MB)</label>
            <input type="file" id="gcash_qr" name="gcash_qr" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" style="width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:9px 12px;font-size:14px;">
            @error('gcash_qr') <p style="color:#b91c1c;font-size:12px;margin:4px 0 0;">{{ $message }}</p> @enderror
            @if (!empty($settings['gcash_qr_path']->value) && (str_starts_with($settings['gcash_qr_path']->value, 'gcash-qr/') ? \Illuminate\Support\Facades\Storage::disk('public')->exists($settings['gcash_qr_path']->value) : true))
                <div style="margin-top:12px;">
                    <p style="color:#6b7280;font-size:12px;margin:0 0 6px;">Current GCash QR image:</p>
                    <img src="{{ str_starts_with($settings['gcash_qr_path']->value, 'gcash-qr/') ? route('patron.gcash-qr') : asset($settings['gcash_qr_path']->value) }}" alt="Current GCash QR payment code" style="max-width:240px;max-height:240px;object-fit:contain;border:1px solid #e5e7eb;border-radius:8px;padding:6px;">
                </div>
            @endif
        </div>

        <h3 style="color:#2d2d2d;margin:24px 0 16px;padding-bottom:10px;border-bottom:1px solid #eef2ed;">Bank Transfer (QRPH)</h3>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#2d2d2d;font-size:13px;">Bank Name</label>
            <input type="text" name="bank_name" value="{{ old('bank_name', $settings['bank_name']->value ?? 'BPI Savings Bank') }}" style="width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:9px 12px;font-size:14px;">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#2d2d2d;font-size:13px;">Account Name</label>
            <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $settings['bank_account_name']->value ?? 'Ernesto Rafael Jr. and/or Elizabeth Rafael') }}" style="width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:9px 12px;font-size:14px;">
        </div>

        <div style="margin-bottom:16px;">
            <label for="bank_qr" style="display:block;font-weight:600;margin-bottom:4px;color:#2d2d2d;font-size:13px;">Bank QR Image (JPG, PNG, or WebP, max 2 MB)</label>
            <input type="file" id="bank_qr" name="bank_qr" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" style="width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:9px 12px;font-size:14px;">
            @error('bank_qr') <p style="color:#b91c1c;font-size:12px;margin:4px 0 0;">{{ $message }}</p> @enderror
            @if (!empty($settings['bank_qr_path']->value) && \Illuminate\Support\Facades\Storage::disk('public')->exists($settings['bank_qr_path']->value))
                <div style="margin-top:12px;">
                    <p style="color:#6b7280;font-size:12px;margin:0 0 6px;">Current bank QR image:</p>
                    <img src="{{ route('patron.bank-qr') }}" alt="Current Bank Transfer QRPH code" style="max-width:240px;max-height:240px;object-fit:contain;border:1px solid #e5e7eb;border-radius:8px;padding:6px;">
                </div>
            @endif
        </div>

        <h3 style="color:#2d2d2d;margin:24px 0 16px;padding-bottom:10px;border-bottom:1px solid #eef2ed;">Cash Payment</h3>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#2d2d2d;font-size:13px;">Instructions</label>
            <textarea name="cash_instructions" rows="3" style="width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:9px 12px;font-size:14px;">{{ old('cash_instructions', $settings['cash_instructions']->value ?? 'Pay in person at the Villa office.') }}</textarea>
        </div>

        <h3 style="color:#2d2d2d;margin:24px 0 16px;padding-bottom:10px;border-bottom:1px solid #eef2ed;">Contact Information</h3>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#2d2d2d;font-size:13px;">Phone Number</label>
            <input type="text" name="contact_phone" value="{{ old('contact_phone', $settings['contact_phone']->value ?? '(+63) 912 345 6789') }}" style="width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:9px 12px;font-size:14px;">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#2d2d2d;font-size:13px;">Email Address</label>
            <input type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email']->value ?? 'coheredit@gmail.com') }}" style="width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:9px 12px;font-size:14px;">
        </div>

        <div style="margin-top:24px;">
            <button type="submit" class="admin-btn admin-btn-primary" style="padding:10px 28px;">Save Settings</button>
            <a href="{{ route('admin.home') }}" style="color:#6b7280;font-size:14px;margin-left:16px;">&larr; Back to Dashboard</a>
        </div>
    </form>
</div>
@endsection
