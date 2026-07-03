<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>IT Dashboard - Villa Salud</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background: linear-gradient(180deg, #f3f7f1 0%, #ffffff 100%);
        }
        .it-shell {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 20px 60px;
        }
        .it-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 28px;
        }
        .it-title {
            font-family: Georgia, serif;
            font-size: 34px;
            margin: 0;
            color: #123b26;
        }
        .it-subtitle {
            margin: 6px 0 0;
            color: #587064;
        }
        .it-link {
            color: #165c34;
            text-decoration: none;
            font-weight: 700;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .panel {
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(18, 59, 38, 0.1);
            box-shadow: 0 10px 30px rgba(18, 59, 38, 0.06);
            padding: 22px;
        }
        .panel h2 {
            font-size: 20px;
            margin: 0 0 16px;
            color: #123b26;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .stat {
            background: #f6faf5;
            border-radius: 12px;
            padding: 14px;
            border: 1px solid #e2ece0;
        }
        .stat strong {
            display: block;
            font-size: 28px;
            color: #165c34;
        }
        .stat span {
            color: #5d6f63;
            font-size: 13px;
        }
        .field {
            margin-bottom: 12px;
        }
        .field label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #22332a;
        }
        .field input,
        .field select {
            width: 100%;
            border: 1px solid #d7dfd5;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
        }
        .btn-primary,
        .btn-ghost {
            border: none;
            border-radius: 999px;
            padding: 10px 16px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #165c34;
            color: #fff;
        }
        .btn-ghost {
            background: #edf4ec;
            color: #165c34;
        }
        .list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .list li {
            padding: 10px 0;
            border-bottom: 1px solid #eef2ed;
        }
        .muted {
            color: #6b7d71;
            font-size: 13px;
        }
        .token-box {
            margin-top: 12px;
            padding: 12px;
            border-radius: 12px;
            background: #f0f8f2;
            border: 1px solid #d4e7d8;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <div class="it-shell">
        <div class="it-header">
            <div>
                <h1 class="it-title">IT Dashboard</h1>
                <p class="it-subtitle">Create admin accounts and guest passes from one restricted page.</p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="{{ url('/') }}" class="btn-ghost">Landing Page</a>
                <form method="POST" action="{{ route('it.logout') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn-primary">Logout</button>
                </form>
            </div>
        </div>

        @if (session('success'))
            <div class="panel" style="margin-bottom: 20px; border-color: rgba(22,92,52,0.25); background: #f5fbf6;">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="panel" style="margin-bottom: 20px; border-color: rgba(160,30,30,0.25); background: #fff6f6;">
                {{ session('error') }}
            </div>
        @endif

        <div class="stats">
            <div class="stat">
                <strong>{{ $adminCount }}</strong>
                <span>Admin accounts</span>
            </div>
            <div class="stat">
                <strong>{{ $guestCount }}</strong>
                <span>Guest passes</span>
            </div>
        </div>

        <div class="grid">
            <div class="panel">
                <h2>Create Admin</h2>
                <form method="POST" action="{{ route('it.admins.store') }}">
                    @csrf
                    <div class="field">
                        <label>First Name</label>
                        <input type="text" name="f_name" required>
                    </div>
                    <div class="field">
                        <label>Last Name</label>
                        <input type="text" name="l_name" required>
                    </div>
                    <div class="field">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="field">
                        <label>Phone</label>
                        <input type="text" name="phone" maxlength="11" required>
                    </div>
                    <div class="field">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="field">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirmation" required>
                    </div>
                    {{-- PROFILE PICTURE: commented out for now — re-enable later
                    <div class="field">
                        <label>Profile Picture</label>
                        <select name="profile_picture">
                            <option value="default.png">Default</option>
                            <option value="boy.png">Boy</option>
                            <option value="boy1.png">Boy 1</option>
                            <option value="boy2.png">Boy 2</option>
                            <option value="girl.png">Girl</option>
                            <option value="girl1.png">Girl 1</option>
                            <option value="girl2.png">Girl 2</option>
                        </select>
                    </div>
                    --}}
                    <button class="btn-primary" type="submit">Create Admin</button>
                </form>
            </div>

            <div class="panel">
                <h2>Create Guest Pass</h2>
                <form method="POST" action="{{ route('it.guests.store') }}">
                    @csrf
                    <div class="field">
                        <label>Guest Label</label>
                        <input type="text" name="label" placeholder="e.g. John Guest" required>
                    </div>
                    <div class="field">
                        <label>Email, optional</label>
                        <input type="email" name="email" placeholder="guest@example.com">
                    </div>
                    <div class="field">
                        <label>Valid Days</label>
                        <input type="number" name="valid_days" value="30" min="1" max="365" required>
                    </div>
                    <button class="btn-primary" type="submit">Issue Guest Pass</button>
                </form>

                @if (session('guest_token'))
                    <div class="token-box">
                        <div class="muted">Guest token:</div>
                        <strong>{{ session('guest_token') }}</strong>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid" style="margin-top: 20px;">
            <div class="panel">
                <h2>Recent Admins</h2>
                <ul class="list">
                    @forelse ($recentAdmins as $admin)
                        <li>
                            <strong>{{ $admin->f_name }} {{ $admin->l_name }}</strong>
                            <div class="muted">{{ $admin->email }}</div>
                        </li>
                    @empty
                        <li class="muted">No admin accounts yet.</li>
                    @endforelse
                </ul>
            </div>

            <div class="panel">
                <h2>Recent Guest Passes</h2>
                <ul class="list">
                    @forelse ($recentGuests as $guest)
                        <li>
                            <strong>{{ $guest->label ?? 'Guest' }}</strong>
                            <div class="muted">{{ $guest->email ?? 'No email' }}</div>
                            <div class="muted">Expires: {{ optional($guest->expires_at)->format('M d, Y h:i A') }}</div>
                        </li>
                    @empty
                        <li class="muted">No guest passes yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
