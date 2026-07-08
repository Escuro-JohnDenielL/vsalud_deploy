@extends('layouts.admin')

@section('title', 'Payment Settings')

@section('content')
<div style="max-width: 720px; margin: 32px auto; padding: 0 20px;">
    <h1 style="font-family: Georgia, serif; font-size: 28px; color: #123b26; margin: 0 0 6px;">Payment Settings</h1>
    <p style="color: #587064; margin: 0 0 24px;">Manage payment instructions shown to patrons. Changes take effect immediately.</p>

    @if (session('success'))
        <div style="background:#f0f8f2;border:1px solid #d4e7d8;color:#155724;padding:12px 16px;border-radius:12px;margin-bottom:20px;">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.payment-settings.update') }}" style="background:#fff;border-radius:16px;border:1px solid rgba(18,59,38,0.1);box-shadow:0 10px 30px rgba(18,59,38,0.06);padding:28px;">
        @csrf

        <h3 style="color:#123b26;margin:0 0 16px;padding-bottom:10px;border-bottom:1px solid #eef2ed;">GCash</h3>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#22332a;font-size:13px;">Account Name</label>
            <input type="text" name="gcash_account_name" value="{{ old('gcash_account_name', $settings['gcash_account_name']->value ?? 'Elizabeth R.') }}" style="width:100%;border:1px solid #d7dfd5;border-radius:10px;padding:9px 12px;font-size:14px;">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#22332a;font-size:13px;">Mobile Number</label>
            <input type="text" name="gcash_mobile_number" value="{{ old('gcash_mobile_number', $settings['gcash_mobile_number']->value ?? '09062236120') }}" style="width:100%;border:1px solid #d7dfd5;border-radius:10px;padding:9px 12px;font-size:14px;">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#22332a;font-size:13px;">QR Code Image Path</label>
            <input type="text" name="gcash_qr_path" value="{{ old('gcash_qr_path', $settings['gcash_qr_path']->value ?? 'images/gcash.jpg') }}" style="width:100%;border:1px solid #d7dfd5;border-radius:10px;padding:9px 12px;font-size:14px;">
            <p style="color:#6b7d71;font-size:12px;margin:4px 0 0;">Relative to the <code>public/</code> directory. Upload new QR via your web host's file manager.</p>
        </div>

        <h3 style="color:#123b26;margin:24px 0 16px;padding-bottom:10px;border-bottom:1px solid #eef2ed;">Bank Transfer (BPI)</h3>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#22332a;font-size:13px;">Bank Name</label>
            <input type="text" name="bank_name" value="{{ old('bank_name', $settings['bank_name']->value ?? 'BPI Savings Bank') }}" style="width:100%;border:1px solid #d7dfd5;border-radius:10px;padding:9px 12px;font-size:14px;">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#22332a;font-size:13px;">Account Name</label>
            <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $settings['bank_account_name']->value ?? 'Ernesto Rafael Jr. and/or Elizabeth Rafael') }}" style="width:100%;border:1px solid #d7dfd5;border-radius:10px;padding:9px 12px;font-size:14px;">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#22332a;font-size:13px;">Account Number</label>
            <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $settings['bank_account_number']->value ?? '8230001538') }}" style="width:100%;border:1px solid #d7dfd5;border-radius:10px;padding:9px 12px;font-size:14px;">
        </div>

        <h3 style="color:#123b26;margin:24px 0 16px;padding-bottom:10px;border-bottom:1px solid #eef2ed;">Cash Payment</h3>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#22332a;font-size:13px;">Instructions</label>
            <textarea name="cash_instructions" rows="3" style="width:100%;border:1px solid #d7dfd5;border-radius:10px;padding:9px 12px;font-size:14px;">{{ old('cash_instructions', $settings['cash_instructions']->value ?? 'Pay in person at the Villa office.') }}</textarea>
        </div>

        <h3 style="color:#123b26;margin:24px 0 16px;padding-bottom:10px;border-bottom:1px solid #eef2ed;">Contact Information</h3>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#22332a;font-size:13px;">Phone Number</label>
            <input type="text" name="contact_phone" value="{{ old('contact_phone', $settings['contact_phone']->value ?? '(+63) 912 345 6789') }}" style="width:100%;border:1px solid #d7dfd5;border-radius:10px;padding:9px 12px;font-size:14px;">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#22332a;font-size:13px;">Email Address</label>
            <input type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email']->value ?? 'coheredit@gmail.com') }}" style="width:100%;border:1px solid #d7dfd5;border-radius:10px;padding:9px 12px;font-size:14px;">
        </div>

        <div style="margin-top:24px;">
            <button type="submit" style="background:#165c34;color:#fff;border:none;border-radius:999px;padding:10px 28px;font-weight:600;cursor:pointer;">Save Settings</button>
            <a href="{{ route('admin.home') }}" style="color:#587064;font-size:14px;margin-left:16px;">&larr; Back to Dashboard</a>
        </div>
    </form>
</div>
@endsection
