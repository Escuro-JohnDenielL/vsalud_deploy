{{--
    Shared site footer used by the landing page and the patron layout.
    Reads every detail from config/villasalud.php and omits any empty line.
    Pass a context class to style it: class="landing-footer" or class="vs-patron-footer".
--}}
@props(['class' => ''])

@php
    $contact = config('villasalud.contact', []);
    $social = config('villasalud.social', []);

    // A value can hold several numbers ("8837-68-01 / 8776-68-67"). Split it so
    // each number gets its own valid tel: link instead of one broken one.
    $splitNumbers = function ($value) {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return collect(preg_split('#[/,]#', $value))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();
    };

    $tel = fn ($value) => 'tel:' . preg_replace('/[^0-9+]/', '', $value);

    $email = $contact['email'] ?? null;
    $address = $contact['address'] ?? null;
    $phones = $splitNumbers($contact['phone'] ?? null);
    $mobiles = $splitNumbers($contact['mobile'] ?? null);
    $faxes = $splitNumbers($contact['fax'] ?? null);
    $facebook = $social['facebook'] ?? null;
    $instagram = $social['instagram'] ?? null;

    $hasContact = $address || $email || $phones || $mobiles || $faxes;
@endphp

<footer class="site-footer {{ $class }}">
    <div class="site-footer__inner">
        <div class="site-footer__brand">
            <span class="site-footer__logo">VILLA SALUD</span>
        </div>

        @if ($hasContact)
            <div class="site-footer__col">
                <h2 class="site-footer__heading">Contact</h2>
                <ul class="site-footer__list">
                    @if ($address)
                        <li>{{ $address }}</li>
                    @endif
                    @if ($email)
                        <li><a class="site-footer__email" href="mailto:{{ $email }}">{{ $email }}</a></li>
                    @endif
                    @if (! empty($phones))
                        <li>
                            <span>Office: </span>
                            @foreach ($phones as $number)<a href="{{ $tel($number) }}">{{ $number }}</a>@if (! $loop->last)<span>, </span>@endif @endforeach
                        </li>
                    @endif
                    @if (! empty($mobiles))
                        <li>
                            <span>Mobile: </span>
                            @foreach ($mobiles as $number)<a href="{{ $tel($number) }}">{{ $number }}</a>@if (! $loop->last)<span>, </span>@endif @endforeach
                        </li>
                    @endif
                    @if (! empty($faxes))
                        <li>
                            <span>Tele-fax: </span>
                            @foreach ($faxes as $number)<a href="{{ $tel($number) }}">{{ $number }}</a>@if (! $loop->last)<span>, </span>@endif @endforeach
                        </li>
                    @endif
                </ul>
            </div>
        @endif

        @if ($facebook || $instagram)
            <div class="site-footer__col">
                <h2 class="site-footer__heading">Follow</h2>
                <ul class="site-footer__list">
                    @if ($facebook)
                        <li><a href="{{ $facebook }}" target="_blank" rel="noopener">Facebook</a></li>
                    @endif
                    @if ($instagram)
                        <li><a href="{{ $instagram }}" target="_blank" rel="noopener">Instagram</a></li>
                    @endif
                </ul>
            </div>
        @endif
    </div>

    <div class="site-footer__bottom">
        <p>&copy; {{ date('Y') }} Villa Salud Catering. All rights reserved.</p>
        {{ $slot }}
    </div>
</footer>
