<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analytica — Intelligence Financière</title>
        <link rel="icon" type="image/png" href="{{ asset('analytica-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
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
        body { background-color: var(--beige-50); color: var(--charcoal); font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Cormorant Garamond', serif; }

        /* 3D card */
        .luxury-card {
            background: #FFFFFF;
            border: 1px solid var(--beige-200);
            border-radius: 2px;
            box-shadow:
                0 2px 4px rgba(28,25,22,0.04),
                0 8px 20px rgba(28,25,22,0.07),
                0 20px 48px rgba(28,25,22,0.06),
                inset 0 1px 0 rgba(255,255,255,0.9);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .luxury-card:hover {
            transform: translateY(-4px) scale(1.005);
            box-shadow:
                0 4px 8px rgba(28,25,22,0.06),
                0 16px 40px rgba(28,25,22,0.11),
                0 32px 64px rgba(28,25,22,0.08),
                inset 0 1px 0 rgba(255,255,255,0.9);
        }
        /* Gold divider */
        .gold-rule {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }
        /* Gold top accent */
        .gold-top::before {
            content: '';
            display: block;
            height: 3px;
            background: linear-gradient(90deg, var(--gold-dark), var(--gold-light), var(--gold-dark));
            border-radius: 2px 2px 0 0;
        }
        /* Buttons */
        .btn-primary {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 14px 40px;
            background: linear-gradient(135deg, var(--charcoal-2), var(--charcoal));
            color: var(--gold-light);
            font-family: 'Inter', sans-serif;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            border: 1px solid rgba(201,168,76,0.3);
            border-radius: 2px;
            box-shadow: 0 4px 16px rgba(28,25,22,0.25), inset 0 1px 0 rgba(255,255,255,0.06);
            transition: all 0.25s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #3D3830, #2E2A25);
            color: var(--gold);
            box-shadow: 0 6px 24px rgba(28,25,22,0.35), inset 0 1px 0 rgba(255,255,255,0.08);
            transform: translateY(-1px);
        }
        .btn-secondary {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 13px 40px;
            background: transparent;
            color: var(--charcoal-2);
            font-family: 'Inter', sans-serif;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            border: 1px solid var(--beige-300);
            border-radius: 2px;
            box-shadow: 0 2px 8px rgba(28,25,22,0.06);
            transition: all 0.25s ease;
        }
        .btn-secondary:hover {
            border-color: var(--gold);
            color: var(--gold-dark);
            box-shadow: 0 4px 16px rgba(201,168,76,0.15);
            transform: translateY(-1px);
        }
        /* Hero background texture */
        .hero-bg {
            background-color: var(--beige-50);
            background-image:
                radial-gradient(ellipse 80% 50% at 50% -10%, rgba(201,168,76,0.08) 0%, transparent 70%),
                radial-gradient(ellipse 40% 40% at 90% 80%, rgba(201,168,76,0.05) 0%, transparent 60%);
        }
        /* Feature icon circle */
        .icon-circle {
            width: 52px; height: 52px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--beige-100), var(--beige-200));
            border: 1px solid rgba(201,168,76,0.25);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 8px rgba(28,25,22,0.08), inset 0 1px 0 rgba(255,255,255,0.8);
        }
        /* Footer strip */
        .footer-strip {
            background: var(--charcoal);
            color: rgba(253,250,245,0.4);
        }
        /* Monogram ring */

    </style>
</head>
<body class="antialiased min-h-screen">

{{-- ===== HERO ===== --}}
<section class="hero-bg min-h-screen flex flex-col items-center justify-center px-6 py-20 relative overflow-hidden">

    {{-- Corner ornaments --}}
    <div class="absolute top-8 left-8 w-12 h-12 border-t border-l opacity-20" style="border-color: var(--gold)"></div>
    <div class="absolute top-8 right-8 w-12 h-12 border-t border-r opacity-20" style="border-color: var(--gold)"></div>
    <div class="absolute bottom-8 left-8 w-12 h-12 border-b border-l opacity-20" style="border-color: var(--gold)"></div>
    <div class="absolute bottom-8 right-8 w-12 h-12 border-b border-r opacity-20" style="border-color: var(--gold)"></div>

    {{-- Logotype --}}
    <div class="mb-2 flex flex-col items-center" style="text-align:center;">
        <div class="font-display" style="font-size: clamp(3.5rem, 11vw, 7rem); font-weight: 300; letter-spacing: 0.12em; color: var(--charcoal); line-height: 1; text-transform: uppercase;">
            Analytica
        </div>
        <div style="font-size: 0.58rem; letter-spacing: 0.45em; color: var(--gold); text-transform: uppercase; font-weight: 600; font-family: 'Inter', sans-serif; margin-top: 0.6rem; opacity: 0.9;">
            Intelligence Financière
        </div>
    </div>

    {{-- Gold rule --}}
    <div class="gold-rule w-24 my-6"></div>

    {{-- Subtitle --}}
    <p class="text-center max-w-lg" style="font-size: 0.95rem; font-weight: 300; color: var(--charcoal-3); line-height: 1.85; letter-spacing: 0.03em;">
        Analyse intelligente de relevés bancaires<br>
        et génération de rapports financiers neutres.
    </p>

    {{-- CTAs --}}
    <div class="flex flex-col sm:flex-row items-center gap-4 mt-12">
        <a href="{{ route('register') }}" class="btn-primary">
            Créer un compte
        </a>
        <a href="{{ route('login') }}" class="btn-secondary">
            Se connecter
        </a>
    </div>

    {{-- Scroll hint --}}
    <div class="absolute bottom-10 flex flex-col items-center gap-2 opacity-40">
        <span style="font-size:0.65rem; letter-spacing:0.2em; color: var(--charcoal-3); text-transform: uppercase;">Découvrir</span>
        <div class="w-px h-8" style="background: linear-gradient(to bottom, var(--gold), transparent)"></div>
    </div>
</section>

{{-- ===== FEATURES ===== --}}
<section class="px-6 py-24" style="background: var(--beige-100);">
    <div class="max-w-5xl mx-auto">

        {{-- Section header --}}
        <div class="text-center mb-16">
            <p style="font-size:0.65rem; letter-spacing:0.25em; color: var(--gold); text-transform:uppercase; font-weight:600;">Fonctionnalités</p>
            <h2 class="font-display mt-3" style="font-size: clamp(1.8rem, 4vw, 3rem); font-weight: 300; color: var(--charcoal);">
                Les trois piliers de l'analyse
            </h2>
            <div class="gold-rule w-16 mx-auto mt-5"></div>
        </div>

        {{-- Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            {{-- Import --}}
            <div class="luxury-card gold-top p-8">
                <div class="icon-circle mb-6">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke-width="1.5" style="stroke: var(--gold-dark)">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                    </svg>
                </div>
                <p style="font-size:0.6rem; letter-spacing:0.25em; color:var(--gold); text-transform:uppercase; font-weight:700; margin-bottom:0.75rem;">I. Import</p>
                <h3 class="font-display" style="font-size:1.5rem; font-weight:400; color:var(--charcoal); margin-bottom:0.75rem;">Relevés & Doublons</h3>
                <div class="gold-rule w-8 mb-4"></div>
                <p style="font-size:0.875rem; color:var(--charcoal-3); line-height:1.8; font-weight:300;">
                    Import de relevés PDF et CSV. Détection automatique des doublons et normalisation des flux.
                </p>
            </div>

            {{-- Analyse --}}
            <div class="luxury-card gold-top p-8" style="margin-top: 1.5rem;">
                <div class="icon-circle mb-6">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke-width="1.5" style="stroke: var(--gold-dark)">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                    </svg>
                </div>
                <p style="font-size:0.6rem; letter-spacing:0.25em; color:var(--gold); text-transform:uppercase; font-weight:700; margin-bottom:0.75rem;">II. Analyse</p>
                <h3 class="font-display" style="font-size:1.5rem; font-weight:400; color:var(--charcoal); margin-bottom:0.75rem;">Score Dossier</h3>
                <div class="gold-rule w-8 mb-4"></div>
                <p style="font-size:0.875rem; color:var(--charcoal-3); line-height:1.8; font-weight:300;">
                    Règles pondérées R1–R6 appliquées sur chaque dossier. Score de fiabilité calculé en temps réel.
                </p>
            </div>

            {{-- Rapport --}}
            <div class="luxury-card gold-top p-8">
                <div class="icon-circle mb-6">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke-width="1.5" style="stroke: var(--gold-dark)">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                </div>
                <p style="font-size:0.6rem; letter-spacing:0.25em; color:var(--gold); text-transform:uppercase; font-weight:700; margin-bottom:0.75rem;">III. Rapport</p>
                <h3 class="font-display" style="font-size:1.5rem; font-weight:400; color:var(--charcoal); margin-bottom:0.75rem;">PDF Neutre</h3>
                <div class="gold-rule w-8 mb-4"></div>
                <p style="font-size:0.875rem; color:var(--charcoal-3); line-height:1.8; font-weight:300;">
                    Génération de rapports PDF neutres, chiffrés et stockés en toute sécurité dans le cloud.
                </p>
            </div>

        </div>
    </div>
</section>

{{-- ===== CTA BAND ===== --}}
<section class="px-6 py-20 text-center" style="background: var(--charcoal);">
    <div class="max-w-2xl mx-auto">
        <div class="gold-rule w-12 mx-auto mb-8"></div>
        <h2 class="font-display" style="font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight:300; color: var(--beige-50);">
            Commencez votre analyse dès aujourd'hui
        </h2>
        <p class="mt-4" style="font-size:0.875rem; color: rgba(253,250,245,0.5); font-weight:300; letter-spacing:0.04em; line-height:1.8;">
            Accès sécurisé — Données chiffrées — Rapports neutres
        </p>
        <div class="gold-rule w-12 mx-auto my-8"></div>
        <a href="{{ route('register') }}" class="btn-primary" style="background: linear-gradient(135deg, var(--gold-dark), var(--gold)); color: var(--charcoal); border-color: transparent; box-shadow: 0 4px 24px rgba(201,168,76,0.35);">
            Créer un compte gratuitement
        </a>
    </div>
</section>

{{-- ===== FOOTER ===== --}}
<footer class="footer-strip px-6 py-6 text-center">
    <p style="font-size:0.65rem; letter-spacing:0.15em;">
        © {{ date('Y') }} ANALYTICA &nbsp;·&nbsp; Intelligence Financière
    </p>
</footer>

</body>
</html>
