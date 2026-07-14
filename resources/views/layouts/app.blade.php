@php
    $brand = $workliveBrand ?? null;
    $hex = fn ($value, $fallback) => is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? $value : $fallback;
    $brandName = filled($brand?->brand_name) ? $brand->brand_name : 'WorkLive Pro';
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" referrerpolicy="no-referrer">
    @if(!empty($brand?->brand_icon_path))<link rel="icon" href="{{ route('branding.icon') }}?v={{ strtotime((string) ($brand?->updated_at ?? '')) ?: time() }}">@endif
    <style>:root{--wl-primary:{{ $hex($brand?->color_primary,'#4f46e5') }};--wl-secondary:{{ $hex($brand?->color_secondary,'#312e81') }};--wl-accent:{{ $hex($brand?->color_accent,'#06b6d4') }};--wl-sidebar:{{ $hex($brand?->color_sidebar,'#0f172a') }};--wl-sidebar-text:{{ $hex($brand?->color_sidebar_text,'#cbd5e1') }};--wl-page:{{ $hex($brand?->color_page,'#f8fafc') }};--wl-surface:{{ $hex($brand?->color_surface,'#ffffff') }};--wl-text:{{ $hex($brand?->color_text,'#0f172a') }};--wl-logo-background:{{ $hex($brand?->color_logo_background,'#4f46e5') }};}</style>
    @vite('resources/css/app.css')
    <title>{{ isset($title) ? $title.' · '.$brandName : $brandName }}</title>
</head>
<body>@yield('body')</body>
</html>
