<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

@php
    $pageTitle = filled($title ?? null) ? $title.' - '.config('app.name', 'StreamVault') : config('app.name', 'StreamVault');
    $ogTitle = $ogTitle ?? $title ?? config('app.name');
    $ogDescription = $ogDescription ?? 'Discover, track, and enjoy movies and TV shows on StreamVault — your open-source streaming companion.';
    $ogImage = $ogImage ?? null;
    $ogImageAlt = $ogImageAlt ?? $ogTitle;
    $ogUrl = $ogUrl ?? request()->url();
    $ogType = $ogType ?? 'website';
@endphp

<title>{{ $pageTitle }}</title>

<meta name="description" content="{{ $ogDescription }}">
<link rel="canonical" href="{{ $ogUrl }}">

<meta property="og:type" content="{{ $ogType }}">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDescription }}">
<meta property="og:url" content="{{ $ogUrl }}">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
@if($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:secure_url" content="{{ $ogImage }}">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:width" content="1280">
    <meta property="og:image:height" content="720">
    <meta property="og:image:alt" content="{{ $ogImageAlt }}">
@endif
<meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $ogTitle }}">
<meta name="twitter:description" content="{{ $ogDescription }}">
@if($ogImage)
    <meta name="twitter:image" content="{{ $ogImage }}">
    <meta name="twitter:image:alt" content="{{ $ogImageAlt }}">
@endif

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="icon" href="/favicon.png" type="image/png" sizes="512x512">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#d97706">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">

@if (session('toast'))
    <meta name="flash-toast" content="{{ json_encode(session('toast')) }}">
@endif

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
