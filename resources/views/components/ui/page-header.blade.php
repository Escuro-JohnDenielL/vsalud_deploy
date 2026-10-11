{{--
    Shared page header (admin and patron pages).
    Base styling lives in resources/css/app.css under .vs-page-header.
    Usage:
        <x-ui.page-header title="View Inquiries" subtitle="View and manage customer inquiries.">
            <x-slot:action>
                <a href="..." class="admin-btn admin-btn-primary">+ Create</a>
            </x-slot:action>
        </x-ui.page-header>
--}}
@props(['title', 'subtitle' => null])

<div class="vs-page-header">
    <div class="vs-page-header__title-wrap">
        <h1 class="vs-page-header__title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="vs-page-header__sub">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($action)
        <div class="vs-page-header__action">{{ $action }}</div>
    @endisset
</div>
