<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Analytica') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('analytica-logo.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('analytica-logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @stack('styles')
    </head>
    <body class="font-sans antialiased" style="background-color:#FDFAF5;color:#1C1916;">
        <div class="min-h-screen" style="background-color:#FDFAF5;">
            @include('layouts.navigation')

            <!-- En-tête -->
            @isset($header)
                <header style="background:#FFFFFF;border-bottom:1px solid #EDE4D0;box-shadow:0 2px 12px rgba(28,25,22,0.06);">
                    <div class="max-w-[112rem] mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Contenu -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
