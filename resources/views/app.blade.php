<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script>
            (function() {
                var c = document.cookie.match(/\btheme=([^;]*)/);
                var theme = c ? decodeURIComponent(c[1]) : 'auto';
                var isDark = theme === 'dark' || (theme === 'auto' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (isDark) document.documentElement.classList.add('dark');
                else document.documentElement.classList.remove('dark');
            })();
        </script>
        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts'])
        @inertiaHead
        @php
                    $brand = app(\App\Services\BrandingService::class);
                @endphp
                <style>
                            :root {
                                --brand-primary: {{ $brand->getPrimaryColor() }};
                            }
                        </style>
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
