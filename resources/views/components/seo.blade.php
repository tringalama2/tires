@props([
    'title'       => null,
    'description' => 'Track tire rotations and tread wear across all 5 positions. Know which corners wear fastest and when to replace — free for every vehicle.',
    'canonical'   => null,
    'noindex'     => false,
])
@php
    $siteName   = 'Tread·Mark';
    $tagline    = 'Every rotation, on the record.';
    $fullTitle  = $title ? $title.' — '.$siteName : $siteName.' — '.$tagline;
    $canonical  = $canonical ?? request()->url();
    $ogImage    = asset('assets/og-card.svg');
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
@if ($noindex)
    <meta name="robots" content="noindex, nofollow">
@else
    <meta name="robots" content="index, follow">
@endif
<link rel="canonical" href="{{ $canonical }}">

{{-- Open Graph --}}
<meta property="og:type"        content="website">
<meta property="og:site_name"   content="{{ $siteName }}">
<meta property="og:title"       content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url"         content="{{ $canonical }}">
<meta property="og:image"       content="{{ $ogImage }}">
<meta property="og:image:width"  content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt"   content="{{ $siteName }} logo">

{{-- Twitter Card --}}
<meta name="twitter:card"        content="summary">
<meta name="twitter:title"       content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image"       content="{{ $ogImage }}">

{{-- Favicon --}}
<link rel="icon" href="{{ asset('assets/favicon/favicon.ico') }}" sizes="any">
<link rel="icon" type="image/svg+xml" href="{{ asset('assets/favicon/favicon.svg') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/favicon/favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/favicon/favicon-16x16.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/favicon/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('assets/favicon/site.webmanifest') }}">
<meta name="theme-color" content="#0F1410">
