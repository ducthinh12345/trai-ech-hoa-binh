@props(['seo' => null])
@php
    $seo ??= \App\Support\Seo::make();
    $storeName = setting('store_name', config('app.name'));
    $flash = collect(['success', 'error', 'warning'])
        ->filter(fn ($type) => session()->has($type))
        ->map(fn ($type) => ['type' => $type, 'message' => session($type)])
        ->values();
@endphp
<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $seo->fullTitle() }}</title>
    <meta name="description" content="{{ $seo->metaDescription() }}">
    <link rel="canonical" href="{{ $seo->canonicalUrl() }}">
    @if ($seo->noindex || setting('seo_noindex_site'))
        <meta name="robots" content="noindex, follow">
    @else
        <meta name="robots" content="index, follow, max-image-preview:large">
    @endif

    <meta property="og:site_name" content="{{ $storeName }}">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:type" content="{{ $seo->type }}">
    <meta property="og:title" content="{{ $seo->fullTitle() }}">
    <meta property="og:description" content="{{ $seo->metaDescription() }}">
    <meta property="og:url" content="{{ $seo->canonicalUrl() }}">
    <meta property="og:image" content="{{ $seo->imageUrl() }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#14532d">

    @if ($verify = setting('google_site_verification'))
        <meta name="google-site-verification" content="{{ $verify }}">
    @endif

    <link rel="icon" href="{{ setting('favicon') ? asset('storage/'.setting('favicon')) : asset('favicon.svg') }}">
    <link rel="alternate" type="application/rss+xml" title="{{ $storeName }}" href="{{ route('sitemap') }}">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    @foreach ($seo->allSchemas() as $schema)
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endforeach

    @if ($ga = setting('google_analytics_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga }}"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',@js($ga));</script>
    @endif
    @if ($pixel = setting('facebook_pixel_id'))
        <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init',@js($pixel));fbq('track','PageView');</script>
    @endif
    {!! setting('custom_head_code') !!}
    @stack('head')
</head>
<body class="flex min-h-full flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">Bỏ qua tới nội dung chính</a>

    @include('partials.header')

    <main id="main" class="flex-1 pb-20 lg:pb-0">
        @if ($seo->breadcrumbs && count($seo->breadcrumbs) > 1)
            <x-breadcrumbs :items="$seo->breadcrumbs" />
        @endif
        {{ $slot }}
    </main>

    @include('partials.footer')
    @include('partials.mobile-nav')
    @include('partials.floating-contact')

    {{-- Toast: nhận flash session lúc tải trang và event `toast` từ Livewire. --}}
    <div x-data="toaster(@js($flash))" class="pointer-events-none fixed inset-x-0 bottom-24 z-[60] flex flex-col items-center gap-2 px-4 lg:bottom-6 lg:items-end lg:pr-6" aria-live="polite">
        <template x-for="t in toasts" :key="t.id">
            <div class="animate-toast-in pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-2xl bg-white p-4 shadow-[var(--shadow-lift)] ring-1 ring-brand-900/10">
                <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full text-white"
                      :class="t.type === 'error' ? 'bg-red-500' : (t.type === 'warning' ? 'bg-accent-500' : 'bg-brand-600')">
                    <svg x-show="t.type !== 'error'" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    <svg x-show="t.type === 'error'" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </span>
                <p class="flex-1 text-sm font-medium text-ink" x-text="t.message"></p>
                <button type="button" @click="dismiss(t.id)" class="text-gray-400 hover:text-gray-600" aria-label="Đóng">&times;</button>
            </div>
        </template>
    </div>

    @livewireScripts
    {!! setting('custom_body_code') !!}
    @stack('scripts')
</body>
</html>
