<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Laravel Admin') }} | Laravel AdminLTE 4</title>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/inertia.js'])
    @inertiaHead

    <script>
        // Apply the stored theme before first paint to avoid a flash of the
        // wrong color scheme (same behavior as the former Blade layouts).
        (() => {
            const stored = localStorage.getItem('theme');
            const preferred = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', preferred === 'auto'
                ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : preferred);
        })();
    </script>
</head>
<body>
    @inertia
</body>
</html>
