<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
        <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --beige-50:  #FDFAF5;
                --beige-100: #F7F2E8;
                --beige-200: #EDE4D0;
                --beige-300: #DDD0B8;
                --charcoal:  #1C1916;
                --charcoal-2:#2E2A25;
                --charcoal-3:#5C5449;
                --gold:      #C9A84C;
                --gold-light:#E0C278;
                --gold-dark: #9B7A2A;
            }
            body {
                font-family: 'Inter', sans-serif;
                background-color: var(--beige-50);
            }
            .font-display { font-family: 'Cormorant Garamond', serif; }
            .guest-card {
                background: #FFFFFF;
                border: 1px solid var(--beige-200);
                border-radius: 2px;
                box-shadow:
                    0 2px 4px rgba(28,25,22,0.04),
                    0 8px 24px rgba(28,25,22,0.08),
                    0 24px 56px rgba(28,25,22,0.06),
                    inset 0 1px 0 rgba(255,255,255,0.95);
            }
            .gold-rule {
                height: 1px;
                background: linear-gradient(90deg, transparent, var(--gold), transparent);
            }
            .guest-bg {
                background-color: var(--beige-50);
                background-image:
                    radial-gradient(ellipse 70% 50% at 50% 0%, rgba(201,168,76,0.07) 0%, transparent 70%),
                    radial-gradient(ellipse 40% 40% at 10% 80%, rgba(201,168,76,0.04) 0%, transparent 60%);
            }
        </style>
    </head>
    <body class="antialiased" style="color: var(--charcoal);">
        <div class="guest-bg min-h-screen flex flex-col sm:justify-center items-center px-4 py-12 relative">

            {{-- Corner ornaments --}}
            <div class="absolute top-6 left-6 w-8 h-8 border-t border-l opacity-25" style="border-color: var(--gold)"></div>
            <div class="absolute top-6 right-6 w-8 h-8 border-t border-r opacity-25" style="border-color: var(--gold)"></div>
            <div class="absolute bottom-6 left-6 w-8 h-8 border-b border-l opacity-25" style="border-color: var(--gold)"></div>
            <div class="absolute bottom-6 right-6 w-8 h-8 border-b border-r opacity-25" style="border-color: var(--gold)"></div>

            {{-- Logotype --}}
            <div class="mb-8 flex flex-col items-center gap-3">
                <a href="/" class="flex flex-col items-center gap-2 no-underline" style="text-decoration:none;">
                    <div class="font-display" style="font-size: clamp(2.2rem, 7vw, 3.4rem); font-weight: 300; letter-spacing: 0.14em; color: var(--charcoal); line-height: 1; text-transform: uppercase;">
                        Analytica
                    </div>
                    <div style="font-size: 0.52rem; letter-spacing: 0.42em; color: var(--gold); text-transform: uppercase; font-weight: 600; font-family: 'Inter', sans-serif; opacity: 0.85;">
                        Intelligence Financière
                    </div>
                </a>
                <div class="gold-rule w-16 mt-1"></div>
            </div>

            {{-- Card --}}
            <div class="guest-card w-full sm:max-w-md overflow-hidden">
                <div style="height:3px;background:linear-gradient(90deg,var(--gold-dark),var(--gold-light),var(--gold-dark))"></div>
                <div class="px-8 py-8">
                    {{ $slot }}
                </div>
            </div>

            {{-- Back link --}}
            <a href="/" class="mt-8" style="font-size:0.65rem;letter-spacing:0.2em;text-transform:uppercase;color:var(--charcoal-3);opacity:0.6;transition:opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'">
                ← Retour à l'accueil
            </a>
        </div>
    </body>
</html>
