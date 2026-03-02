<x-app-layout>

@push('styles')
<style>
/* ===== SHOW PAGE — LUXURY THEME OVERRIDES ===== */

/* ---- Main Bloc sections ---- */
.py-12 > .max-w-\[112rem\] > section {
    background: #FFFFFF !important;
    border: 1px solid #EDE4D0 !important;
    border-radius: 2px !important;
    box-shadow: 0 2px 4px rgba(28,25,22,0.04), 0 8px 20px rgba(28,25,22,0.07), 0 20px 48px rgba(28,25,22,0.05) !important;
    position: relative;
    overflow: hidden;
}
/* Gold top bar on each bloc */
.py-12 > .max-w-\[112rem\] > section::before {
    content: '';
    display: block;
    height: 3px;
    background: linear-gradient(90deg, #9B7A2A, #E0C278, #9B7A2A);
    position: absolute;
    top: 0; left: 0; right: 0;
}

/* ---- Bloc labels (amber-700) → gold ---- */
.text-amber-700  { color: #C9A84C !important; }
.text-amber-800  { color: #9B7A2A !important; }

/* ---- Sub-card radius ---- */
.rounded-xl { border-radius: 4px !important; }
.rounded-lg { border-radius: 3px !important; }
.sm\:rounded-lg { border-radius: 4px !important; }
.rounded-md { border-radius: 3px !important; }
.rounded-full { border-radius: 999px !important; }

/* ---- Amber/green border neutralisation ---- */
.border-amber-200 { border-color: #EDE4D0 !important; }
.from-amber-50, .via-slate-50, .to-green-50,
.bg-amber-50                         { background-color: #F7F2E8 !important; }

/* ---- Green → warm beige palette ---- */
.border-green-200, .border-green-100 { border-color: #E5DCC8 !important; }
.bg-green-50                         { background-color: #F7F2E8 !important; }
.text-green-800                      { color: #5C5449 !important; }
.text-green-700                      { color: #C9A84C !important; }
.bg-gradient-to-r                    { background: #FFFFFF !important; }

/* ---- Slate/gray borders → beige ---- */
.border-slate-200, .border-slate-100 { border-color: #EDE4D0 !important; }
.border-slate-300                    { border-color: #DDD0B8 !important; }
.border-gray-200                     { border-color: #EDE4D0 !important; }
.border-gray-300                     { border-color: #DDD0B8 !important; }

/* ---- Background neutrals ---- */
.bg-gray-50, .bg-slate-50   { background-color: #F7F2E8 !important; }
.bg-gray-100, .bg-slate-100 { background-color: #EDE4D0 !important; }

/* ---- Red exceptional block → very subtle ---- */
.bg-red-50  { background-color: #FEF8F6 !important; }
.border-red-100 { border-color: #F0DDD8 !important; }

/* ---- Text colors ---- */
.text-slate-800, .text-gray-900 { color: #1C1916 !important; }
.text-slate-700, .text-gray-800, .text-gray-700 { color: #2E2A25 !important; }
.text-slate-600, .text-gray-600, .text-slate-500, .text-gray-500 { color: #5C5449 !important; }

/* ---- Orange anomaly text — keep warm ---- */
.text-orange-700 { color: #9B7A2A !important; }

/* ---- Checkboxes (remove default blue accent) ---- */
.print-section-checkbox,
.card-visibility-checkbox {
    accent-color: #C9A84C;
}

/* ---- Score breakdown + AI card ---- */
.bg-white.border.border-amber-200.rounded-xl,
.bg-white.border.border-green-200.rounded-xl {
    border-color: #EDE4D0 !important;
}

/* ---- Action buttons: green → luxury charcoal+gold ---- */
.bg-green-600 {
    background: linear-gradient(135deg, #2E2A25, #1C1916) !important;
    border-color: rgba(201,168,76,0.35) !important;
    color: #E0C278 !important;
    border-radius: 2px !important;
    letter-spacing: 0.1em !important;
}
.hover\:bg-green-500:hover { background: linear-gradient(135deg, #3D3830, #2E2A25) !important; color: #C9A84C !important; }

/* ---- Export / slate-700 button ---- */
.bg-slate-700 {
    background: linear-gradient(135deg, #2E2A25, #1C1916) !important;
    color: #E0C278 !important;
    border-radius: 2px !important;
}
.hover\:bg-slate-600:hover { background: #2E2A25 !important; }

/* ---- Print / gray-900 button ---- */
.bg-gray-900 {
    background: linear-gradient(135deg, #2E2A25, #1C1916) !important;
    color: #E0C278 !important;
    border-radius: 2px !important;
}
.hover\:bg-gray-800:hover { background: #2E2A25 !important; }

/* ---- Generic white bordered buttons ---- */
button.border-gray-300, a.border-gray-300, button.border-slate-300, a.border-slate-300 {
    border-color: #DDD0B8 !important;
    color: #2E2A25 !important;
}
button.border-gray-300:hover, button.border-slate-300:hover { border-color: #C9A84C !important; color: #9B7A2A !important; }

/* ---- Hover: bg-slate/gray-50 → beige ---- */
.hover\:bg-slate-50:hover, .hover\:bg-gray-50:hover { background-color: #F7F2E8 !important; }
.hover\:bg-slate-100:hover, .hover\:bg-gray-100:hover { background-color: #EDE4D0 !important; }

/* ---- Bloc 3 toggle section (big white card) ---- */
.bg-white.overflow-hidden.shadow-sm {
    background-color: #FDFAF5 !important;
}

/* ---- Inner Bloc 3 cards ---- */
.toggle-card-section {
    background: #FFFFFF !important;
    border-color: #EDE4D0 !important;
    border-radius: 3px !important;
}

/* ---- Alert messages ---- */
.text-green-700 { color: #C9A84C !important; }
.text-red-700   { color: #9B3030 !important; }
.text-amber-700 { color: #C9A84C !important; }

/* ---- Score bar / exception input ---- */
input[type="number"].border-gray-300,
input[type="number"].focus\:border-green-500 {
    border-color: #DDD0B8 !important;
    border-radius: 2px !important;
}
input[type="number"]:focus {
    border-color: #C9A84C !important;
    box-shadow: 0 0 0 2px rgba(201,168,76,0.15) !important;
}

/* ---- Pagination / table links ---- */
a.text-blue-700, button.text-blue-700 { color: #9B7A2A !important; }
a.text-blue-700:hover { color: #C9A84C !important; }

/* ---- Dividers ---- */
.divide-y > * + * { border-color: #EDE4D0 !important; }
.border-l-4.border-slate-300 { border-color: #DDD0B8 !important; }
.border-t.border-slate-100 { border-color: #EDE4D0 !important; }

/* ---- Score gauge ---- */
.score-gauge-track {
    height: 8px;
    background: #EDE4D0;
    border-radius: 999px;
    overflow: hidden;
    margin-top: 8px;
}
.score-gauge-fill {
    height: 100%;
    border-radius: 999px;
    transition: width 0.6s ease;
}

/* ---- Mini AI highlights ---- */
.ai-mini-highlight {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 6px 10px;
    background: #F7F2E8;
    border-left: 3px solid #C9A84C;
    border-radius: 0 3px 3px 0;
    font-size: 0.75rem;
    color: #2E2A25;
    line-height: 1.4;
}
.ai-mini-highlight::before {
    content: '▸';
    color: #C9A84C;
    font-size: 0.65rem;
    margin-top: 1px;
    flex-shrink: 0;
}

/* ---- Coherence badge ---- */
.coherence-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 2px;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}
</style>
@endpush

    <x-slot name="header">
        @php
            $statusLabel = match ((string) ($case->status ?? '')) {
                'draft' => 'Brouillon',
                'in_progress' => 'En cours',
                'completed' => 'Terminé',
                'archived' => 'Archivé',
                default => (string) ($case->status ?? '—'),
            };
            $scoreValue = is_null($case->global_score) ? null : (int) $case->global_score;
            $scoreLevelLabel = is_null($scoreValue) ? 'Non calculé' : ($scoreValue >= 60 ? 'Fortement atypique' : ($scoreValue >= 30 ? 'Atypique' : 'Normal'));
            // Badge niveau de risque — palette luxury (pas de Tailwind vert/rouge cru)
            $scoreLevelClass = 'badge';
            if (is_null($scoreValue))        { $scoreLevelClass .= ' badge-neutral'; }
            elseif ($scoreValue >= 60) { $scoreLevelClass .= ' badge-critical'; }
            elseif ($scoreValue >= 30) { $scoreLevelClass .= ' badge-high'; }
            else                              { $scoreLevelClass .= ' badge-low'; }
            $scoreLevelStyle = ''; // géré par les classes CSS
        @endphp
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <h2 style="font-family:'Cormorant Garamond',serif;font-weight:400;font-size:1.8rem;color:#1C1916;line-height:1.1;">
                    {{ $case->title }}
                </h2>
                <p class="mt-1 text-base" style="color:#5C5449;">Statut : {{ $statusLabel }} &nbsp;·&nbsp; Score : {{ $case->global_score ?? '—' }}</p>
                <div class="mt-2 {{ $scoreLevelClass }}" style="{{ $scoreLevelStyle }}">
                    Niveau : {{ $scoreLevelLabel }}
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-[112rem] mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-4 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                </div>
            @endif

            @if (session('analysis_error'))
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-4 text-sm text-red-700">
                        {{ session('analysis_error') }}
                    </div>
                </div>
            @endif

            @if (session('analysis_warning'))
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-4 text-sm text-amber-700">
                        {{ session('analysis_warning') }}
                    </div>
                </div>
            @endif

            @php
                // ── Label normalisation helper (OCR cleanup) ──────────────────
                function cleanBankLabel(string $raw): string {
                    // Normalize case (title case, keep acronyms)
                    $s = mb_convert_case(mb_strtolower($raw), MB_CASE_TITLE, 'UTF-8');
                    // Remove common OCR/SEPA noise patterns
                    $s = preg_replace('/\b(VIR|ViR|VIR\s+SEPA\s+REC[UÇ]?)\b/iu', 'Virement reçu –', $s);
                    $s = preg_replace('/\b(VIR\s+SEPA\s+EMIS)\b/iu', 'Virement émis –', $s);
                    $s = preg_replace('/\b(CHEQUE|CHQ)\b/iu', 'Chèque', $s);
                    $s = preg_replace('/\b(PRLV\s+SEPA|PRELEVEMENT)\b/iu', 'Prélèvement –', $s);
                    $s = preg_replace('/\b(CB|PAIEMENT\s+CB)\b/iu', 'Paiement carte –', $s);
                    // Remove SEPA technical fields: /DE, /MOTF, /MOT:F, /REF, /RFA, etc.
                    $s = preg_replace('/\s*\/(DE|MOTF?|MOT:F?|REFDO?|RFA|BIC|IBAN|PR:X|VER|REF|Z\s*\d{3})\s+/iu', ' ', $s);
                    // Remove stray special chars and repeated spaces
                    $s = preg_replace('/[\|\*\^]/', ' ', $s);
                    $s = preg_replace('/\s{2,}/', ' ', $s);
                    // Remove trailing date artefacts like "26.02" or "16.02"
                    $s = preg_replace('/\s+\d{2}\.\d{2}\b/', '', $s);
                    return trim($s);
                }

                $latestAnalysis = $latest_analysis_result ?? null;
                $lastAi = $last_ai ?? [];
                $ai = session('ai_result', $lastAi['result'] ?? null);
                $aiError = session('ai_error', $lastAi['error'] ?? null);
                if ((bool) config('analytica.ai.enabled', false) && is_string($aiError) && str_contains($aiError, 'ANALYTICA_AI_ENABLED=false')) {
                    $aiError = null;
                }
                $behavioral = $behavioral ?? [];
                $monthlyTotals = collect($behavioral['monthly_totals'] ?? []);
                $sensitive = $behavioral['sensitive_stats'] ?? null;
                $topCriticalMonths = $monthlyTotals
                    ->filter(fn ($row) => !empty($row['credit_anomaly']) || !empty($row['debit_anomaly']))
                    ->sortByDesc(fn ($row) => max(abs((float)($row['credit_z'] ?? 0)), abs((float)($row['debit_z'] ?? 0))))
                    ->take(5)
                    ->values();
                $criticalExceptional = collect($exceptional_transactions ?? [])->sortByDesc(fn ($tx) => abs((float)($tx->amount ?? 0)))->take(5)->values();
                $criticalBeneficiaries = collect($beneficiary_concentration ?? [])->take(3)->values();
                $aiLines = collect((array)($ai['suspicious'] ?? []))->filter(fn ($v) => is_string($v) && trim($v) !== '')->take(3)->values();
                if ($aiLines->count() < 3) {
                    if ($topCriticalMonths->count() > 0) {
                        $aiLines->push('Le dossier présente '.$topCriticalMonths->count().' pic(s) significatif(s) sur la période analysée.');
                    }
                    if ($sensitive && !is_null($sensitive['change_pct'] ?? null)) {
                        $aiLines->push('Une variation notable des débits est observée dans la période sensible ('.(($sensitive['change_pct'] ?? 0) >= 0 ? '+' : '').($sensitive['change_pct'] ?? 0).'%).');
                    }
                    $aiLines->push('Aucun transfert massif isolé postérieur à la date clé n\'est détecté automatiquement.');
                    $aiLines = $aiLines->take(3)->values();
                }

                // ── Mini highlights (3 bullets for quick read) ─────────────────
                $miniHighlights = collect();
                $excCount = collect($exceptional_transactions ?? [])->filter(fn($t) => abs((float)($t->amount ?? 0)) >= ($exceptional_threshold ?? 20000))->count();
                if ($excCount > 0) {
                    $miniHighlights->push($excCount.' flux exceptionnel'.($excCount > 1 ? 's' : '').' ≥ '.number_format((float)($exceptional_threshold ?? 20000), 0, ',', ' ').' €');
                }
                if ($topCriticalMonths->count() > 0) {
                    $miniHighlights->push($topCriticalMonths->count().' pic'.($topCriticalMonths->count() > 1 ? 's' : '').' mensuel'.($topCriticalMonths->count() > 1 ? 's' : '').' atypique'.($topCriticalMonths->count() > 1 ? 's' : ''));
                }
                if ($criticalBeneficiaries->isNotEmpty()) {
                    $topB = $criticalBeneficiaries->first();
                    $topBShare = number_format((float)($topB['share_pct'] ?? 0), 1, ',', ' ');
                    $topBName = mb_convert_case($topB['beneficiary'] ?? 'Bénéficiaire inconnu', MB_CASE_TITLE, 'UTF-8');
                    $miniHighlights->push('Concentration : '.($topBName).' ('.($topBShare).'% des débits)');
                }
                if ($miniHighlights->isEmpty()) {
                    $miniHighlights->push('Aucune anomalie critique détectée sur la période.');
                }

                // ── Economic coherence level ────────────────────────────────────
                $ecoScore = (float)($economic_coherence['coherence_score'] ?? -1);
                $ecoNarrative = (string)($economic_coherence['narrative'] ?? '');
                if ($ecoScore >= 75) {
                    $cohLabel = 'Forte cohérence'; $cohBadgeClass = 'badge badge-coherent'; $cohDot = '●';
                } elseif ($ecoScore >= 50) {
                    $cohLabel = 'Cohérence partielle'; $cohBadgeClass = 'badge badge-partial'; $cohDot = '◐';
                } elseif ($ecoScore >= 25) {
                    $cohLabel = 'Cohérence faible'; $cohBadgeClass = 'badge badge-weak'; $cohDot = '◑';
                } else {
                    $wordCount = str_word_count($ecoNarrative);
                    if ($wordCount > 30) {
                        $cohLabel = 'Cohérence partielle'; $cohBadgeClass = 'badge badge-partial'; $cohDot = '◐';
                    } else {
                        $cohLabel = 'Non évalué'; $cohBadgeClass = 'badge badge-unrated'; $cohDot = '○';
                    }
                }
            @endphp

            <section class="bg-white" style="border:1px solid var(--beige-200);border-radius:2px;padding:1.5rem;" x-data="{ scoreOpen: false }">
                <div class="flex flex-col xl:flex-row gap-6 xl:items-start xl:justify-between">
                    <div class="flex-1">
                        <div class="section-label">Bloc 1 — Diagnostic</div>
                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            {{-- Score + visual gauge --}}
                            <div class="stat-card">
                                <div class="stat-card-label">Score de risque</div>
                                <button type="button" @click="scoreOpen = !scoreOpen"
                                    style="font-family:'Cormorant Garamond',serif;font-size:2.4rem;font-weight:500;color:#1C1916;line-height:1;cursor:pointer;background:none;border:none;padding:0;"
                                    onmouseover="this.style.color='#9B7A2A'" onmouseout="this.style.color='#1C1916'">{{ $case->global_score ?? '—' }}</button>
                                @if (!is_null($scoreValue))
                                    @php
                                        $gaugeColor = $scoreValue >= 75 ? '#C0392B' : ($scoreValue >= 50 ? '#C47D1E' : ($scoreValue >= 25 ? '#B8860B' : '#4A7C59'));
                                        $gaugeWidth = min(100, max(2, $scoreValue));
                                    @endphp
                                    <div class="score-gauge-track">
                                        <div class="score-gauge-fill" style="width:{{ $gaugeWidth }}%;background:{{ $gaugeColor }};"></div>
                                    </div>
                                    <div class="mt-1 flex justify-between" style="font-size:0.58rem;color:#8A7E72;">
                                        <span>0 Conforme</span>
                                        <span>50</span>
                                        <span>100 Critique</span>
                                    </div>
                                @endif
                                <div class="stat-card-sub" style="font-style:italic;">Cliquer pour détail</div>
                            </div>
                            {{-- Niveau --}}
                            <div class="stat-card">
                                <div class="stat-card-label">Niveau de risque</div>
                                @php
                                    // SVG dot — pas d'emoji
                                    $dotColor = is_null($scoreValue) ? '#8A7E72' : ($scoreValue >= 75 ? '#C0392B' : ($scoreValue >= 50 ? '#C47D1E' : ($scoreValue >= 25 ? '#B8860B' : '#4A7C59')));
                                    $levelDotSvg = '<svg width="9" height="9" viewBox="0 0 9 9" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline;vertical-align:middle;margin-right:5px;"><circle cx="4.5" cy="4.5" r="4.5" fill="'.e($dotColor).'"/></svg>';
                                @endphp
                                <div class="stat-card-value" style="font-size:1.15rem;margin-top:0.3rem;">{!! $levelDotSvg !!}{{ $scoreLevelLabel }}</div>
                                <div class="stat-card-sub">
                                    @if (!is_null($scoreValue))
                                        {{ $scoreValue < 25 ? '0–25 : Conforme' : ($scoreValue < 50 ? '25–50 : Vigilance' : ($scoreValue < 75 ? '50–75 : Atypique' : '75–100 : Critique')) }}
                                    @else
                                        Non calculé
                                    @endif
                                </div>
                            </div>
                            {{-- Transactions signalées --}}
                            <div class="stat-card">
                                <div class="stat-card-label">Transactions signalées</div>
                                <div class="stat-card-value">{{ (int)($stats['total_flagged'] ?? 0) }}</div>
                                <div class="stat-card-sub">
                                    @if (!empty($stats['total_transactions']))
                                        sur {{ (int)$stats['total_transactions'] }} analysées
                                        ({{ round(($stats['total_flagged'] ?? 0) / max(1, $stats['total_transactions']) * 100, 1) }}%)
                                    @else
                                        transactions marquées
                                    @endif
                                </div>
                            </div>
                            {{-- Variation période sensible --}}
                            <div class="stat-card" style="border-left:2px solid var(--gold);">
                                <div class="stat-card-label">Variation période sensible</div>
                                <div class="stat-card-value" style="color:var(--charcoal-2);">
                                    @if (!is_null($sensitive['change_pct'] ?? null))
                                        {{ (($sensitive['change_pct'] ?? 0) >= 0 ? '+' : '') }}{{ $sensitive['change_pct'] ?? 0 }}%
                                    @else
                                        —
                                    @endif
                                </div>
                                @if (!empty($sensitive['window_label']))
                                    <div class="stat-card-sub">{{ $sensitive['window_label'] }}</div>
                                @endif
                            </div>
                        </div>
                        <div x-show="scoreOpen" x-cloak class="mt-3 p-4 bg-white" style="border:1px solid var(--beige-200);border-radius:2px;">
                            <div class="section-label" style="margin-bottom:0.6rem;">Score explicatif dynamique</div>
                            <div style="font-size:0.78rem;color:#5C5449;margin-bottom:0.5rem;">Total : {{ (int)($score_breakdown['total'] ?? 0) }} points</div>
                            <ul class="space-y-1" style="font-size:0.8rem;color:#2E2A25;padding-left:1rem;">
                                @foreach ((array)($score_breakdown['items'] ?? []) as $item)
                                    <li style="list-style:none;padding-left:0.8rem;text-indent:-0.8rem;">– {{ (int)($item['points'] ?? 0) }} pts &nbsp;{{ (string)($item['label'] ?? '') }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="xl:w-[30rem]">
                        <div class="p-luxury" style="background:#FFF;border:1px solid var(--beige-200);border-radius:2px;">
                            <div class="section-label" style="margin-bottom:0.8rem;">Synthèse du dossier</div>
                            @if ($aiError)
                                <div class="mt-2 text-xs" style="color:#9B3030;">{{ $aiError }}</div>
                            @endif
                            {{-- Mini highlights: 3 points clés --}}
                            <div class="mt-3 space-y-2">
                                @foreach ($miniHighlights as $hl)
                                    <div class="ai-mini-highlight">{{ $hl }}</div>
                                @endforeach
                            </div>
                            {{-- Texte détaillé --}}
                            @if (!empty($ai['summary']))
                                <div class="mt-4 pt-3" style="border-top:1px solid #EDE4D0;">
                                    <div class="text-[11px] uppercase tracking-widest mb-2" style="color:#C9A84C;">Analyse détaillée</div>
                                    <div class="text-sm" style="color:#5C5449;line-height:1.7;">{{ (string)$ai['summary'] }}</div>
                                    @if ($aiLines->isNotEmpty())
                                        <ul class="mt-3 space-y-1" style="font-size:0.8rem;color:#5C5449;">
                                            @foreach ($aiLines as $line)
                                                <li style="padding-left:1rem;text-indent:-0.8rem;">— {{ $line }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-white" style="border:1px solid var(--beige-200);border-radius:2px;padding:1.5rem;">
                <div class="section-label">Bloc 2 — Éléments critiques</div>
                <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="stat-card" style="padding:1rem;">
                        <div class="section-label" style="margin-bottom:0.4rem;">Mois atypiques</div>
                        <ul class="mt-2 text-sm space-y-1">
                            @forelse ($topCriticalMonths as $row)
                                @php
                                    $monthLabel = '—';
                                    if (!empty($row['month'])) {
                                        try {
                                            $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', (string) $row['month'])->translatedFormat('m/Y');
                                        } catch (\Throwable) {
                                            $monthLabel = (string) $row['month'];
                                        }
                                    }
                                    $creditAnomaly = !empty($row['credit_anomaly']);
                                    $debitAnomaly = !empty($row['debit_anomaly']);
                                    $creditZ = (float) ($row['credit_z'] ?? 0);
                                    $debitZ = (float) ($row['debit_z'] ?? 0);
                                    $signals = [];
                                    if ($creditAnomaly) {
                                        $signals[] = 'Crédits atypiques (+'.number_format(abs($creditZ), 2, ',', ' ').'σ)';
                                    }
                                    if ($debitAnomaly) {
                                        $signals[] = 'Débits atypiques (+'.number_format(abs($debitZ), 2, ',', ' ').'σ)';
                                    }
                                    if (empty($signals)) {
                                        $signals[] = 'Écart modéré non critique';
                                    }
                                @endphp
                                <li class="flex justify-between gap-3">
                                    <span>{{ $monthLabel }}</span>
                                    <span class="text-orange-700 font-medium text-right">
                                        {{ implode(' · ', $signals) }}
                                        <span class="text-slate-500 font-normal"> · C {{ number_format((float)($row['credits'] ?? 0), 2, ',', ' ') }} € / D {{ number_format((float)($row['debits'] ?? 0), 2, ',', ' ') }} €</span>
                                    </span>
                                </li>
                            @empty
                                <li class="text-gray-500">Aucun mois atypique détecté.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="stat-card" style="padding:1rem;">
                        <div class="section-label" style="margin-bottom:0.4rem;">Top 5 montants exceptionnels</div>
                        <ul class="mt-2 text-sm space-y-1">
                            @forelse ($criticalExceptional as $tx)
                                <li class="flex justify-between gap-2">
                                    <span class="truncate" title="{{ $tx->display_label_full ?? ($tx->label ?? '') }}">
                                        <span class="text-slate-500">{{ optional($tx->date)->format('d/m/Y') ?? '—' }}</span>
                                        <span class="mx-1" style="color:#DDD0B8;">·</span>
                                        {{ cleanBankLabel($tx->display_label ?? ($tx->label ?? '')) }}
                                    </span>
                                    <span class="font-medium whitespace-nowrap">{{ number_format(abs((float)($tx->amount ?? 0)), 2, ',', ' ') }}</span>
                                </li>
                            @empty
                                <li class="text-gray-500">Aucun montant exceptionnel.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="stat-card" style="padding:1rem;">
                        <div class="section-label" style="margin-bottom:0.4rem;">Top 3 bénéficiaires concentrés</div>
                        <ul class="mt-2 text-sm space-y-2">
                            @forelse ($criticalBeneficiaries as $beneficiary)
                                <li class="border border-slate-100 rounded-lg px-2 py-1">
                                    <details>
                                        <summary class="list-none cursor-pointer">
                                            <div class="flex items-start justify-between gap-2">
                                                <span class="truncate font-medium text-slate-800">{{ $beneficiary['beneficiary'] ?? 'INCONNU' }}</span>
                                                <span class="text-right whitespace-nowrap">
                                                    <span class="font-semibold text-slate-800">{{ number_format((float)($beneficiary['amount'] ?? 0), 2, ',', ' ') }} €</span>
                                                    <span class="text-slate-600"> · {{ number_format((float)($beneficiary['share_pct'] ?? 0), 1, ',', ' ') }} %</span>
                                                </span>
                                            </div>
                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ (int)($beneficiary['details_total'] ?? ($beneficiary['count'] ?? 0)) }} écriture(s) affichée(s)
                                                @if (!empty($beneficiary['details_duplicates_merged']))
                                                    · {{ (int)$beneficiary['details_duplicates_merged'] }} doublon(s) fusionné(s)
                                                @endif
                                                · cliquer pour le détail
                                            </div>
                                        </summary>
                                        <div class="mt-2 border-t border-slate-100 pt-2">
                                            <ul class="space-y-1 text-xs text-slate-700">
                                                @foreach (collect($beneficiary['details'] ?? []) as $detail)
                                                    <li class="flex items-start justify-between gap-2">
                                                        <span class="min-w-[78px] text-slate-500">{{ $detail['date'] ?? '—' }}</span>
                                                        <span class="flex-1 text-slate-700">
                                                            @php
                                                                $shortClean = cleanBankLabel($detail['label'] ?? '');
                                                                $fullClean  = cleanBankLabel($detail['label_full'] ?? ($detail['label'] ?? ''));
                                                                $needsExpand = !empty($detail['label_truncated']) || (mb_strlen($fullClean) > mb_strlen($shortClean) + 5);
                                                            @endphp
                                                            <span class="js-short-label">{{ $shortClean ?: '—' }}</span>
                                                            <span class="js-full-label hidden">{{ $fullClean ?: '—' }}</span>
                                                            @if ($needsExpand)
                                                                <button type="button" class="ms-1 underline" style="color:#9B7A2A;" onclick="toggleFullLabel(this)">Voir complet</button>
                                                            @endif
                                                        </span>
                                                        <span class="whitespace-nowrap font-medium">
                                                            {{ number_format((float)($detail['amount'] ?? 0), 2, ',', ' ') }} €
                                                            @if ((int)($detail['duplicate_count'] ?? 1) > 1)
                                                                <span class="text-[10px] text-slate-500">×{{ (int)$detail['duplicate_count'] }}</span>
                                                            @endif
                                                        </span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </details>
                                </li>
                            @empty
                                <li class="text-gray-500">Aucune concentration marquée.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="border border-green-200 rounded-xl p-4 bg-green-50">
                        <div class="text-sm font-semibold text-green-800">Variation fenêtre sensible</div>
                        <div class="mt-2 text-3xl font-semibold text-green-700">
                            @if (!is_null($sensitive['change_pct'] ?? null))
                                {{ (($sensitive['change_pct'] ?? 0) >= 0 ? '+' : '') }}{{ $sensitive['change_pct'] ?? 0 }}%
                            @else
                                —
                            @endif
                        </div>
                        <div class="mt-1 text-xs text-green-700">{{ $sensitive['window_label'] ?? 'Fenêtre non disponible' }}</div>
                        @if (!is_null($sensitive['change_pct'] ?? null))
                            @php
                                $sensChange = (float)($sensitive['change_pct'] ?? 0);
                                $sensInterpret = $sensChange >= 100 ? 'Hausse très significative des flux : le double de l\'activité habituelle sur cette période.' :
                                    ($sensChange >= 50  ? 'Hausse importante : activité nettement supérieure à la normale.' :
                                    ($sensChange >= 20  ? 'Hausse modérée mais notable.' :
                                    ($sensChange >= 0   ? 'Légère hausse — dans les marges habituelles.' :
                                    ($sensChange >= -20 ? 'Légère baisse — dans les marges habituelles.' :
                                    ($sensChange >= -50 ? 'Baisse notable : activité réduite sur la fenêtre.' :
                                    'Baisse très marquée : activité anormalement faible.')))));
                                $sensCreditTotal = number_format((float)($sensitive['credits_window'] ?? 0), 2, ',', ' ');
                                $sensDebitTotal  = number_format((float)($sensitive['debits_window']  ?? 0), 2, ',', ' ');
                                $sensRef         = number_format((float)($sensitive['reference_avg'] ?? 0), 2, ',', ' ');
                            @endphp
                            <div class="mt-3 text-xs" style="color:#5C5449;line-height:1.7;">
                                <div style="font-weight:600;color:#2E2A25;">Interprétation</div>
                                <div>{{ $sensInterpret }}</div>
                                @if ((float)($sensitive['credits_window'] ?? 0) > 0 || (float)($sensitive['debits_window'] ?? 0) > 0)
                                    <div class="mt-2 grid grid-cols-2 gap-2">
                                        <div style="border:1px solid #E5DCC8;border-radius:3px;padding:6px 10px;">
                                            <div class="text-[10px] uppercase tracking-wider" style="color:#C9A84C;">Crédits sur fenêtre</div>
                                            <div class="font-semibold" style="color:#1C1916;">{{ $sensCreditTotal }} €</div>
                                        </div>
                                        <div style="border:1px solid #E5DCC8;border-radius:3px;padding:6px 10px;">
                                            <div class="text-[10px] uppercase tracking-wider" style="color:#C9A84C;">Débits sur fenêtre</div>
                                            <div class="font-semibold" style="color:#1C1916;">{{ $sensDebitTotal }} €</div>
                                        </div>
                                    </div>
                                @endif
                                @if ((float)($sensitive['reference_avg'] ?? 0) > 0)
                                    <div class="mt-2" style="color:#5C5449;">Référence mensuelle habituelle : <strong>{{ $sensRef }} €</strong></div>
                                @endif
                                <div class="mt-2 pt-2" style="border-top:1px solid #E5DCC8;font-size:0.68rem;color:#8a7d6a;">
                                    La fenêtre sensible correspond à la période entourant la date clé du dossier (décès ou événement juridique). Une variation élevée sur cette fenêtre peut indiquer des mouvements liés à la gestion anticipée ou postérieure à l'événement.
                                </div>
                            </div>
                        @endif
                    </div>
                    <div style="border:1px solid var(--beige-200);border-radius:2px;padding:1rem 1.2rem;" class="lg:col-span-2">
                        <div class="flex items-center justify-between gap-4">
                            <div class="section-label" style="margin-bottom:0;">Cohérence économique</div>
                            <span class="{{ $cohBadgeClass }}">{{ $cohDot }} {{ $cohLabel }}</span>
                        </div>
                        <div class="mt-1 text-xs text-slate-500">Hypothèse automatique (non juridique) — à confirmer par justificatifs.</div>
                        @php
                            $ecoExamples = collect($economic_coherence['examples'] ?? []);
                        @endphp
                        @if ($ecoExamples->isNotEmpty())
                            <div class="mt-2 text-xs uppercase tracking-wider" style="color:#C9A84C;">Flux significatifs</div>
                            <ul class="mt-1 space-y-1" style="font-size:0.8rem;color:#5C5449;">
                                @foreach ($ecoExamples as $example)
                                    <li style="padding-left:1rem;text-indent:-0.8rem;">— {{ $example['label'] ?? 'Flux important' }} · {{ number_format((float)($example['amount'] ?? 0), 2, ',', ' ') }} €</li>
                                @endforeach
                            </ul>
                        @endif
                        @if (!empty($ecoNarrative))
                            <div class="mt-3 ps-3" style="border-left:2px solid var(--gold);color:#2E2A25;line-height:1.7;font-size:0.82rem;">{{ $ecoNarrative }}</div>
                        @endif
                    </div>
                </div>
            </section>

            <section class="bg-white" style="border:1px solid var(--beige-200);border-radius:2px;padding:1rem 1.4rem;" x-data="{ fullOpen: false }">
                <div class="flex items-center justify-between gap-3">
                    <div class="section-label" style="margin-bottom:0;">Bloc 3 — Analyse complète</div>
                    <button type="button" @click="fullOpen = !fullOpen" style="display:inline-flex;align-items:center;padding:0.25rem 0.85rem;font-size:0.72rem;border:1px solid var(--beige-200);border-radius:2px;color:#5C5449;background:var(--beige-50);">
                        <span x-text="fullOpen ? 'Masquer l’analyse complète' : 'Afficher l’analyse complète'"></span>
                    </button>
                </div>
                <div class="mt-2 text-xs text-slate-600">Contient: résumé annuel, graphique détaillé, pics par catégorie, transactions, multi-années, détails techniques.</div>
                <div class="mt-2 text-xs text-slate-500">Méthodologie: {{ (string)(($analysis_meta['methodology'] ?? '')) }}</div>
                <div class="text-xs text-slate-500">RGPD: {{ (string)(($analysis_meta['rgpd'] ?? '')) }}</div>
                <div class="text-xs text-slate-500">Neutralité statistique: {{ (string)(($analysis_meta['neutrality'] ?? '')) }}</div>
                <div class="text-xs text-slate-500">Date d’analyse: {{ (string)(($analysis_meta['analysis_date'] ?? '')) }} · Version algorithme: {{ (string)(($analysis_meta['algorithm_version'] ?? '')) }}</div>
                <div x-show="fullOpen" x-cloak class="mt-4">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div id="card-actions-quick" class="toggle-card-section border border-gray-200 rounded-xl p-5 bg-white shadow-sm">
                        <div class="text-sm font-semibold text-gray-700">Actions rapides</div>
                        <div class="mt-1 text-xs text-gray-500">Avant chaque analyse, Analytica pré-classe automatiquement les bénéficiaires de virements et signale les cas ambigus à préciser seulement si nécessaire.</div>
                        <div class="mt-3 flex flex-wrap gap-3 items-end">
                            <form method="POST" action="{{ route('cases.analyze', $case) }}">
                                @csrf
                                <input type="hidden" name="analysis_scope" value="all" />
                                <button type="submit" class="inline-flex items-center px-5 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">{{ __('Analyser tout l’historique') }}</button>
                            </form>

                            <form method="POST" action="{{ route('cases.analyze', $case) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-2 items-end">
                                @csrf
                                <input type="hidden" name="analysis_scope" value="period" />
                                <div>
                                    <x-input-label for="analysis_from_action" :value="__('Du')" />
                                    <x-text-input id="analysis_from_action" name="analysis_from" type="date" class="mt-1 block w-full" :value="old('analysis_from', optional($case->analysis_period_start)->format('Y-m-d'))" />
                                </div>
                                <div>
                                    <x-input-label for="analysis_to_action" :value="__('Au')" />
                                    <x-text-input id="analysis_to_action" name="analysis_to" type="date" class="mt-1 block w-full" :value="old('analysis_to', optional($case->analysis_period_end)->format('Y-m-d'))" />
                                </div>
                                <button type="submit" class="inline-flex items-center px-5 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">{{ __('Analyser période') }}</button>
                            </form>

                            <details class="relative">
                                <summary class="list-none cursor-pointer inline-flex items-center px-5 py-3 bg-slate-700 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-slate-600">⬇ Exporter</summary>
                                <div class="absolute z-20 mt-2 min-w-56 bg-white border border-slate-200 rounded-md shadow-lg p-2 space-y-1">
                                    <form method="POST" action="{{ route('reports.generate', $case) }}">@csrf<input type="hidden" name="format" value="pdf" /><button type="submit" class="w-full text-left px-3 py-2 text-xs rounded hover:bg-slate-50">PDF synthèse</button></form>
                                    <form method="POST" action="{{ route('reports.generate', $case) }}">@csrf<input type="hidden" name="format" value="pdf" /><button type="submit" class="w-full text-left px-3 py-2 text-xs rounded hover:bg-slate-50">PDF complet</button></form>
                                    <a href="{{ route('cases.transactions.export', array_merge(['case' => $case], array_filter($tx_filters ?? [], fn($value) => !is_null($value) && $value !== ''))) }}" class="block px-3 py-2 text-xs rounded hover:bg-slate-50">Excel</a>
                                    <button type="button" onclick="exportChartSVG()" class="w-full text-left px-3 py-2 text-xs rounded hover:bg-slate-50">SVG</button>
                                </div>
                            </details>
                        </div>
                    </div>

                    <div id="card-print-custom" class="toggle-card-section mt-4 border border-gray-200 rounded-xl p-5 bg-white shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-gray-700">Impression personnalisée</div>
                                <div class="text-xs text-gray-500 mt-1">Sélectionne les cards à inclure, puis lance l’impression du dossier.</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="setPrintSections(true)" class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-700 rounded-md text-xs font-medium hover:bg-gray-50">
                                    Tout cocher
                                </button>
                                <button type="button" onclick="setPrintSections(false)" class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-700 rounded-md text-xs font-medium hover:bg-gray-50">
                                    Tout décocher
                                </button>
                                <button type="button" onclick="printSelectedSections()" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md text-sm font-medium hover:bg-gray-800">
                                    Imprimer la sélection
                                </button>
                                <button type="button" onclick="openBilanConfig()" class="inline-flex items-center gap-2 px-4 py-2 rounded-md text-sm font-semibold" style="background:linear-gradient(135deg,#2E2A25,#1C1916);color:#E0C278;border:1px solid rgba(201,168,76,0.45);letter-spacing:0.04em;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                    Bilan Notaire / Avocat
                                </button>
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 text-xs">
                            <label class="inline-flex items-center gap-2"><input class="print-section-checkbox rounded border-gray-300" type="checkbox" data-target="card-kind-monthly" checked> Mouvements mensuels</label>
                            <label class="inline-flex items-center gap-2"><input class="print-section-checkbox rounded border-gray-300" type="checkbox" data-target="card-kind-peaks" checked> Pics par catégorie</label>
                            <label class="inline-flex items-center gap-2"><input class="print-section-checkbox rounded border-gray-300" type="checkbox" data-target="card-beneficiary-concentration" checked> Concentration débits</label>
                            <label class="inline-flex items-center gap-2"><input class="print-section-checkbox rounded border-gray-300" type="checkbox" data-target="card-source-concentration" checked> Concentration crédits + suivi</label>
                            <label class="inline-flex items-center gap-2"><input class="print-section-checkbox rounded border-gray-300" type="checkbox" data-target="card-family-bilateral" checked> Bilan entrants/sortants (Top 10)</label>
                            <label class="inline-flex items-center gap-2"><input class="print-section-checkbox rounded border-gray-300" type="checkbox" data-target="card-beneficiary-disambiguation" checked> Désambiguïsation bénéficiaires</label>
                            <label class="inline-flex items-center gap-2"><input class="print-section-checkbox rounded border-gray-300" type="checkbox" data-target="card-regular-inflows" checked> Rentrées régulières</label>
                            <label class="inline-flex items-center gap-2"><input class="print-section-checkbox rounded border-gray-300" type="checkbox" data-target="card-regular-outflows" checked> Sorties régulières</label>
                            <label class="inline-flex items-center gap-2"><input class="print-section-checkbox rounded border-gray-300" type="checkbox" data-target="card-regular-outliers" checked> Écarts réguliers</label>
                            <label class="inline-flex items-center gap-2"><input class="print-section-checkbox rounded border-gray-300" type="checkbox" data-target="card-cash-withdrawals" checked> Retraits espèces</label>
                            <label class="inline-flex items-center gap-2"><input class="print-section-checkbox rounded border-gray-300" type="checkbox" data-target="card-transactions" checked> Transactions filtrées</label>
                            <label class="inline-flex items-center gap-2"><input class="print-section-checkbox rounded border-gray-300" type="checkbox" data-target="card-ai-report" checked> Compte rendu IA</label>
                        </div>
                    </div>

                    <div id="card-visibility-controls" class="mt-4 border border-gray-200 rounded-xl p-5 bg-white shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-gray-700">Affichage des cartes</div>
                                <div class="text-xs text-gray-500 mt-1">Affiche ou masque chaque carte d'analyse selon ton besoin.</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="setCardVisibilityForAll(true)" class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-700 rounded-md text-xs font-medium hover:bg-gray-50">Tout afficher</button>
                                <button type="button" onclick="setCardVisibilityForAll(false)" class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-700 rounded-md text-xs font-medium hover:bg-gray-50">Tout masquer</button>
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 text-xs">
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-actions-quick" checked> Actions rapides</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-case-info" checked> Informations dossier</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-followup-analysis" checked> Suivi analyse dossier</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-followup-ai" checked> Suivi assistant IA</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-exceptional" checked> Montants exceptionnels</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-reports" checked> Rapports générés</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-dashboard-summary" checked> Dashboard synthèse</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-habits" checked> Habitudes sensibles</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-monthly-chart" checked> Graphique mensuel</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-kind-monthly" checked> Mouvements mensuels</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-kind-peaks" checked> Pics par catégorie</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-beneficiary-concentration" checked> Concentration débits</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-source-concentration" checked> Concentration crédits + suivi</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-family-bilateral" checked> Bilan entrants/sortants (Top 10)</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-beneficiary-disambiguation" checked> Désambiguïsation bénéficiaires</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-regular-inflows" checked> Rentrées régulières</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-regular-outflows" checked> Sorties régulières</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-regular-outliers" checked> Écarts réguliers</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-cash-withdrawals" checked> Retraits espèces</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-transactions" checked> Transactions</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-ai-report" checked> Compte rendu IA</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-accounts" checked> Comptes</label>
                            <label class="inline-flex items-center gap-2"><input class="card-visibility-checkbox rounded border-gray-300" type="checkbox" data-target="card-multiyear-control" checked> Contrôle multi-années</label>
                        </div>
                    </div>

                    <div id="card-case-info" class="toggle-card-section mt-4 border border-gray-200 rounded-xl p-5 bg-white shadow-sm">
                        <div class="text-sm font-semibold text-gray-700">Informations dossier</div>
                        <form method="POST" action="{{ route('cases.update-details', $case) }}" class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                            @csrf
                            @method('PATCH')

                            <div>
                                <x-input-label for="deceased_name" :value="__('Nom du défunt')" />
                                <x-text-input id="deceased_name" name="deceased_name" type="text" class="mt-1 block w-full" :value="old('deceased_name', $case->deceased_name)" />
                                <x-input-error class="mt-1" :messages="$errors->get('deceased_name')" />
                            </div>

                            <div>
                                <x-input-label for="death_date" :value="__('Date de décès')" />
                                <x-text-input id="death_date" name="death_date" type="date" class="mt-1 block w-full" :value="old('death_date', optional($case->death_date)->format('Y-m-d'))" />
                                <x-input-error class="mt-1" :messages="$errors->get('death_date')" />
                            </div>

                            <div>
                                <x-input-label for="analysis_period_start" :value="__('Début période')" />
                                <x-text-input id="analysis_period_start" name="analysis_period_start" type="date" class="mt-1 block w-full" :value="old('analysis_period_start', optional($case->analysis_period_start)->format('Y-m-d'))" />
                                <x-input-error class="mt-1" :messages="$errors->get('analysis_period_start')" />
                            </div>

                            <div>
                                <x-input-label for="analysis_period_end" :value="__('Fin période')" />
                                <x-text-input id="analysis_period_end" name="analysis_period_end" type="date" class="mt-1 block w-full" :value="old('analysis_period_end', optional($case->analysis_period_end)->format('Y-m-d'))" />
                                <x-input-error class="mt-1" :messages="$errors->get('analysis_period_end')" />
                            </div>

                            <div class="md:col-span-4">
                                <x-primary-button>
                                    Enregistrer informations
                                </x-primary-button>
                            </div>
                        </form>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div id="card-followup-analysis" class="toggle-card-section border border-green-100 rounded-xl p-5 bg-green-50 shadow-sm">
                            <div class="font-semibold text-gray-800">Suivi analyse dossier</div>
                            @if ($latestAnalysis)
                                <div class="mt-2 text-gray-700">Dernière exécution: <span class="font-medium whitespace-nowrap">{{ optional($latestAnalysis->generated_at)->format('d/m/Y H:i') }}</span></div>
                                <div class="mt-1 text-gray-700">Score: <span class="font-medium">{{ $latestAnalysis->global_score ?? '—' }}</span> · Anomalies: <span class="font-medium">{{ $latestAnalysis->total_flagged ?? '—' }}</span> / {{ $latestAnalysis->total_transactions ?? '—' }}</div>
                            @else
                                <div class="mt-2 text-gray-600">Aucune analyse dossier enregistrée pour le moment. Clique sur <span class="font-medium">Analyser tout l’historique</span>.</div>
                            @endif
                        </div>

                        <div id="card-followup-ai" class="toggle-card-section border border-gray-200 rounded-xl p-5 bg-gray-50 shadow-sm">
                            <div class="font-semibold text-gray-800">Suivi Assistant IA</div>
                            @if (!empty($lastAi['ran_at']))
                                <div class="mt-2 text-gray-700">Dernière demande: <span class="font-medium whitespace-nowrap">{{ \Carbon\Carbon::parse($lastAi['ran_at'])->format('d/m/Y H:i') }}</span></div>
                                <div class="mt-1 text-gray-700">Question: <span class="font-medium">{{ $lastAi['prompt'] !== '' ? $lastAi['prompt'] : 'demande par défaut' }}</span></div>
                            @else
                                <div class="mt-2 text-gray-600">Aucune demande IA envoyée pour ce dossier.</div>
                            @endif
                        </div>
                    </div>

                    <div id="card-exceptional" class="toggle-card-section mt-4 border border-red-100 rounded-xl p-5 bg-red-50 shadow-sm">
                        <div class="font-semibold text-gray-800">Montants exceptionnels (≥ {{ number_format((float)($exceptional_threshold ?? 20000), 0, ',', ' ') }} €)</div>
                        <div class="mt-1 text-xs text-gray-600">Vérification rapide des mouvements majeurs — <strong>débits et crédits</strong> inclus. Filtrez par type ou par sens pour préparer un courrier de demande de justificatifs.</div>

                        {{-- Filtres + seuil --}}
                        <div class="mt-3 flex flex-wrap items-end gap-4">
                            <form method="GET" action="{{ route('cases.show', $case) }}" class="flex flex-wrap items-end gap-3 text-xs">
                                @foreach (request()->except('exceptional_threshold', 'page') as $key => $value)
                                    @if (is_array($value))
                                        @foreach ($value as $item)
                                            <input type="hidden" name="{{ $key }}[]" value="{{ $item }}" />
                                        @endforeach
                                    @else
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}" />
                                    @endif
                                @endforeach
                                <div>
                                    <label for="exceptional_threshold" class="block text-gray-600">Seuil (€)</label>
                                    <input id="exceptional_threshold" name="exceptional_threshold" type="number" min="500" step="100" value="{{ (float)($exceptional_threshold ?? 20000) }}" class="mt-1 w-32 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" />
                                </div>
                                <button type="submit" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">Appliquer</button>
                            </form>

                            {{-- Filtre type JS --}}
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="text-gray-600 font-medium">Filtrer :</span>
                                <button type="button" onclick="filterExceptional('all')" id="exc-btn-all"
                                    class="exc-type-btn px-3 py-1.5 rounded border font-semibold"
                                    style="background:#1C1916;color:#E0C278;border-color:#C9A84C;">Tous</button>
                                <button type="button" onclick="filterExceptional('virement')" id="exc-btn-virement"
                                    class="exc-type-btn px-3 py-1.5 rounded border font-semibold"
                                    style="background:#fff;color:#374151;border-color:#d1d5db;">Virements</button>
                                <button type="button" onclick="filterExceptional('cheque')" id="exc-btn-cheque"
                                    class="exc-type-btn px-3 py-1.5 rounded border font-semibold"
                                    style="background:#fff;color:#374151;border-color:#d1d5db;">Chèques</button>
                                <button type="button" onclick="filterExceptional('especes')" id="exc-btn-especes"
                                    class="exc-type-btn px-3 py-1.5 rounded border font-semibold"
                                    style="background:#fff;color:#374151;border-color:#d1d5db;">Retraits espèces</button>

                                <span class="text-gray-300 mx-1">|</span>

                                <button type="button" onclick="filterExceptionalDir('all')" id="exc-btn-dir-all"
                                    class="exc-dir-btn px-3 py-1.5 rounded border font-semibold"
                                    style="background:#1C1916;color:#E0C278;border-color:#C9A84C;">Tous sens</button>
                                <button type="button" onclick="filterExceptionalDir('debit')" id="exc-btn-dir-debit"
                                    class="exc-dir-btn px-3 py-1.5 rounded border font-semibold"
                                    style="background:#fff;color:#374151;border-color:#d1d5db;">↓ Débits</button>
                                <button type="button" onclick="filterExceptionalDir('credit')" id="exc-btn-dir-credit"
                                    class="exc-dir-btn px-3 py-1.5 rounded border font-semibold"
                                    style="background:#fff;color:#374151;border-color:#d1d5db;">↑ Crédits</button>
                            </div>

                            {{-- Bouton export --}}
                            <button type="button" onclick="printExceptionalTable()" id="exc-print-btn"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded text-xs font-semibold"
                                style="background:linear-gradient(135deg,#2E2A25,#1C1916);color:#E0C278;border:1px solid rgba(201,168,76,0.35);border-radius:2px;letter-spacing:0.08em;">
                                ↓ Télécharger / Imprimer la sélection
                            </button>
                        </div>

                        @if (($exceptional_transactions ?? collect())->count() === 0)
                            <div class="mt-2 text-sm text-gray-600">Aucun mouvement exceptionnel trouvé sur les filtres actuels.</div>
                        @else
                            <div class="mt-3 overflow-x-auto" id="exceptional-table-wrapper">
                                <table class="min-w-full text-sm" id="exceptional-table">
                                    <thead>
                                        <tr class="text-left text-gray-600">
                                            <th class="py-2 pr-4">Date</th>
                                            <th class="py-2 pr-4">Compte</th>
                                            <th class="py-2 pr-4">Libellé (normalisé)</th>
                                            <th class="py-2 pr-4">Type</th>
                                            <th class="py-2 pr-4">Sens</th>
                                            <th class="py-2 pr-4 text-right">Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" id="exceptional-tbody">
                                        @foreach (($exceptional_transactions ?? collect()) as $tx)
                                            @php
                                                $account = $case->bankAccounts->firstWhere('id', $tx->bank_account_id);
                                                $txMeta = is_array($tx->meta ?? null) ? $tx->meta : [];
                                                $sourceLine = '';
                                                foreach (['source_line', 'raw_line', 'ocr_line', 'source_text', 'raw_label'] as $metaKey) {
                                                    $candidate = $txMeta[$metaKey] ?? null;
                                                    if (is_string($candidate) && trim($candidate) !== '') {
                                                        $sourceLine = trim($candidate);
                                                        break;
                                                    }
                                                    if (is_array($candidate) && $candidate !== []) {
                                                        $joined = trim(implode(' ', array_map(fn ($v) => is_scalar($v) ? (string) $v : '', $candidate)));
                                                        if ($joined !== '') { $sourceLine = $joined; break; }
                                                    }
                                                }
                                                if ($sourceLine === '' && is_array($txMeta['source_block_lines'] ?? null)) {
                                                    $joinedLines = trim(implode(' ', array_map(fn ($v) => is_scalar($v) ? (string) $v : '', (array) $txMeta['source_block_lines'])));
                                                    if ($joinedLines !== '') $sourceLine = $joinedLines;
                                                }
                                                $sourceConfidence = isset($txMeta['confidence']) ? (int) $txMeta['confidence'] : null;
                                                $rawLbl = $tx->display_label_full ?? $tx->label ?? '';
                                                $rawUp  = mb_strtoupper($rawLbl);
                                                $txKind = $tx->kind ?? '';
                                                $txTypeKey = (str_contains($rawUp, 'CHEQUE') || str_contains($rawUp, 'CHQ') || $txKind === 'cheque') ? 'cheque'
                                                    : ((str_contains($rawUp, 'ESPECE') || str_contains($rawUp, 'DAB') || str_contains($rawUp, 'RETRAIT') || $txKind === 'cash_withdrawal' || $txKind === 'cash') ? 'especes'
                                                    : 'virement');
                                                $txTypeLabel = $txTypeKey === 'cheque' ? 'Chèque' : ($txTypeKey === 'especes' ? 'Retrait espèces' : 'Virement');
                                                $cleanedLabel = cleanBankLabel($tx->display_label ?? ($tx->label ?: ''));
                                            @endphp
                                            <tr class="exc-row" data-type="{{ $txTypeKey }}" data-direction="{{ ($tx->type ?? '') === 'debit' ? 'debit' : 'credit' }}">
                                                <td class="py-2 pr-4 whitespace-nowrap">{{ optional($tx->date)->format('d/m/Y') }}</td>
                                                <td class="py-2 pr-4 whitespace-nowrap">{{ ($account_display_by_id[$tx->bank_account_id] ?? null) ?: ($account?->bank_name ?: ('Compte #'.$tx->bank_account_id)) }}</td>
                                                <td class="py-2 pr-4 max-w-sm" title="{{ $rawLbl }}">
                                                    <span>{{ $cleanedLabel ?: '—' }}</span>
                                                    @if (!empty($tx->display_label_truncated))
                                                        <button type="button" class="text-xs underline ml-2" style="color:#9B7A2A;" onclick="toggleFullLabel(this)" data-short="{{ e($cleanedLabel) }}" data-full="{{ e(cleanBankLabel($tx->display_label_full ?? '')) }}">Voir complet</button>
                                                    @endif
                                                </td>
                                                <td class="py-2 pr-4 text-xs">
                                                    <span class="px-2 py-0.5 rounded text-[11px] font-semibold"
                                                        style="background:{{ $txTypeKey === 'cheque' ? '#FEF9EC' : ($txTypeKey === 'especes' ? '#FEF2F2' : '#F0F9FF') }};color:{{ $txTypeKey === 'cheque' ? '#92400E' : ($txTypeKey === 'especes' ? '#991B1B' : '#1E40AF') }};border:1px solid {{ $txTypeKey === 'cheque' ? '#FDE68A' : ($txTypeKey === 'especes' ? '#FECACA' : '#BFDBFE') }};">{{ $txTypeLabel }}</span>
                                                </td>
                                                <td class="py-2 pr-4">{{ ($tx->type ?? '') === 'debit' ? 'Débit' : 'Crédit' }}</td>
                                                <td class="py-2 pr-4 text-right whitespace-nowrap">
                                                    <button type="button"
                                                        class="tx-source-toggle font-semibold underline decoration-dotted underline-offset-2 {{ ($tx->type ?? '') === 'debit' ? 'text-red-700 hover:text-red-800' : 'text-green-700 hover:text-green-800' }}"
                                                        data-target="tx-source-row-{{ $tx->id }}" aria-expanded="false">
                                                        {{ number_format(abs((float)$tx->amount), 2, ',', ' ') }} €
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr id="tx-source-row-{{ $tx->id }}" class="hidden bg-white/70 exc-row" data-type="{{ $txTypeKey }}" data-direction="{{ ($tx->type ?? '') === 'debit' ? 'debit' : 'credit' }}">
                                                <td colspan="6" class="py-3 pr-4">
                                                    <div class="text-xs font-medium text-gray-700">Ligne source</div>
                                                    @if ($sourceLine !== '')
                                                        <pre class="mt-1 p-2 text-xs whitespace-pre-wrap break-words rounded border border-gray-200 bg-gray-50 text-gray-800">{{ $sourceLine }}</pre>
                                                    @else
                                                        <pre class="mt-1 p-2 text-xs whitespace-pre-wrap break-words rounded border border-gray-200 bg-gray-50 text-gray-700">{{ optional($tx->date)->format('d/m/Y') }} | {{ ($tx->type ?? '') === 'debit' ? 'DÉBIT' : 'CRÉDIT' }} | {{ number_format(abs((float)$tx->amount), 2, ',', ' ') }} | {{ $rawLbl }}</pre>
                                                    @endif
                                                    @if ($sourceConfidence !== null)
                                                        <div class="mt-1 text-[11px] text-gray-500">Indice de confiance OCR : {{ $sourceConfidence }}/100</div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                        <tr id="exc-no-results-row" style="display:none;">
                                            <td colspan="7" class="py-8 text-center text-sm text-gray-400 italic">Aucune transaction de ce type au-dessus du seuil.</td>
                                        </tr>
                                    </table>
                                </div>
                        @endif

                        {{-- Graphique pics exceptionnels --}}
                        @if (($exceptional_transactions ?? collect())->count() > 0)
                            @php
                                $excByMonth = collect($exceptional_transactions ?? [])
                                    ->groupBy(fn($t) => optional($t->date)->format('Y-m') ?? 'N/A')
                                    ->map(fn($group) => $group->sum(fn($t) => abs((float)($t->amount ?? 0))))
                                    ->sortKeys();
                                $excMonths  = $excByMonth->keys()->values();
                                $excAmounts = $excByMonth->values();
                                $excMax     = max(1, (float)$excAmounts->max());
                                $excCount   = $excMonths->count();
                                $excW  = 980; $excH = 200;
                                $excPL = 110; $excPR = 30; $excPT = 16;
                                $rotateTicks = $excCount > 12;
                                $excPB = $rotateTicks ? 65 : 40;
                                $excH  = 200 + ($rotateTicks ? 25 : 0);
                                $excPlotW = $excW - $excPL - $excPR;
                                $excPlotH = $excH - $excPT - $excPB;
                                // Divide plot width by count (not count-1) so bars are evenly spaced
                                // with a half-step margin on each side → no overflow left or right.
                                $excStep  = $excPlotW / max(1, $excCount);
                            @endphp
                            <div class="mt-6">
                                <div class="text-xs font-semibold" style="color:#5C5449;letter-spacing:0.06em;">ANALYSE DES PICS EXCEPTIONNELS PAR MOIS</div>
                                <div class="text-xs text-gray-500 mt-1">Montant cumulé par mois des transactions ≥ {{ number_format((float)($exceptional_threshold ?? 20000), 0, ',', ' ') }} €. Chaque barre représente l'intensité financière d'un mois.</div>
                                <div class="mt-3">
                                    <svg width="100%" viewBox="0 0 {{ $excW }} {{ $excH }}" xmlns="http://www.w3.org/2000/svg" style="display:block;">
                                        <rect x="0" y="0" width="{{ $excW }}" height="{{ $excH }}" fill="#FDFAF5"/>
                                        {{-- Grille Y --}}
                                        @foreach ([0, 0.25, 0.5, 0.75, 1] as $frac)
                                            @php
                                                $gridY = $excPT + $excPlotH * (1 - $frac);
                                                $gridVal = $excMax * $frac;
                                            @endphp
                                            <line x1="{{ $excPL }}" y1="{{ number_format($gridY, 1, '.', '') }}" x2="{{ $excW - $excPR }}" y2="{{ number_format($gridY, 1, '.', '') }}" stroke="#EDE4D0" stroke-width="1"/>
                                            <text x="{{ $excPL - 6 }}" y="{{ number_format($gridY + 4, 1, '.', '') }}" text-anchor="end" font-size="9" fill="#5C5449">{{ number_format($gridVal / 1000, 0, ',', ' ') }}k€</text>
                                        @endforeach
                                        {{-- Barres --}}
                                        @foreach ($excMonths as $i => $month)
                                            @php
                                                $amt    = (float)($excAmounts[$i] ?? 0);
                                                $barH   = ($amt / $excMax) * $excPlotH;
                                                // Half-step offset: first bar at 0.5·step, last at (n-0.5)·step
                                                $barX   = $excPL + ($i + 0.5) * $excStep;
                                                $barW   = max(4, $excStep * 0.6);
                                                $barY   = $excPT + $excPlotH - $barH;
                                                $isHigh = $amt > $excMax * 0.75;
                                            @endphp
                                            <rect x="{{ number_format($barX - $barW/2, 1, '.', '') }}" y="{{ number_format($barY, 1, '.', '') }}" width="{{ number_format($barW, 1, '.', '') }}" height="{{ number_format($barH, 1, '.', '') }}"
                                                fill="{{ $isHigh ? '#9B7A2A' : '#C9A84C' }}" opacity="0.85" rx="2">
                                                <title>{{ $month }} : {{ number_format($amt, 0, ',', ' ') }} €</title>
                                            </rect>
                                            @if ($excCount <= 24 || $i % 3 === 0)
                                                @if ($rotateTicks)
                                                    <text x="{{ number_format($barX, 1, '.', '') }}"
                                                          y="{{ $excPT + $excPlotH + 10 }}"
                                                          text-anchor="end" font-size="8" fill="#5C5449"
                                                          transform="rotate(-45, {{ number_format($barX, 1, '.', '') }}, {{ $excPT + $excPlotH + 10 }})">{{ substr($month, 2) }}</text>
                                                @else
                                                    <text x="{{ number_format($barX, 1, '.', '') }}" y="{{ $excPT + $excPlotH + 14 }}" text-anchor="middle" font-size="8" fill="#5C5449">{{ substr($month, 2) }}</text>
                                                @endif
                                            @endif
                                            @if ($isHigh)
                                                @if ($barH > 22)
                                                    @php
                                                        // Place label in the middle of the bar to avoid colliding
                                                        // with the Y-axis top tick label when the bar is at max height.
                                                        $labelY = $barY + max(14, $barH / 2);
                                                    @endphp
                                                    {{-- Label inside bar in white --}}
                                                    <text x="{{ number_format($barX, 1, '.', '') }}" y="{{ number_format($labelY, 1, '.', '') }}" text-anchor="middle" font-size="8" font-weight="bold" fill="#fff">{{ number_format($amt / 1000, 0, ',', ' ') }}k</text>
                                                @endif
                                            @endif
                                        @endforeach
                                        {{-- Axe X --}}
                                        <line x1="{{ $excPL }}" y1="{{ $excPT + $excPlotH }}" x2="{{ $excW - $excPR }}" y2="{{ $excPT + $excPlotH }}" stroke="#DDD0B8" stroke-width="1"/>
                                    </svg>
                                </div>
                            </div>

                        @endif
                    </div>

                    {{-- Script : disambiguation form — n'envoie que les lignes explicitement modifiées --}}
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var form = document.getElementById('disambiguation-form');
                        if (!form) return;
                        form.addEventListener('submit', function() {
                            // Highlight visually which rows were changed
                            form.querySelectorAll('select[data-original]').forEach(function(sel) {
                                var changed = sel.value !== sel.dataset.original;
                                // If unchanged AND has a data-original that is not RESET, disable the
                                // hidden label input too so neither field is sent for this row.
                                if (!changed) {
                                    sel.disabled = true;
                                    // Also disable the corresponding hidden normalized_label input
                                    var tr = sel.closest('tr');
                                    if (tr) {
                                        var hidden = tr.querySelector('input[type="hidden"][name*="normalized_label"]');
                                        if (hidden) hidden.disabled = true;
                                    }
                                }
                            });
                        });
                    });
                    </script>

                    {{-- Toujours disponible : filtres + impression pour card-exceptional --}}
                    <script>
                    var _excTypeFilter = 'all';
                    var _excDirFilter = 'all';

                    function _applyExceptionalFilters() {
                        var visibleCount = 0;
                        document.querySelectorAll('#exceptional-tbody .exc-row').forEach(function(row) {
                            var typeOk = _excTypeFilter === 'all' || row.dataset.type === _excTypeFilter;
                            var dirOk  = _excDirFilter  === 'all' || row.dataset.direction === _excDirFilter;
                            var show   = typeOk && dirOk;
                            row.style.display = show ? '' : 'none';
                            if (show && !row.id.startsWith('tx-source')) visibleCount++;
                        });
                        var noResults = document.getElementById('exc-no-results-row');
                        if (noResults) noResults.style.display = visibleCount === 0 ? '' : 'none';
                    }

                    function filterExceptional(type) {
                        _excTypeFilter = type;
                        document.querySelectorAll('.exc-type-btn').forEach(function(btn) {
                            btn.style.background = '#fff';
                            btn.style.color = '#374151';
                            btn.style.borderColor = '#d1d5db';
                        });
                        var active = document.getElementById('exc-btn-' + type);
                        if (active) {
                            active.style.background = '#1C1916';
                            active.style.color = '#E0C278';
                            active.style.borderColor = '#C9A84C';
                        }
                        _applyExceptionalFilters();
                    }

                    function filterExceptionalDir(dir) {
                        _excDirFilter = dir;
                        document.querySelectorAll('.exc-dir-btn').forEach(function(btn) {
                            btn.style.background = '#fff';
                            btn.style.color = '#374151';
                            btn.style.borderColor = '#d1d5db';
                        });
                        var active = document.getElementById('exc-btn-dir-' + dir);
                        if (active) {
                            active.style.background = '#1C1916';
                            active.style.color = '#E0C278';
                            active.style.borderColor = '#C9A84C';
                        }
                        _applyExceptionalFilters();
                    }

                    function filterSrcConc(nature) {
                        document.querySelectorAll('.src-nature-btn').forEach(function(btn) {
                            btn.style.background = 'white';
                            btn.style.color = '#2E2A25';
                            btn.style.borderColor = '#DDD0B8';
                        });
                        var active = document.getElementById('src-btn-' + nature);
                        if (active) {
                            active.style.background = '#1C1916';
                            active.style.color = '#E0C278';
                            active.style.borderColor = '#C9A84C';
                        }
                        document.querySelectorAll('#src-conc-tbody .src-row').forEach(function(row) {
                            if (nature === 'all' || row.dataset.nature === nature) {
                                row.style.display = '';
                            } else {
                                row.style.display = 'none';
                            }
                        });
                    }

                    function printExceptionalTable() {
                        var caseName = {!! json_encode($case->title ?? 'Dossier') !!};
                        var threshold = {!! json_encode(number_format((float)($exceptional_threshold ?? 20000), 0, ',', ' ')) !!};
                        var rows = document.querySelectorAll('#exceptional-tbody .exc-row:not([style*="display: none"])');
                        var activeType = 'Tous types';
                        document.querySelectorAll('.exc-type-btn').forEach(function(btn) {
                            if (btn.style.background.includes('1C1916') || btn.style.background.includes('rgb(28')) {
                                activeType = btn.textContent.trim();
                            }
                        });
                        var html = '<html><head><title>Montants exceptionnels \u2013 ' + caseName + '</title>';
                        html += '<style>body{font-family:Georgia,serif;font-size:12px;color:#1C1916;padding:32px;} h1{font-size:1.5rem;font-weight:300;letter-spacing:0.04em;margin-bottom:4px;} .sub{font-size:0.75rem;color:#5C5449;margin-bottom:24px;} table{width:100%;border-collapse:collapse;} th{text-align:left;border-bottom:2px solid #C9A84C;padding:6px 8px;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;} td{padding:6px 8px;border-bottom:1px solid #EDE4D0;font-size:12px;} .amount{text-align:right;font-variant-numeric:tabular-nums;} .footer{margin-top:32px;font-size:9px;color:#8a7d6a;border-top:1px solid #EDE4D0;padding-top:8px;}</style>';
                        html += '</head><body>';
                        html += '<h1>Montants exceptionnels \u2014 ' + caseName + '</h1>';
                        html += '<div class="sub">Seuil : \u2265 ' + threshold + ' \u20ac &nbsp;\u00b7&nbsp; Filtre : ' + activeType + ' &nbsp;\u00b7&nbsp; G\u00e9n\u00e9r\u00e9 le {{ now()->format("d/m/Y") }}</div>';
                        html += '<p style="font-size:11px;color:#5C5449;margin-bottom:16px;">Ce document liste les transactions financi\u00e8res exceptionnelles d\u00e9tect\u00e9es lors de l\'analyse du dossier. Il peut servir de base \u00e0 une demande de justificatifs adress\u00e9e aux \u00e9metteurs ou b\u00e9n\u00e9ficiaires concern\u00e9s.</p>';
                        html += '<table><thead><tr><th>Date</th><th>Libell\u00e9</th><th>Type</th><th>Sens</th><th class="amount">Montant</th></tr></thead><tbody>';
                        rows.forEach(function(row) {
                            if (row.id && row.id.startsWith('tx-source')) return;
                            var cells = row.querySelectorAll('td');
                            if (cells.length >= 6) {
                                html += '<tr>';
                                html += '<td>' + (cells[0]?.textContent?.trim() || '') + '</td>';
                                html += '<td>' + (cells[2]?.querySelector('span')?.textContent?.trim() || cells[2]?.textContent?.trim() || '') + '</td>';
                                html += '<td>' + (cells[3]?.textContent?.trim() || '') + '</td>';
                                html += '<td>' + (cells[4]?.textContent?.trim() || '') + '</td>';
                                html += '<td class="amount">' + (cells[5]?.querySelector('button')?.textContent?.trim() || cells[5]?.textContent?.trim() || '') + '</td>';
                                html += '</tr>';
                            }
                        });
                        html += '</tbody></table>';
                        html += '<div class="footer">Document g\u00e9n\u00e9r\u00e9 automatiquement par Analytica \u2014 Usage strictement professionnel et confidentiel.</div>';
                        html += '</body></html>';
                        var win = window.open('', '_blank', 'width=900,height=700');
                        win.document.write(html);
                        win.document.close();
                        win.focus();
                        setTimeout(function() { win.print(); }, 400);
                    }
                    </script>

                    <hr class="my-6" />

                    <div id="card-reports" class="toggle-card-section">
                    <h3 class="font-semibold">Rapports générés</h3>
                    @if (($reports ?? collect())->count() === 0)
                        <p class="mt-2 text-sm text-gray-600">Aucun rapport généré pour le moment.</p>
                    @else
                        <div class="mt-3 overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-600">
                                        <th class="py-2 pr-4">Date</th>
                                        <th class="py-2 pr-4">Version</th>
                                        <th class="py-2 pr-4">Fichier</th>
                                        <th class="py-2 pr-4">Type</th>
                                        <th class="py-2 pr-4 text-right">Taille</th>
                                        <th class="py-2 pr-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach (($reports ?? collect()) as $report)
                                        <tr>
                                            <td class="py-2 pr-4 whitespace-nowrap">{{ optional($report->generated_at)->format('Y-m-d H:i') }}</td>
                                            <td class="py-2 pr-4">v{{ $report->version }}</td>
                                            <td class="py-2 pr-4 max-w-md truncate" title="{{ $report->original_filename ?? '' }}">{{ $report->original_filename ?? 'rapport' }}</td>
                                            <td class="py-2 pr-4">{{ $report->mime_type ?? '—' }}</td>
                                            <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap">{{ $report->size_bytes ? number_format(((float)$report->size_bytes) / 1024, 1, ',', ' ') . ' Ko' : '—' }}</td>
                                            <td class="py-2 pr-4">
                                                <a href="{{ route('reports.download', $report) }}" class="text-green-700 hover:underline">Télécharger</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    </div>

                    <hr class="my-6" />

                    <div id="card-dashboard-summary" class="toggle-card-section">
                    <h3 class="font-semibold">Dashboard</h3>

                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                        <div class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
                            <div class="text-gray-600">Transactions</div>
                            <div class="text-lg font-semibold">{{ $stats['total_transactions'] ?? '—' }}</div>
                        </div>
                        <div class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
                            <div class="text-gray-600">Anomalies</div>
                            <div class="text-lg font-semibold">{{ $stats['total_flagged'] ?? '—' }}</div>
                        </div>
                        <div class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
                            <div class="text-gray-600">Montant cumulé (abs.)</div>
                            <div class="text-lg font-semibold">{{ $stats['total_flagged_amount'] ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                        <div>
                            <div class="font-medium">Top bénéficiaires</div>
                            @if (($stats['top_beneficiaries'] ?? collect())->count() === 0)
                                <div class="text-gray-600">—</div>
                            @else
                                <ul class="mt-2 space-y-1">
                                    @foreach ($stats['top_beneficiaries'] as $label => $amount)
                                        <li class="flex justify-between gap-4">
                                            <span class="truncate">{{ $label !== '' ? $label : '—' }}</span>
                                            <span class="tabular-nums whitespace-nowrap">{{ number_format((float) $amount, 2, ',', ' ') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        <div>
                            <div class="font-medium">Timeline (anomalies / mois)</div>
                            @if (($stats['timeline'] ?? collect())->count() === 0)
                                <div class="text-gray-600">—</div>
                            @else
                                <ul class="mt-2 space-y-1">
                                    @foreach ($stats['timeline'] as $ym => $cnt)
                                        <li class="flex justify-between gap-4">
                                            <span>{{ $ym }}</span>
                                            <span class="tabular-nums">{{ $cnt }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    @php
                        $behavioral = $behavioral ?? [];
                        $monthlyTotals = collect($behavioral['monthly_totals'] ?? []);
                        $monthlyMax = (float) ($behavioral['monthly_max'] ?? 0);
                        $sensitive = $behavioral['sensitive_stats'] ?? null;
                        $topSpikes = collect($behavioral['top_spikes'] ?? []);
                    @endphp

                    </div>

                    <hr class="my-6" />

                    <div id="card-habits" class="toggle-card-section">
                    <h3 class="font-semibold">Habitudes & périodes sensibles</h3>

                    @if ($sensitive)
                        <div class="mt-3 border border-gray-200 rounded-xl p-5 bg-gray-50 shadow-sm text-sm">
                            <div class="font-medium text-gray-800">Comparatif avant décès</div>
                            <div class="mt-2 text-gray-700">
                                Fenêtre sensible: <span class="font-medium">{{ $sensitive['window_label'] }}</span> · Référence: <span class="font-medium">{{ $sensitive['baseline_label'] }}</span>
                            </div>
                            <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
                                    <div class="text-xs text-gray-500">Débit mensuel (fenêtre sensible)</div>
                                    <div class="text-xl font-semibold text-gray-900">{{ number_format((float)($sensitive['sensitive_monthly_debit'] ?? 0), 2, ',', ' ') }}</div>
                                </div>
                                <div class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
                                    <div class="text-xs text-gray-500">Débit mensuel (référence)</div>
                                    <div class="text-xl font-semibold text-gray-900">{{ number_format((float)($sensitive['baseline_monthly_debit'] ?? 0), 2, ',', ' ') }}</div>
                                </div>
                                <div class="border rounded-xl p-4 shadow-sm {{ (($sensitive['severity'] ?? 'neutral') === 'high') ? 'bg-red-50 border-red-200' : ((($sensitive['severity'] ?? 'neutral') === 'medium') ? 'bg-yellow-50 border-yellow-200' : 'bg-green-50 border-green-200') }}">
                                    <div class="text-xs text-gray-600">Variation vs référence</div>
                                    <div class="text-xl font-semibold {{ (($sensitive['severity'] ?? 'neutral') === 'high') ? 'text-red-700' : ((($sensitive['severity'] ?? 'neutral') === 'medium') ? 'text-yellow-700' : 'text-green-700') }}">
                                        @if (is_null($sensitive['change_pct']))
                                            n/a
                                        @else
                                            {{ ($sensitive['change_pct'] >= 0 ? '+' : '') . $sensitive['change_pct'] }}%
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div id="card-monthly-chart" class="toggle-card-section mt-4 border border-gray-200 rounded-xl p-5 bg-white shadow-sm">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="font-medium text-sm">Graphique mensuel (courbes crédits vs débits)</div>
                            <button type="button" onclick="exportChartSVG()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                ↓ Exporter SVG
                            </button>
                        </div>
                        <div class="mt-1 text-xs text-gray-500">
                            Ordre chronologique automatique · mois sans opérations = 0 ·
                            <span class="text-orange-700 font-medium">⬟ anomalie &gt; 2σ</span>
                            <span class="text-gray-400 cursor-help" title="σ (sigma) = écart-type. Un point marqué ⬟ dépasse la moyenne mensuelle de plus de 2 écarts-types : flux statistiquement inhabituel sur la période analysée, qui justifie une vérification des justificatifs correspondants."> (?)</span>
                            ·
                            <span class="text-green-800">--- Moyenne mobile 12 mois</span>
                            <span class="text-gray-400 cursor-help" title="La moyenne mobile 12 mois (MM12) lisse les variations saisonnières en calculant la moyenne des 12 derniers mois glissants. Elle révèle la tendance de fond, indépendamment des pics ponctuels."> (?)</span>
                        </div>
                        @if ($monthlyTotals->count() === 0)
                            <div class="mt-2 text-sm text-gray-600">Pas assez de données pour afficher le graphique.</div>
                        @else
                            @php
                                $chartRows = $monthlyTotals->values();
                                $chartCount = $chartRows->count();
                                $chartWidth = 980;
                                $chartHeight = 320;
                                $padLeft = 68;
                                $padRight = 24;
                                $padTop = 22;
                                $padBottom = 48;
                                $plotWidth = $chartWidth - $padLeft - $padRight;
                                $plotHeight = $chartHeight - $padTop - $padBottom;
                                $maxChartValue = max(1, (float) $monthlyMax);
                                $stepX = $chartCount > 1 ? ($plotWidth / ($chartCount - 1)) : 0;
                                $tickEvery = $chartCount > 96 ? 12 : ($chartCount > 48 ? 6 : ($chartCount > 24 ? 3 : 1));
                                $avgCredits = (float) ($behavioral['avg_credits'] ?? 0);
                                $avgDebits = (float) ($behavioral['avg_debits'] ?? 0);
                            @endphp
                            @php
                                $stdC = (float) ($behavioral['std_credits'] ?? 0);
                                $stdD = (float) ($behavioral['std_debits'] ?? 0);
                                $avgCreditY = $padTop + ($plotHeight - (min(1, $avgCredits / $maxChartValue) * $plotHeight));
                                $avgDebitY = $padTop + ($plotHeight - (min(1, $avgDebits / $maxChartValue) * $plotHeight));
                                $creditPoints = $chartRows->map(function ($row, $index) use ($padLeft, $padTop, $plotHeight, $stepX, $maxChartValue) {
                                    $x = $padLeft + ($index * $stepX);
                                    $y = $padTop + ($plotHeight - ((((float) ($row['credits'] ?? 0)) / $maxChartValue) * $plotHeight));
                                    return number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
                                })->implode(' ');
                                $debitPoints = $chartRows->map(function ($row, $index) use ($padLeft, $padTop, $plotHeight, $stepX, $maxChartValue) {
                                    $x = $padLeft + ($index * $stepX);
                                    $y = $padTop + ($plotHeight - ((((float) ($row['debits'] ?? 0)) / $maxChartValue) * $plotHeight));
                                    return number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
                                })->implode(' ');
                                $ma12CreditPoints = $chartRows->map(function ($row, $index) use ($padLeft, $padTop, $plotHeight, $stepX, $maxChartValue) {
                                    $x = $padLeft + ($index * $stepX);
                                    $y = $padTop + ($plotHeight - ((((float) ($row['ma12_credits'] ?? 0)) / $maxChartValue) * $plotHeight));
                                    return number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
                                })->implode(' ');
                                $ma12DebitPoints = $chartRows->map(function ($row, $index) use ($padLeft, $padTop, $plotHeight, $stepX, $maxChartValue) {
                                    $x = $padLeft + ($index * $stepX);
                                    $y = $padTop + ($plotHeight - ((((float) ($row['ma12_debits'] ?? 0)) / $maxChartValue) * $plotHeight));
                                    return number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
                                })->implode(' ');
                            @endphp

                            {{-- Légende --}}
                            @php
                                $lastMa12C = $chartRows->last()['ma12_credits'] ?? null;
                                $lastMa12D = $chartRows->last()['ma12_debits'] ?? null;
                            @endphp
                            <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs">
                                <span class="inline-flex items-center gap-1.5 text-green-700">
                                    <svg width="16" height="4"><line x1="0" y1="2" x2="16" y2="2" stroke="#4A7C59" stroke-width="2.5"/></svg>Crédits
                                </span>
                                <span class="inline-flex items-center gap-1.5 text-red-700">
                                    <svg width="16" height="4"><line x1="0" y1="2" x2="16" y2="2" stroke="#C0392B" stroke-width="2.5"/></svg>Débits
                                </span>
                                <span class="inline-flex items-center gap-1.5 text-green-800" title="Moyenne glissante sur les 12 derniers mois — lisse les variations saisonnières et révèle la tendance de fond des crédits.">
                                    <svg width="16" height="4"><line x1="0" y1="2" x2="16" y2="2" stroke="#2D5A3D" stroke-width="1.5" stroke-dasharray="4,2"/></svg>
                                    Moy. mobile 12 mois – Crédits
                                    @if (!is_null($lastMa12C))
                                        <span class="font-semibold">({{ number_format((float)$lastMa12C, 0, ',', ' ') }} €/mois)</span>
                                    @endif
                                </span>
                                <span class="inline-flex items-center gap-1.5 text-red-900" title="Moyenne glissante sur les 12 derniers mois — lisse les variations saisonnières et révèle la tendance de fond des débits.">
                                    <svg width="16" height="4"><line x1="0" y1="2" x2="16" y2="2" stroke="#991B1B" stroke-width="1.5" stroke-dasharray="4,2"/></svg>
                                    Moy. mobile 12 mois – Débits
                                    @if (!is_null($lastMa12D))
                                        <span class="font-semibold">({{ number_format((float)$lastMa12D, 0, ',', ' ') }} €/mois)</span>
                                    @endif
                                </span>
                                <span class="text-orange-700 font-medium" title="2σ = deux écarts-types au-dessus de la moyenne. Flux statistiquement inhabituel nécessitant vérification.">⬟ Anomalie &gt; 2σ <span class="font-normal text-gray-400">(flux inhabituel)</span></span>
                                <span class="text-gray-600 tabular-nums">Max: {{ number_format($maxChartValue, 0, ',', ' ') }} €</span>
                                <span class="text-gray-600 tabular-nums">
                                    Moy. mensuelle — Crédits : {{ number_format($avgCredits, 0, ',', ' ') }} € · Débits : {{ number_format($avgDebits, 0, ',', ' ') }} €
                                </span>
                            </div>
                            <div class="mt-1 text-xs text-gray-600">
                                Période affichée: <strong>{{ $chartRows->first()['month'] ?? '—' }} → {{ $chartRows->last()['month'] ?? '—' }}</strong>
                                ({{ $chartCount }} mois)
                            </div>
                            <div class="mt-3 overflow-x-auto">
                                <svg id="monthlyChartSVG" class="min-w-[920px] w-full" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" preserveAspectRatio="none" role="img" aria-label="Courbes mensuelles des crédits et débits" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="0" y="0" width="{{ $chartWidth }}" height="{{ $chartHeight }}" fill="white"/>

                                    {{-- Bandes 1σ autour des moyennes --}}
                                    @if ($stdC > 0)
                                        @php
                                            $bandCTop = max($padTop, $padTop + ($plotHeight - (min($maxChartValue, $avgCredits + $stdC) / $maxChartValue) * $plotHeight));
                                            $bandCBottom = min($padTop + $plotHeight, $padTop + ($plotHeight - (max(0, $avgCredits - $stdC) / $maxChartValue) * $plotHeight));
                                        @endphp
                                        <rect x="{{ $padLeft }}" y="{{ $bandCTop }}" width="{{ $plotWidth }}" height="{{ $bandCBottom - $bandCTop }}" fill="#DCFCE7" opacity="0.4"/>
                                    @endif
                                    @if ($stdD > 0)
                                        @php
                                            $bandDTop = max($padTop, $padTop + ($plotHeight - (min($maxChartValue, $avgDebits + $stdD) / $maxChartValue) * $plotHeight));
                                            $bandDBottom = min($padTop + $plotHeight, $padTop + ($plotHeight - (max(0, $avgDebits - $stdD) / $maxChartValue) * $plotHeight));
                                        @endphp
                                        <rect x="{{ $padLeft }}" y="{{ $bandDTop }}" width="{{ $plotWidth }}" height="{{ $bandDBottom - $bandDTop }}" fill="#FEE2E2" opacity="0.4"/>
                                    @endif

                                    {{-- Grille horizontale et étiquettes axe Y --}}
                                    @for ($g = 0; $g <= 4; $g++)
                                        @php
                                            $yGrid = $padTop + (($plotHeight / 4) * $g);
                                            $labelValue = $maxChartValue * (1 - ($g / 4));
                                        @endphp
                                        <line x1="{{ $padLeft }}" y1="{{ $yGrid }}" x2="{{ $chartWidth - $padRight }}" y2="{{ $yGrid }}" stroke="#E5E7EB" stroke-width="1"/>
                                        <text x="{{ $padLeft - 6 }}" y="{{ $yGrid + 4 }}" text-anchor="end" font-size="10" fill="#6B7280">{{ number_format($labelValue, 0, ',', ' ') }}</text>
                                    @endfor

                                    {{-- Axes --}}
                                    <line x1="{{ $padLeft }}" y1="{{ $padTop }}" x2="{{ $padLeft }}" y2="{{ $chartHeight - $padBottom }}" stroke="#9CA3AF" stroke-width="1.2"/>
                                    <line x1="{{ $padLeft }}" y1="{{ $chartHeight - $padBottom }}" x2="{{ $chartWidth - $padRight }}" y2="{{ $chartHeight - $padBottom }}" stroke="#9CA3AF" stroke-width="1.2"/>

                                    {{-- Lignes de moyenne globale (pointillées) --}}
                                    @if ($avgCredits > 0)
                                        <line x1="{{ $padLeft }}" y1="{{ $avgCreditY }}" x2="{{ $chartWidth - $padRight }}" y2="{{ $avgCreditY }}" stroke="#15803D" stroke-width="1" stroke-dasharray="8,4" opacity="0.65"/>
                                        <text x="{{ $chartWidth - $padRight - 2 }}" y="{{ $avgCreditY - 3 }}" text-anchor="end" font-size="9" fill="#15803D" opacity="0.8">moy.C</text>
                                    @endif
                                    @if ($avgDebits > 0)
                                        <line x1="{{ $padLeft }}" y1="{{ $avgDebitY }}" x2="{{ $chartWidth - $padRight }}" y2="{{ $avgDebitY }}" stroke="#B24040" stroke-width="1" stroke-dasharray="8,4" opacity="0.65"/>
                                        <text x="{{ $chartWidth - $padRight - 2 }}" y="{{ $avgDebitY - 3 }}" text-anchor="end" font-size="9" fill="#B24040" opacity="0.8">moy.D</text>
                                    @endif

                                    {{-- Courbes principales --}}
                                    <polyline fill="none" stroke="#4A7C59" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" points="{{ $creditPoints }}"/>
                                    <polyline fill="none" stroke="#C0392B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" points="{{ $debitPoints }}"/>

                                    {{-- Moyennes mobiles 12 mois (tirets) --}}
                                    @if ($chartCount >= 2)
                                        <polyline fill="none" stroke="#2D5A3D" stroke-width="1.5" stroke-dasharray="5,3" stroke-linecap="round" points="{{ $ma12CreditPoints }}" opacity="0.85"/>
                                        <polyline fill="none" stroke="#9B4040" stroke-width="1.5" stroke-dasharray="5,3" stroke-linecap="round" points="{{ $ma12DebitPoints }}"  opacity="0.85"/>
                                    @endif

                                    {{-- Points, encadrés d'anomalie, ticks X --}}
                                    @foreach ($chartRows as $index => $row)
                                        @php
                                            $xTick = $padLeft + ($index * $stepX);
                                            $creditY = $padTop + ($plotHeight - ((((float) ($row['credits'] ?? 0)) / $maxChartValue) * $plotHeight));
                                            $debitY = $padTop + ($plotHeight - ((((float) ($row['debits'] ?? 0)) / $maxChartValue) * $plotHeight));
                                            $isCAnom = !empty($row['credit_anomaly']);
                                            $isDAnom = !empty($row['debit_anomaly']);
                                        @endphp

                                        @if ($isCAnom)
                                            <rect x="{{ $xTick - 9 }}" y="{{ $creditY - 9 }}" width="18" height="18" fill="none" stroke="#2D5A3D" stroke-width="1.8" rx="3" opacity="0.9"/>
                                        @endif
                                        @if ($isDAnom)
                                            <rect x="{{ $xTick - 9 }}" y="{{ $debitY - 9 }}" width="18" height="18" fill="none" stroke="#7B2B2B" stroke-width="1.8" rx="3" opacity="0.9"/>
                                        @endif

                                        <circle cx="{{ $xTick }}" cy="{{ $creditY }}" r="{{ $isCAnom ? 4.5 : 1.8 }}" fill="{{ $isCAnom ? '#2D5A3D' : '#4A7C59' }}">
                                            <title>{{ $row['month'] }} · Crédit: {{ number_format((float)($row['credits'] ?? 0), 2, ',', ' ') }} €{{ $isCAnom ? ' ⚠ Anomalie z='.$row['credit_z'] : '' }} · MM12: {{ number_format((float)($row['ma12_credits'] ?? 0), 2, ',', ' ') }} €</title>
                                        </circle>
                                        <circle cx="{{ $xTick }}" cy="{{ $debitY }}" r="{{ $isDAnom ? 4.5 : 1.8 }}" fill="{{ $isDAnom ? '#7B2B2B' : '#C0392B' }}">
                                            <title>{{ $row['month'] }} · Débit: {{ number_format((float)($row['debits'] ?? 0), 2, ',', ' ') }} €{{ $isDAnom ? ' ⚠ Anomalie z='.$row['debit_z'] : '' }} · MM12: {{ number_format((float)($row['ma12_debits'] ?? 0), 2, ',', ' ') }} €</title>
                                        </circle>

                                        @if ($index % $tickEvery === 0 || $index === ($chartCount - 1))
                                            <line x1="{{ $xTick }}" y1="{{ $padTop }}" x2="{{ $xTick }}" y2="{{ $chartHeight - $padBottom }}" stroke="#F3F4F6" stroke-width="1"/>
                                            <text x="{{ $xTick }}" y="{{ $chartHeight - 18 }}" text-anchor="middle" font-size="10" fill="#4B5563">{{ $row['month'] }}</text>
                                        @endif
                                    @endforeach
                                </svg>
                            </div>

                            {{-- Tableau récapitulatif des anomalies --}}
                            @php
                                $anomalyRows = $chartRows->filter(fn ($r) => !empty($r['credit_anomaly']) || !empty($r['debit_anomaly']));
                            @endphp
                            @if ($anomalyRows->count() > 0)
                                <div class="mt-4 border border-orange-200 rounded-md p-3 bg-orange-50">
                                    <div class="font-medium text-sm text-orange-800">Anomalies détectées automatiquement (z-score &gt; 2σ)</div>
                                    <div class="text-xs text-orange-700 mt-0.5">Un z-score &gt; 2 indique un montant statistiquement inhabituel par rapport à la distribution mensuelle.</div>
                                    <div class="mt-2 overflow-x-auto">
                                        <table class="min-w-full text-xs">
                                            <thead>
                                                <tr class="text-left text-orange-700 border-b border-orange-200">
                                                    <th class="py-1.5 pr-4">Mois</th>
                                                    <th class="py-1.5 pr-4 text-right">Crédits (€)</th>
                                                    <th class="py-1.5 pr-4 text-right">Z crédits</th>
                                                    <th class="py-1.5 pr-4 text-right">Débits (€)</th>
                                                    <th class="py-1.5 pr-4 text-right">Z débits</th>
                                                    <th class="py-1.5 pr-4">Qualification</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-orange-100">
                                                @foreach ($anomalyRows as $anomRow)
                                                    <tr>
                                                        <td class="py-1.5 pr-4 font-medium">{{ $anomRow['month'] }}</td>
                                                        <td class="py-1.5 pr-4 text-right tabular-nums {{ !empty($anomRow['credit_anomaly']) ? 'font-bold text-green-800' : 'text-gray-700' }}">
                                                            {{ number_format((float)($anomRow['credits'] ?? 0), 2, ',', ' ') }}
                                                        </td>
                                                        <td class="py-1.5 pr-4 text-right tabular-nums {{ !empty($anomRow['credit_anomaly']) ? 'font-bold text-green-800' : 'text-gray-500' }}">
                                                            {{ !empty($anomRow['credit_anomaly']) ? '⚠ ' : '' }}{{ $anomRow['credit_z'] }}
                                                        </td>
                                                        <td class="py-1.5 pr-4 text-right tabular-nums {{ !empty($anomRow['debit_anomaly']) ? 'font-bold text-red-800' : 'text-gray-700' }}">
                                                            {{ number_format((float)($anomRow['debits'] ?? 0), 2, ',', ' ') }}
                                                        </td>
                                                        <td class="py-1.5 pr-4 text-right tabular-nums {{ !empty($anomRow['debit_anomaly']) ? 'font-bold text-red-800' : 'text-gray-500' }}">
                                                            {{ !empty($anomRow['debit_anomaly']) ? '⚠ ' : '' }}{{ $anomRow['debit_z'] }}
                                                        </td>
                                                        <td class="py-1.5 pr-4 text-gray-600">
                                                            @if (!empty($anomRow['credit_anomaly']) && !empty($anomRow['debit_anomaly']))
                                                                Flux entrants et sortants atypiques
                                                            @elseif (!empty($anomRow['credit_anomaly']))
                                                                Entrée exceptionnelle
                                                            @else
                                                                Sortie exceptionnelle
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif

                            <div class="mt-4">
                                <div class="flex items-start justify-between flex-wrap gap-2">
                                    <div class="font-medium text-sm">Résumé annuel (dates et montants précis)</div>
                                    @if ($case->bankAccounts->count() > 1)
                                        <div class="text-xs px-3 py-1.5 rounded-lg max-w-lg" style="background:#fff8e6;border:1px solid #f5d87a;color:#7a5800;">
                                            ⚠ {{ $case->bankAccounts->count() }} comptes chargés — les virements entre comptes du dossier apparaissent en débit ET crédit, ce qui peut gonfler les totaux annuels. Vérifiez via le filtre compte.
                                        </div>
                                    @endif
                                </div>
                                @if (($yearly_totals ?? collect())->count() === 0)
                                    <div class="mt-2 text-sm text-gray-600">Aucune donnée annuelle disponible.</div>
                                @else
                                    <div class="mt-2 overflow-x-auto">
                                        <table class="min-w-full text-sm">
                                            <thead>
                                                <tr class="text-left text-gray-600">
                                                    <th class="py-2 pr-4">Année</th>
                                                    <th class="py-2 pr-4 text-right">Crédits</th>
                                                    <th class="py-2 pr-4 text-right">Débits</th>
                                                    <th class="py-2 pr-4 text-right">Net</th>
                                                    <th class="py-2 pr-4 text-right">Mouvements</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y">
                                                @foreach (($yearly_totals ?? collect()) as $yearRow)
                                                    <tr>
                                                        <td class="py-2 pr-4 whitespace-nowrap">{{ $yearRow['year'] ?? '—' }}</td>
                                                        <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap text-green-700">{{ number_format((float)($yearRow['credits'] ?? 0), 2, ',', ' ') }}</td>
                                                        <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap text-red-700">{{ number_format((float)($yearRow['debits'] ?? 0), 2, ',', ' ') }}</td>
                                                        <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap {{ ((float)($yearRow['net'] ?? 0) < 0) ? 'text-red-700' : 'text-green-700' }}">{{ number_format((float)($yearRow['net'] ?? 0), 2, ',', ' ') }}</td>
                                                        <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap">{{ (int)($yearRow['count'] ?? 0) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                    </div>

                    <div class="mt-4 border border-gray-200 rounded-xl p-5 bg-white shadow-sm">
                        <div class="font-medium text-sm">Pics d’activité (montants élevés)</div>
                        <div class="text-xs text-gray-500 mt-1">Seuil actuel: {{ number_format((float)($behavioral['spike_threshold'] ?? 0), 2, ',', ' ') }}</div>
                        @if ($topSpikes->count() === 0)
                            <div class="mt-2 text-sm text-gray-600">Aucun pic significatif détecté.</div>
                        @else
                            <div class="mt-3 overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-600">
                                            <th class="py-2 pr-4">Date</th>
                                            <th class="py-2 pr-4">Libellé</th>
                                            <th class="py-2 pr-4">Type</th>
                                            <th class="py-2 pr-4 text-right">Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach ($topSpikes as $spike)
                                            <tr>
                                                <td class="py-2 pr-4 whitespace-nowrap">{{ optional($spike->date)->format('Y-m-d') }}</td>
                                                <td class="py-2 pr-4 max-w-md" title="{{ $spike->display_label_full ?? $spike->label }}">
                                                    <span>{{ $spike->display_label ?? $spike->label }}</span>
                                                    @if (!empty($spike->display_label_truncated))
                                                        <button type="button" class="text-xs text-slate-600 underline ml-2" onclick="toggleFullLabel(this)" data-short="{{ e($spike->display_label ?? '') }}" data-full="{{ e($spike->display_label_full ?? '') }}">Voir libellé complet</button>
                                                    @endif
                                                </td>
                                                <td class="py-2 pr-4">{{ ($spike->type ?? '') === 'debit' ? 'Débit' : 'Crédit' }}</td>
                                                <td class="py-2 pr-4 text-right font-medium whitespace-nowrap {{ ($spike->type ?? '') === 'debit' ? 'text-red-700' : 'text-green-700' }}">{{ number_format(abs((float)$spike->amount), 2, ',', ' ') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div id="card-kind-monthly" class="toggle-card-section mt-4 border border-gray-200 rounded-xl p-5 bg-white shadow-sm" x-data="{ monthlyKindsOpen: false, q: '' }">
                        <div class="flex items-center justify-between gap-3">
                            <div class="font-medium text-sm">Mouvements mensuels par genre et sens</div>
                            <button type="button" @click="monthlyKindsOpen = !monthlyKindsOpen" class="inline-flex items-center px-3 py-1.5 text-xs border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <span x-text="monthlyKindsOpen ? 'Masquer le détail' : 'Afficher le détail'"></span>
                            </button>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Bloc replié par défaut pour accélérer la lecture de la page.</div>
                        <div class="mt-3">
                            <x-text-input x-model="q" type="text" class="block w-full" placeholder="Rechercher un mois (ex: 2021-05)" />
                        </div>

                        <div x-show="monthlyKindsOpen" x-cloak x-transition>
                            @php
                                $kmTotals = ['vir_c'=>0,'vir_d'=>0,'chq_c'=>0,'chq_d'=>0,'esp_c'=>0,'esp_d'=>0,'crt_c'=>0,'crt_d'=>0];
                                foreach (($kind_monthly_breakdown ?? collect()) as $_mr) {
                                    $kmTotals['vir_c'] += (float)(($_mr['kinds']['transfer']['credit'] ?? 0));
                                    $kmTotals['vir_d'] += (float)(($_mr['kinds']['transfer']['debit'] ?? 0));
                                    $kmTotals['chq_c'] += (float)(($_mr['kinds']['cheque']['credit'] ?? 0));
                                    $kmTotals['chq_d'] += (float)(($_mr['kinds']['cheque']['debit'] ?? 0));
                                    $kmTotals['esp_c'] += (float)(($_mr['kinds']['cash_withdrawal']['credit'] ?? 0));
                                    $kmTotals['esp_d'] += (float)(($_mr['kinds']['cash_withdrawal']['debit'] ?? 0));
                                    $kmTotals['crt_c'] += (float)(($_mr['kinds']['card']['credit'] ?? 0));
                                    $kmTotals['crt_d'] += (float)(($_mr['kinds']['card']['debit'] ?? 0));
                                }
                            @endphp
                            @if (($kind_monthly_breakdown ?? collect())->count() === 0)
                                <div class="mt-2 text-sm text-gray-600">Aucune donnée mensuelle disponible.</div>
                            @else
                                <div class="mt-3 overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr class="text-left text-gray-600">
                                                <th class="py-2 pr-4">Mois</th>
                                                <th class="py-2 pr-4 text-right">Virement C/D</th>
                                                <th class="py-2 pr-4 text-right">Chèque C/D</th>
                                                <th class="py-2 pr-4 text-right">Espèces C/D</th>
                                                <th class="py-2 pr-4 text-right">Carte C/D</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y">
                                            @foreach (($kind_monthly_breakdown ?? collect()) as $monthRow)
                                                @php
                                                    $monthKinds = (array) ($monthRow['kinds'] ?? []);
                                                    $transfer = (array) ($monthKinds['transfer'] ?? []);
                                                    $cheque = (array) ($monthKinds['cheque'] ?? []);
                                                    $cash = (array) ($monthKinds['cash_withdrawal'] ?? []);
                                                    $other = (array) ($monthKinds['card'] ?? []);
                                                @endphp
                                                <tr x-show="q === '' || '{{ $monthRow['month'] ?? '' }}'.includes(q)">
                                                    <td class="py-2 pr-4 whitespace-nowrap font-medium">{{ $monthRow['month'] ?? '—' }}</td>
                                                    <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap text-gray-700">{{ number_format((float)($transfer['credit'] ?? 0), 2, ',', ' ') }} / {{ number_format((float)($transfer['debit'] ?? 0), 2, ',', ' ') }}</td>
                                                    <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap text-gray-700">{{ number_format((float)($cheque['credit'] ?? 0), 2, ',', ' ') }} / {{ number_format((float)($cheque['debit'] ?? 0), 2, ',', ' ') }}</td>
                                                    <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap text-gray-700">{{ number_format((float)($cash['credit'] ?? 0), 2, ',', ' ') }} / {{ number_format((float)($cash['debit'] ?? 0), 2, ',', ' ') }}</td>
                                                    <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap text-gray-700">{{ number_format((float)($other['credit'] ?? 0), 2, ',', ' ') }} / {{ number_format((float)($other['debit'] ?? 0), 2, ',', ' ') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="border-t-2 border-gray-300 bg-gray-50 font-semibold text-sm">
                                                <td class="py-2 pr-4">Total période</td>
                                                <td class="py-2 pr-4 text-right tabular-nums text-gray-800">{{ number_format($kmTotals['vir_c'], 2, ',', ' ') }} / <span class="text-red-700">{{ number_format($kmTotals['vir_d'], 2, ',', ' ') }}</span></td>
                                                <td class="py-2 pr-4 text-right tabular-nums text-gray-800">{{ number_format($kmTotals['chq_c'], 2, ',', ' ') }} / <span class="text-red-700">{{ number_format($kmTotals['chq_d'], 2, ',', ' ') }}</span></td>
                                                <td class="py-2 pr-4 text-right tabular-nums text-gray-800">{{ number_format($kmTotals['esp_c'], 2, ',', ' ') }} / <span class="text-red-700">{{ number_format($kmTotals['esp_d'], 2, ',', ' ') }}</span></td>
                                                <td class="py-2 pr-4 text-right tabular-nums text-gray-800">{{ number_format($kmTotals['crt_c'], 2, ',', ' ') }} / <span class="text-red-700">{{ number_format($kmTotals['crt_d'], 2, ',', ' ') }}</span></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div id="card-kind-peaks" class="toggle-card-section border border-gray-200 rounded-xl p-5 bg-white shadow-sm" x-data="{ q: '' }">
                            <div class="font-medium text-sm">Pics par catégorie</div>
                            <div class="mt-2">
                                <x-text-input x-model="q" type="text" class="block w-full" placeholder="Rechercher un genre" />
                            </div>
                            @if (($kind_peaks ?? collect())->count() === 0)
                                <div class="mt-2 text-sm text-gray-600">Aucun pic catégoriel disponible.</div>
                            @else
                                <div class="mt-2 overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr class="text-left text-gray-600">
                                                <th class="py-2 pr-4">Genre</th>
                                                <th class="py-2 pr-4 text-right">Crédits</th>
                                                <th class="py-2 pr-4 text-right">Débits</th>
                                                <th class="py-2 pr-4">Pic mensuel</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y">
                                            @foreach (($kind_peaks ?? collect()) as $peak)
                                                <tr x-show="q === '' || @js(mb_strtolower((string)($peak['label'] ?? ''))).includes(q.toLowerCase())">
                                                    <td class="py-2 pr-4 whitespace-nowrap">{{ $peak['label'] ?? ($peak['kind'] ?? '—') }}</td>
                                                    <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap text-green-700">{{ number_format((float)($peak['total_credit'] ?? 0), 2, ',', ' ') }}</td>
                                                    <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap text-red-700">{{ number_format((float)($peak['total_debit'] ?? 0), 2, ',', ' ') }}</td>
                                                    <td class="py-2 pr-4 whitespace-nowrap text-gray-700">
                                                        {{ ($peak['monthly_peak']['month'] ?? '—') }} ·
                                                        <span class="font-medium {{ (($peak['monthly_peak']['type'] ?? '') === 'credit') ? 'text-green-700' : 'text-red-700' }}">{{ number_format((float)($peak['monthly_peak']['amount'] ?? 0), 2, ',', ' ') }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        <div id="card-beneficiary-disambiguation" class="toggle-card-section border rounded-xl p-5 shadow-sm" style="border-color:#e2c96b;background:#fffdf0;"
                             x-data="{ open: {{ (($beneficiary_disambiguation ?? collect())->where('is_ambiguous', true)->count() > 0 || $errors->any() || session('status')) ? 'true' : 'false' }}, showAll: false }">
                            @php
                                $disambigGroups   = $beneficiary_disambiguation ?? collect();
                                $ambiguousCount   = $disambigGroups->where('is_ambiguous', true)->count();
                                $resolvedCount    = $disambigGroups->where('is_ambiguous', false)->count();
                                $overridesMap     = $beneficiary_overrides ?? collect();
                                $identityOptions  = [
                                    'PERSONNE_ANTHONY_GIORDANO'        => 'M. Anthony GIORDANO',
                                    'PERSONNE_M_GIORDANO'              => 'M. GIORDANO (Christian)',
                                    'PERSONNE_LILIANE_GIORDANO_NOVAK'  => 'Mme Liliane GIORDANO / NOVAK',
                                    'COMPTE_COMMUN_GIORDANO'           => 'M. ou Mme GIORDANO (compte commun)',
                                    'PERSONNE_EMILIE_GIORDANO'         => 'Mme Emilie GIORDANO',
                                    'PERSONNE_GIORDANO_NOVAK'          => 'Groupe GIORDANO / NOVAK (à ventiler)',
                                    'EXTERNE'                          => 'Externe / Tiers (hors famille)',
                                ];
                            @endphp
                            {{-- Header always visible — click to open/close --}}
                            <button type="button" @click="open = !open"
                                    class="w-full flex items-center justify-between gap-3 text-left" style="background:none;border:none;padding:0;cursor:pointer;">
                                <div>
                                    <div class="font-semibold text-sm" style="color:#7a5800;">Désambiguïsation des bénéficiaires</div>
                                    <div class="text-xs mt-0.5" style="color:#92400e;">Assignez chaque libellé OCR à la bonne personne. Les corrections s'appliquent dans la concentration.</div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    @if ($ambiguousCount > 0)
                                        <span class="text-xs font-medium px-2 py-1 rounded-full" style="background:#fef3c7;color:#92400e;border:1px solid #fbbf24;">{{ $ambiguousCount }} à corriger</span>
                                    @else
                                        <span class="text-xs font-medium px-2 py-1 rounded-full" style="background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;">✓ Tout assigné</span>
                                    @endif
                                    @if ($resolvedCount > 0)
                                        <span class="text-xs text-gray-500">{{ $resolvedCount }} assigné(s)</span>
                                    @endif
                                    <span class="text-gray-400 text-xs" x-text="open ? '▲' : '▼'"></span>
                                </div>
                            </button>
                            <div x-show="open" style="display:none;">
                            @if ($disambigGroups->count() === 0)
                                <div class="mt-3 text-sm text-gray-500">Aucun libellé GIORDANO/NOVAK détecté dans les transactions.</div>
                            @else
                            <form id="disambiguation-form" method="POST" action="{{ route('cases.beneficiary-overrides', $case) }}" class="mt-4">
                                @csrf
                                @if ($errors->any())
                                    <div class="mb-3 p-3 rounded bg-red-50 border border-red-200 text-xs text-red-700">
                                        @foreach ($errors->all() as $error)
                                            <div>{{ $error }}</div>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="overflow-x-auto rounded-lg border border-amber-200">
                                    <table class="text-sm" style="width:100%;border-collapse:collapse;">
                                        <thead>
                                            <tr class="text-left text-gray-600 bg-amber-50 text-xs">
                                                <th class="py-2 px-3 font-medium" style="min-width:280px;">Libellé OCR</th>
                                                <th class="py-2 px-3 text-right font-medium" style="width:52px;">Occ.</th>
                                                <th class="py-2 px-3 text-right font-medium" style="width:110px;">Total débit</th>
                                                <th class="py-2 px-3 font-medium" style="width:90px;">Dernière op.</th>
                                                <th class="py-2 px-3 font-medium" style="min-width:220px;">Assignation</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-amber-100">
                                            @foreach ($disambigGroups as $i => $group)
                                                @php
                                                    $hasOverride    = (bool) ($group['has_override'] ?? $overridesMap->has($group['normalized_label']));
                                                    $isAmbiguous    = (bool) ($group['is_ambiguous'] ?? false);
                                                    $currentKey     = (string) ($group['identity_key'] ?? 'PERSONNE_GIORDANO_NOVAK');
                                                    $rowBg          = $isAmbiguous ? 'background:#fffde7;' : 'background:#fff;';
                                                    $rawLabel       = (string) ($group['raw_label'] ?? $group['normalized_label']);
                                                    $normLabel      = (string) ($group['normalized_label'] ?? '');
                                                    $rawDiffers     = $rawLabel !== $normLabel;
                                                @endphp
                                                {{-- Par défaut : cacher les lignes déjà assignées (non ambiguës), sauf si showAll actif --}}
                                                <tr style="{{ $rowBg }}" x-data="{ expanded: false }"
                                                    x-show="{{ $isAmbiguous ? 'true' : 'false' }} || showAll">
                                                    <input type="hidden" name="overrides[{{ $i }}][normalized_label]" value="{{ $normLabel }}">
                                                    <td class="py-2 px-3 align-top" style="min-width:280px;">
                                                        <div class="flex items-start gap-1.5">
                                                            <span class="flex-shrink-0 w-2 h-2 rounded-full mt-1.5" style="background:{{ $isAmbiguous ? '#f59e0b' : '#10b981' }};"></span>
                                                            <div style="min-width:0;width:100%;">
                                                                {{-- Libellé normalisé : tronqué par défaut, plein si expanded --}}
                                                                <div class="font-mono text-xs font-medium" style="color:#2E2A25;word-break:break-word;"
                                                                     x-show="expanded">{{ $normLabel }}</div>
                                                                <div class="font-mono text-xs font-medium" style="color:#2E2A25;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:380px;"
                                                                     x-show="!expanded">{{ $normLabel }}</div>
                                                                {{-- Libellé brut OCR (ligne capturée) --}}
                                                                @if ($rawDiffers)
                                                                    <div class="text-[10px] mt-0.5" style="color:#78716c;word-break:break-word;"
                                                                         x-show="expanded">{{ $rawLabel }}</div>
                                                                    <div class="text-[10px] mt-0.5" style="color:#78716c;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:380px;"
                                                                         x-show="!expanded">{{ $rawLabel }}</div>
                                                                @endif
                                                                {{-- Bouton expand/collapse --}}
                                                                <button type="button" @click="expanded = !expanded"
                                                                        class="inline-flex items-center gap-0.5 mt-0.5 text-[10px] font-medium"
                                                                        style="color:#92400e;background:none;border:none;padding:0;cursor:pointer;">
                                                                    <span x-text="expanded ? '▲ Réduire' : '▼ Voir tout'"></span>
                                                                </button>
                                                                @if ($hasOverride)
                                                                    <div class="text-[10px] mt-0.5" style="color:#059669;">✎ correction manuelle active</div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="py-2 px-3 text-right tabular-nums align-top text-xs font-medium" style="width:52px;">{{ $group['count'] }}</td>
                                                    <td class="py-2 px-3 text-right tabular-nums align-top text-xs font-medium text-red-700 whitespace-nowrap" style="width:110px;">{{ number_format((float)($group['total'] ?? 0), 2, ',', ' ') }} €</td>
                                                    <td class="py-2 px-3 align-top text-xs text-gray-500 whitespace-nowrap" style="width:90px;">{{ $group['last_date'] ?? '—' }}</td>
                                                    <td class="py-2 px-3 align-top" style="min-width:220px;">
                                                        <select name="overrides[{{ $i }}][identity_key]" data-original="{{ $currentKey }}" class="text-xs rounded px-2 py-1" style="width:100%;border:1px solid {{ $isAmbiguous ? '#f59e0b' : '#d1d5db' }};background:white;">
                                                            @foreach ($identityOptions as $optKey => $optLabel)
                                                                <option value="{{ $optKey }}" {{ $currentKey === $optKey ? 'selected' : '' }}>{{ $optLabel }}</option>
                                                            @endforeach
                                                            @if ($hasOverride)
                                                                <option value="RESET">↩ Retourner en automatique</option>
                                                            @endif
                                                        </select>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3 flex items-center justify-between gap-3 flex-wrap">
                                    <div class="flex items-center gap-3 text-xs text-gray-500 flex-wrap">
                                        <span><span class="inline-block w-2 h-2 rounded-full" style="background:#f59e0b;"></span> À corriger</span>
                                        <span><span class="inline-block w-2 h-2 rounded-full" style="background:#10b981;"></span> Assigné</span>
                                        @if ($resolvedCount > 0)
                                            <button type="button" @click="showAll = !showAll"
                                                    class="underline decoration-dotted text-xs" style="color:#92400e;background:none;border:none;cursor:pointer;">
                                                <span x-text="showAll ? '▲ Masquer les {{ $resolvedCount }} déjà assignés' : '▼ Voir aussi les {{ $resolvedCount }} déjà assignés'"></span>
                                            </button>
                                        @endif
                                    </div>
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-sm font-medium" style="background:#2E2A25;color:#fff;">
                                        Sauvegarder les corrections
                                    </button>
                                </div>
                            </form>
                            @endif
                            </div>{{-- /x-show open --}}
                        </div>{{-- /card-beneficiary-disambiguation --}}

                    </div>{{-- /md:grid-cols-2 (kind-peaks + disambiguation) --}}

                    <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
                        <div id="card-beneficiary-concentration" class="toggle-card-section border border-gray-200 rounded-xl p-5 bg-white shadow-sm" x-data="{ q: '', minShare: 0 }">
                            <div class="font-medium text-sm">Qui a reçu des versements</div>
                            <div class="mt-0.5 text-xs text-gray-500">Argent sorti du compte et reçu par ces personnes / entités (débits = encaissé par le bénéficiaire).</div>
                            @if ((int)($beneficiary_concentration_excluded_count ?? 0) > 0)
                                <div class="mt-1 text-xs text-gray-400">{{ (int)($beneficiary_concentration_excluded_count ?? 0) }} regroupement(s) de factures récurrentes masqué(s).</div>
                            @endif
                            <div class="mt-2">
                                <x-text-input x-model="q" type="text" class="block w-full" placeholder="Rechercher un bénéficiaire" />
                            </div>
                            @if (($beneficiary_concentration ?? collect())->count() === 0)
                                <div class="mt-2 text-sm text-gray-600">Aucune concentration détectable.</div>
                            @else
                                @php
                                    $concTotalAmount = ($beneficiary_concentration ?? collect())->sum(fn($b) => (float)($b['amount'] ?? 0));
                                    $concTotalCount  = ($beneficiary_concentration ?? collect())->sum(fn($b) => (int)($b['count'] ?? 0));
                                @endphp
                                <div class="mt-2">
                                    <table class="w-full table-fixed text-sm">
                                        <colgroup>
                                            <col style="width:52%">
                                            <col style="width:24%">
                                            <col style="width:12%">
                                            <col style="width:12%">
                                        </colgroup>
                                        <thead>
                                            <tr class="text-left text-gray-500 text-xs uppercase tracking-wide border-b border-gray-200">
                                                <th class="py-2 pr-2">Bénéficiaire</th>
                                                <th class="py-2 pr-2 text-right">Reçu (€)</th>
                                                <th class="py-2 pr-2 text-right">%</th>
                                                <th class="py-2 text-right">Mvts</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y">
                                            @foreach (($beneficiary_concentration ?? collect()) as $beneficiary)
                                                @php
                                                    $detailRowId = 'beneficiary-details-'.md5((string)($beneficiary['key'] ?? $beneficiary['beneficiary'] ?? uniqid()));
                                                @endphp
                                                <tr x-show="q === '' || @js(mb_strtolower((string)($beneficiary['beneficiary'] ?? ''))).includes(q.toLowerCase())">
                                                    <td class="py-1.5 pr-2 align-top">
                                                        <button type="button" class="text-left underline decoration-dotted underline-offset-2 hover:text-green-700 w-full" data-target="{{ $detailRowId }}" onclick="toggleBeneficiaryDetails(this)">
                                                            <span class="block truncate w-full text-sm" title="{{ $beneficiary['beneficiary'] ?? 'INCONNU' }}">{{ $beneficiary['beneficiary'] ?? 'INCONNU' }}</span>
                                                        </button>
                                                    </td>
                                                    <td class="py-1.5 pr-2 text-right tabular-nums whitespace-nowrap align-top font-medium">{{ number_format((float)($beneficiary['amount'] ?? 0), 2, ',', ' ') }}</td>
                                                    <td class="py-1.5 pr-2 text-right tabular-nums whitespace-nowrap align-top text-gray-500 text-xs">{{ number_format((float)($beneficiary['share_pct'] ?? 0), 1, ',', ' ') }}%</td>
                                                    <td class="py-1.5 text-right tabular-nums whitespace-nowrap align-top text-gray-500 text-xs">{{ (int)($beneficiary['count'] ?? 0) }}</td>
                                                </tr>
                                                <tr id="{{ $detailRowId }}" class="hidden bg-gray-50" x-show="q === '' || @js(mb_strtolower((string)($beneficiary['beneficiary'] ?? ''))).includes(q.toLowerCase())">
                                                    <td colspan="4" class="py-3 pr-2 pl-2">
                                                        <div class="flex items-center justify-between gap-3 mb-1">
                                                            <div class="text-xs font-medium text-gray-700">Détail des versements reçus</div>
                                                            <a href="{{ route('cases.show', array_merge(['case' => $case->id], array_filter(array_merge(request()->query(), ['q' => (string)($beneficiary['filter_q'] ?? ''), 'type' => 'debit', 'page' => null])))) }}" class="text-xs text-green-700 hover:underline">↗ Liste filtrée</a>
                                                        </div>
                                                        @if (!empty($beneficiary['aliases'] ?? []))
                                                            <div class="text-[11px] text-gray-400 truncate mb-1" title="{{ implode(' · ', (array)($beneficiary['aliases'] ?? [])) }}">Libellés OCR : {{ implode(' · ', (array)($beneficiary['aliases'] ?? [])) }}</div>
                                                        @endif
                                                        @if (!empty($beneficiary['details'] ?? []))
                                                            <table class="w-full table-fixed text-xs">
                                                                <colgroup><col style="width:22%"><col style="width:58%"><col style="width:20%"></colgroup>
                                                                <thead><tr class="text-left text-gray-500">
                                                                    <th class="py-1 pr-2">Date</th>
                                                                    <th class="py-1 pr-2">Libellé</th>
                                                                    <th class="py-1 text-right">Montant</th>
                                                                </tr></thead>
                                                                <tbody class="divide-y">
                                                                    @foreach ((array)($beneficiary['details'] ?? []) as $detail)
                                                                        <tr>
                                                                            <td class="py-1 pr-2 whitespace-nowrap">{{ $detail['date'] ?? '—' }}</td>
                                                                            <td class="py-1 pr-2 truncate" title="{{ $detail['label_full'] ?? ($detail['label'] ?? '') }}">{{ $detail['label'] ?? '—' }}</td>
                                                                            <td class="py-1 text-right tabular-nums whitespace-nowrap text-red-700 font-semibold">{{ number_format((float)($detail['amount'] ?? 0), 2, ',', ' ') }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="border-t-2 border-gray-300 bg-gray-50 text-sm font-semibold">
                                                <td class="py-2 pr-2">Total versé à des tiers</td>
                                                <td class="py-2 pr-2 text-right tabular-nums text-red-700">{{ number_format($concTotalAmount, 2, ',', ' ') }} €</td>
                                                <td class="py-2 pr-2 text-right text-gray-500 text-xs">100%</td>
                                                <td class="py-2 text-right text-gray-500 text-xs">{{ $concTotalCount }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @endif
                        </div>{{-- /card-beneficiary-concentration --}}

                        {{-- ═══════════════════════════════════════════════════════
                             CONCENTRATION PAR SOURCE (CRÉDITS) — niveau expert
                             Qui a encaissé · Revenus courants vs Produits de cession
                             ═══════════════════════════════════════════════════════ --}}
                        <div id="card-source-concentration" class="toggle-card-section border border-gray-200 rounded-xl p-5 bg-white shadow-sm">
                            <div class="font-medium text-sm">Ce qui a été encaissé sur le(s) compte(s)</div>
                            <div class="mt-0.5 text-xs text-gray-500">Crédits reçus, décomposés entre revenus courants et produits de cessions (ventes de biens).</div>

                            {{-- Badges synthèse --}}
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <div class="px-3 py-1.5 rounded-lg" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                                    <span class="text-gray-500">Total crédits</span>
                                    <span class="ml-1 font-semibold text-green-700 tabular-nums">{{ number_format((float)($total_incoming_for_conc ?? 0), 2, ',', ' ') }} €</span>
                                </div>
                                @if (($total_incoming_ventes ?? 0) > 0)
                                <div class="px-3 py-1.5 rounded-lg" style="background:#fef9ec;border:1px solid #fde68a;">
                                    <span class="text-gray-500">Dont cessions</span>
                                    <span class="ml-1 font-semibold text-amber-700 tabular-nums">{{ number_format((float)($total_incoming_ventes ?? 0), 2, ',', ' ') }} €</span>
                                </div>
                                @endif
                                <div class="px-3 py-1.5 rounded-lg" style="background:#fafaf8;border:1px solid #e5e1d8;">
                                    <span class="text-gray-500">Revenus hors cessions</span>
                                    <span class="ml-1 font-semibold tabular-nums" style="color:#2E2A25;">{{ number_format((float)($total_incoming_hors_ventes ?? 0), 2, ',', ' ') }} €</span>
                                </div>
                            </div>

                            @if (($source_concentration ?? collect())->count() === 0)
                                <div class="mt-2 text-sm text-gray-600">Aucune source de crédit détectable.</div>
                            @else
                                @php
                                    $srcRevenus    = ($source_concentration ?? collect())->where('nature', 'revenu')->values();
                                    $srcPonctuels  = ($source_concentration ?? collect())->where('nature', 'ponctuel')->values();
                                    $srcAssurances = ($source_concentration ?? collect())->where('nature', 'assurance')->values();
                                    $srcFamiliaux  = ($source_concentration ?? collect())->where('nature', 'familial')->values();
                                    $srcVentes     = ($source_concentration ?? collect())->where('nature', 'vente')->values();
                                    // Revenus courants = revenus réguliers + ponctuels non familiaux
                                    $srcRevAndPonct  = $srcRevenus->merge($srcPonctuels)->sortByDesc('amount')->values();
                                    $srcRevTotal     = $srcRevAndPonct->sum(fn($r) => (float)($r['amount'] ?? 0));
                                    $srcRevCount     = $srcRevAndPonct->sum(fn($r) => (int)($r['count'] ?? 0));
                                    $srcVteTotal     = $srcVentes->sum(fn($r) => (float)($r['amount'] ?? 0));
                                    $srcVteCount     = $srcVentes->sum(fn($r) => (int)($r['count'] ?? 0));
                                    $srcAsnTotal     = $srcAssurances->sum(fn($r) => (float)($r['amount'] ?? 0));
                                    $srcAsnCount     = $srcAssurances->sum(fn($r) => (int)($r['count'] ?? 0));
                                    $srcFamTotal     = $srcFamiliaux->sum(fn($r) => (float)($r['amount'] ?? 0));
                                    $srcFamCount     = $srcFamiliaux->sum(fn($r) => (int)($r['count'] ?? 0));
                                @endphp

                                {{-- ─── SECTION 1 : Revenus courants ─────────────────── --}}
                                @if ($srcRevAndPonct->count() > 0)
                                <div class="mt-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-xs font-bold uppercase tracking-wide" style="color:#065F46;">● Revenus &amp; versements reçus (hors cessions)</span>
                                    </div>
                                    <table class="w-full table-fixed text-sm">
                                        <colgroup>
                                            <col style="width:55%">
                                            <col style="width:30%">
                                            <col style="width:15%">
                                        </colgroup>
                                        <thead>
                                            <tr class="text-left text-gray-500 text-xs uppercase tracking-wide border-b border-gray-200">
                                                <th class="py-1.5 pr-2">Source / émetteur</th>
                                                <th class="py-1.5 pr-2 text-right">Encaissé (€)</th>
                                                <th class="py-1.5 text-right">Mvts</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y">
                                            @foreach ($srcRevAndPonct as $src)
                                                @php $srcDetailId = 'src-det-'.md5((string)($src['key'] ?? $src['source'] ?? uniqid())); $hasFunds = !empty($src['fund_tracking'] ?? []); @endphp
                                                <tr>
                                                    <td class="py-1.5 pr-2 align-top">
                                                        <button type="button" class="text-left underline decoration-dotted underline-offset-2 hover:text-green-700 w-full" data-target="{{ $srcDetailId }}" onclick="toggleBeneficiaryDetails(this)">
                                                            <span class="block truncate text-sm" title="{{ $src['source'] ?? 'INCONNU' }}">{{ $src['source'] ?? 'INCONNU' }}</span>
                                                        </button>
                                                    </td>
                                                    <td class="py-1.5 pr-2 text-right tabular-nums whitespace-nowrap align-top text-green-700 font-semibold">{{ number_format((float)($src['amount'] ?? 0), 2, ',', ' ') }}</td>
                                                    <td class="py-1.5 text-right tabular-nums whitespace-nowrap align-top text-gray-500 text-xs">{{ (int)($src['count'] ?? 0) }}</td>
                                                </tr>
                                                <tr id="{{ $srcDetailId }}" class="hidden bg-gray-50">
                                                    <td colspan="3" class="py-2 px-2">
                                                        @if (!empty($src['aliases'] ?? []))
                                                            <div class="text-[11px] text-gray-400 truncate mb-1">Libellés OCR : {{ implode(' · ', (array)($src['aliases'] ?? [])) }}</div>
                                                        @endif
                                                        @if (!empty($src['details'] ?? []))
                                                            <table class="w-full table-fixed text-xs">
                                                                <colgroup><col style="width:22%"><col style="width:58%"><col style="width:20%"></colgroup>
                                                                <tbody class="divide-y">
                                                                    @foreach (array_slice((array)($src['details'] ?? []), 0, 15) as $detail)
                                                                        <tr>
                                                                            <td class="py-0.5 pr-2 whitespace-nowrap text-gray-500">{{ $detail['date'] ?? '—' }}</td>
                                                                            <td class="py-0.5 pr-2 truncate" title="{{ $detail['label_full'] ?? ($detail['label'] ?? '') }}">{{ $detail['label'] ?? '—' }}</td>
                                                                            <td class="py-0.5 text-right tabular-nums text-green-700 font-semibold whitespace-nowrap">{{ number_format((float)($detail['amount'] ?? 0), 2, ',', ' ') }} €</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="border-t-2 font-semibold text-sm" style="border-color:#bbf7d0;background:#f0fdf4;">
                                                <td class="py-1.5 pr-2 text-green-800">Total revenus courants</td>
                                                <td class="py-1.5 pr-2 text-right tabular-nums text-green-700">{{ number_format($srcRevTotal, 2, ',', ' ') }} €</td>
                                                <td class="py-1.5 text-right text-gray-500 text-xs">{{ $srcRevCount }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                @endif

                                {{-- ─── SECTION 1b : Remboursements assurance ─────────── --}}
                                @if ($srcAssurances->count() > 0)
                                <div class="mt-5">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-bold uppercase tracking-wide" style="color:#0369a1;">&#9679; Remboursements d'assurance</span>
                                    </div>
                                    <div class="text-[11px] mb-2" style="color:#0369a1;">Indemnisations reçues d'assureurs. Non comptabilisées dans les revenus courants.</div>
                                    <table class="w-full table-fixed text-sm">
                                        <colgroup><col style="width:55%"><col style="width:30%"><col style="width:15%"></colgroup>
                                        <thead><tr class="text-left text-gray-500 text-xs uppercase tracking-wide border-b border-gray-200">
                                            <th class="py-1.5 pr-2">Source / assureur</th><th class="py-1.5 pr-2 text-right">Encaissé (€)</th><th class="py-1.5 text-right">Mvts</th>
                                        </tr></thead>
                                        <tbody class="divide-y">
                                            @foreach ($srcAssurances as $src)
                                                @php $srcDetailId = 'src-det-asn-'.md5((string)($src['key'] ?? $src['source'] ?? uniqid())); @endphp
                                                <tr>
                                                    <td class="py-1.5 pr-2 align-top"><button type="button" class="text-left underline decoration-dotted underline-offset-2 hover:text-blue-700 w-full" data-target="{{ $srcDetailId }}" onclick="toggleBeneficiaryDetails(this)"><span class="block truncate text-sm" title="{{ $src['source'] ?? 'INCONNU' }}">{{ $src['source'] ?? 'INCONNU' }}</span></button></td>
                                                    <td class="py-1.5 pr-2 text-right tabular-nums whitespace-nowrap font-semibold" style="color:#0369a1;">{{ number_format((float)($src['amount'] ?? 0), 2, ',', ' ') }}</td>
                                                    <td class="py-1.5 text-right tabular-nums whitespace-nowrap text-gray-500 text-xs">{{ (int)($src['count'] ?? 0) }}</td>
                                                </tr>
                                                <tr id="{{ $srcDetailId }}" class="hidden bg-gray-50"><td colspan="3" class="py-2 px-2">
                                                    @if (!empty($src['aliases'] ?? []))<div class="text-[11px] text-gray-400 truncate mb-1">Libellés OCR : {{ implode(' · ', (array)($src['aliases'] ?? [])) }}</div>@endif
                                                    @if (!empty($src['details'] ?? []))<table class="w-full table-fixed text-xs"><colgroup><col style="width:22%"><col style="width:58%"><col style="width:20%"></colgroup><tbody class="divide-y">
                                                        @foreach (array_slice((array)($src['details'] ?? []), 0, 15) as $detail)
                                                            <tr><td class="py-0.5 pr-2 whitespace-nowrap text-gray-500">{{ $detail['date'] ?? '—' }}</td><td class="py-0.5 pr-2 truncate" title="{{ $detail['label_full'] ?? ($detail['label'] ?? '') }}">{{ $detail['label'] ?? '—' }}</td><td class="py-0.5 text-right tabular-nums font-semibold whitespace-nowrap" style="color:#0369a1;">{{ number_format((float)($detail['amount'] ?? 0), 2, ',', ' ') }}&nbsp;€</td></tr>
                                                        @endforeach
                                                    </tbody></table>@endif
                                                </td></tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot><tr class="border-t-2 font-semibold text-sm" style="border-color:#bae6fd;background:#f0f9ff;">
                                            <td class="py-1.5 pr-2" style="color:#0369a1;">Total remboursements assurance</td>
                                            <td class="py-1.5 pr-2 text-right tabular-nums" style="color:#0369a1;">{{ number_format($srcAsnTotal, 2, ',', ' ') }}&nbsp;€</td>
                                            <td class="py-1.5 text-right text-gray-500 text-xs">{{ $srcAsnCount }}</td>
                                        </tr></tfoot>
                                    </table>
                                </div>
                                @endif

                                {{-- ─── SECTION 1c : Virements familiaux ───────────────── --}}
                                @if ($srcFamiliaux->count() > 0)
                                <div class="mt-5">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-bold uppercase tracking-wide" style="color:#b45309;">&#9679; Virements familiaux (intra-dossier)</span>
                                    </div>
                                    <div class="text-[11px] mb-2" style="color:#b45309;">Flux reçus de membres de la famille identifiés dans le dossier. Non comptabilisés dans les revenus — à analyser séparément.</div>
                                    <table class="w-full table-fixed text-sm">
                                        <colgroup><col style="width:55%"><col style="width:30%"><col style="width:15%"></colgroup>
                                        <thead><tr class="text-left text-gray-500 text-xs uppercase tracking-wide border-b border-gray-200">
                                            <th class="py-1.5 pr-2">Membre / émetteur</th><th class="py-1.5 pr-2 text-right">Reçu (€)</th><th class="py-1.5 text-right">Mvts</th>
                                        </tr></thead>
                                        <tbody class="divide-y">
                                            @foreach ($srcFamiliaux as $src)
                                                @php $srcDetailId = 'src-det-fam-'.md5((string)($src['key'] ?? $src['source'] ?? uniqid())); @endphp
                                                <tr>
                                                    <td class="py-1.5 pr-2 align-top"><button type="button" class="text-left underline decoration-dotted underline-offset-2 hover:text-amber-700 w-full" data-target="{{ $srcDetailId }}" onclick="toggleBeneficiaryDetails(this)"><span class="block truncate text-sm font-medium" title="{{ $src['source'] ?? 'INCONNU' }}">{{ $src['source'] ?? 'INCONNU' }}</span></button></td>
                                                    <td class="py-1.5 pr-2 text-right tabular-nums whitespace-nowrap font-semibold" style="color:#b45309;">{{ number_format((float)($src['amount'] ?? 0), 2, ',', ' ') }}</td>
                                                    <td class="py-1.5 text-right tabular-nums whitespace-nowrap text-gray-500 text-xs">{{ (int)($src['count'] ?? 0) }}</td>
                                                </tr>
                                                <tr id="{{ $srcDetailId }}" class="hidden bg-gray-50"><td colspan="3" class="py-2 px-2">
                                                    @if (!empty($src['aliases'] ?? []))<div class="text-[11px] text-gray-400 truncate mb-1">Libellés OCR : {{ implode(' · ', (array)($src['aliases'] ?? [])) }}</div>@endif
                                                    @if (!empty($src['details'] ?? []))<table class="w-full table-fixed text-xs"><colgroup><col style="width:22%"><col style="width:58%"><col style="width:20%"></colgroup><tbody class="divide-y">
                                                        @foreach (array_slice((array)($src['details'] ?? []), 0, 15) as $detail)
                                                            <tr><td class="py-0.5 pr-2 whitespace-nowrap text-gray-500">{{ $detail['date'] ?? '—' }}</td><td class="py-0.5 pr-2 truncate" title="{{ $detail['label_full'] ?? ($detail['label'] ?? '') }}">{{ $detail['label'] ?? '—' }}</td><td class="py-0.5 text-right tabular-nums font-semibold whitespace-nowrap" style="color:#b45309;">{{ number_format((float)($detail['amount'] ?? 0), 2, ',', ' ') }}&nbsp;€</td></tr>
                                                        @endforeach
                                                    </tbody></table>@endif
                                                </td></tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot><tr class="border-t-2 font-semibold text-sm" style="border-color:#fde68a;background:#fffbeb;">
                                            <td class="py-1.5 pr-2" style="color:#b45309;">Total virements familiaux reçus</td>
                                            <td class="py-1.5 pr-2 text-right tabular-nums" style="color:#b45309;">{{ number_format($srcFamTotal, 2, ',', ' ') }}&nbsp;€</td>
                                            <td class="py-1.5 text-right text-gray-500 text-xs">{{ $srcFamCount }}</td>
                                        </tr></tfoot>
                                    </table>
                                </div>
                                @endif

                                {{-- ─── SECTION 2 : Produits de cessions ─────────────── --}}
                                @if ($srcVentes->count() > 0)
                                <div class="mt-5">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-xs font-bold uppercase tracking-wide" style="color:#92400E;">● Produits de cessions encaissés (ventes de biens)</span>
                                    </div>
                                    <div class="text-[11px] text-amber-700 mb-2">Ventes de véhicules, biens immobiliers, terrains, etc. identifiées dans les libellés. 🔍 = suivi des fonds disponible.</div>
                                    <table class="w-full table-fixed text-sm">
                                        <colgroup>
                                            <col style="width:55%">
                                            <col style="width:30%">
                                            <col style="width:15%">
                                        </colgroup>
                                        <thead>
                                            <tr class="text-left text-gray-500 text-xs uppercase tracking-wide border-b border-gray-200">
                                                <th class="py-1.5 pr-2">Bien vendu / source</th>
                                                <th class="py-1.5 pr-2 text-right">Encaissé (€)</th>
                                                <th class="py-1.5 text-right">Mvts</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y">
                                            @foreach ($srcVentes as $src)
                                                @php $srcDetailId = 'src-vte-'.md5((string)($src['key'] ?? $src['source'] ?? uniqid())); $hasFunds = !empty($src['fund_tracking'] ?? []); @endphp
                                                <tr>
                                                    <td class="py-1.5 pr-2 align-top">
                                                        <button type="button" class="text-left underline decoration-dotted underline-offset-2 hover:text-amber-700 w-full" data-target="{{ $srcDetailId }}" onclick="toggleBeneficiaryDetails(this)">
                                                            <span class="flex items-center gap-1">
                                                                @if ($hasFunds)<span class="text-amber-500 flex-shrink-0 text-xs">🔍</span>@endif
                                                                <span class="block truncate text-sm" title="{{ $src['source'] ?? 'INCONNU' }}">{{ $src['source'] ?? 'INCONNU' }}</span>
                                                            </span>
                                                        </button>
                                                    </td>
                                                    <td class="py-1.5 pr-2 text-right tabular-nums whitespace-nowrap align-top text-amber-700 font-semibold">{{ number_format((float)($src['amount'] ?? 0), 2, ',', ' ') }}</td>
                                                    <td class="py-1.5 text-right tabular-nums whitespace-nowrap align-top text-gray-500 text-xs">{{ (int)($src['count'] ?? 0) }}</td>
                                                </tr>
                                                <tr id="{{ $srcDetailId }}" class="hidden bg-amber-50/50">
                                                    <td colspan="3" class="py-3 px-2">
                                                        @if (!empty($src['aliases'] ?? []))
                                                            <div class="text-[11px] text-gray-400 truncate mb-2">Libellés OCR : {{ implode(' · ', (array)($src['aliases'] ?? [])) }}</div>
                                                        @endif
                                                        {{-- SUIVI DES FONDS --}}
                                                        @if ($hasFunds)
                                                            @foreach ($src['fund_tracking'] as $ft)
                                                                <div class="mb-3 p-3 rounded-lg border-l-4" style="background:linear-gradient(135deg,#fffdf0,#fef9e7);border-left-color:#f59e0b;">
                                                                    <div class="text-xs font-bold mb-1" style="color:#92400E;">
                                                                        🔍 {{ number_format((float)($ft['credit_amount'] ?? 0), 2, ',', ' ') }} € encaissé le {{ \Carbon\Carbon::parse($ft['credit_date'] ?? 'now')->format('d/m/Y') }} — où est allé cet argent dans les {{ $ft['window_days'] ?? 90 }} jours suivants ?
                                                                    </div>
                                                                    <table class="w-full table-fixed text-xs">
                                                                        <colgroup><col style="width:45%"><col style="width:25%"><col style="width:10%"><col style="width:20%"></colgroup>
                                                                        <thead><tr style="color:#92400E;">
                                                                            <th class="py-1 pr-2 text-left font-semibold">Bénéficiaire des fonds</th>
                                                                            <th class="py-1 pr-2 text-right font-semibold">Montant</th>
                                                                            <th class="py-1 pr-2 text-right font-semibold">Mvts</th>
                                                                            <th class="py-1 text-right font-semibold">% du prix</th>
                                                                        </tr></thead>
                                                                        <tbody class="divide-y" style="border-color:#fde68a;">
                                                                            @php $ftTotal = collect($ft['destinations'] ?? [])->sum(fn($d) => (float)($d['amount'] ?? 0)); @endphp
                                                                            @foreach (($ft['destinations'] ?? []) as $dest)
                                                                                @php $destPct = $ft['credit_amount'] > 0 ? round(($dest['amount'] / $ft['credit_amount']) * 100, 0) : 0; @endphp
                                                                                <tr>
                                                                                    <td class="py-1 pr-2 truncate text-gray-700" title="{{ $dest['beneficiary'] ?? '' }}">{{ $dest['beneficiary'] ?? '—' }}</td>
                                                                                    <td class="py-1 pr-2 text-right tabular-nums text-red-700 font-semibold whitespace-nowrap">{{ number_format((float)($dest['amount'] ?? 0), 2, ',', ' ') }} €</td>
                                                                                    <td class="py-1 pr-2 text-right text-gray-500">{{ $dest['count'] ?? 0 }}</td>
                                                                                    <td class="py-1 text-right">
                                                                                        <span class="text-amber-700 font-semibold">{{ $destPct }}%</span>
                                                                                        <span class="inline-block h-1.5 rounded-full ml-1 align-middle" style="width:{{ min(40, $destPct) }}px;background:#f59e0b;opacity:0.6;"></span>
                                                                                    </td>
                                                                                </tr>
                                                                            @endforeach
                                                                            @if ($ftTotal < $ft['credit_amount'] * 0.95)
                                                                                <tr class="text-gray-400 italic">
                                                                                    <td class="py-1 pr-2">Solde non retracé</td>
                                                                                    <td class="py-1 pr-2 text-right tabular-nums">{{ number_format(round($ft['credit_amount'] - $ftTotal, 2), 2, ',', ' ') }} €</td>
                                                                                    <td colspan="2"></td>
                                                                                </tr>
                                                                            @endif
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            <div class="text-xs text-gray-400 italic mb-2">Aucun débit significatif retracé dans les 90 j suivant l'encaissement.</div>
                                                        @endif
                                                        {{-- Détail transactions --}}
                                                        @if (!empty($src['details'] ?? []))
                                                            <table class="w-full table-fixed text-xs">
                                                                <colgroup><col style="width:22%"><col style="width:58%"><col style="width:20%"></colgroup>
                                                                <tbody class="divide-y">
                                                                    @foreach (array_slice((array)($src['details'] ?? []), 0, 15) as $detail)
                                                                        <tr>
                                                                            <td class="py-0.5 pr-2 whitespace-nowrap text-gray-500">{{ $detail['date'] ?? '—' }}</td>
                                                                            <td class="py-0.5 pr-2 truncate" title="{{ $detail['label_full'] ?? ($detail['label'] ?? '') }}">{{ $detail['label'] ?? '—' }}</td>
                                                                            <td class="py-0.5 text-right tabular-nums text-amber-700 font-semibold whitespace-nowrap">{{ number_format((float)($detail['amount'] ?? 0), 2, ',', ' ') }} €</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="border-t-2 font-semibold text-sm" style="border-color:#fde68a;background:#fef9ec;">
                                                <td class="py-1.5 pr-2 text-amber-800">Total produits de cessions</td>
                                                <td class="py-1.5 pr-2 text-right tabular-nums text-amber-700">{{ number_format($srcVteTotal, 2, ',', ' ') }} €</td>
                                                <td class="py-1.5 text-right text-gray-500 text-xs">{{ $srcVteCount }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                @endif
                            @endif
                        </div>{{-- /card-source-concentration --}}

                    </div>{{-- /lg:grid-cols-2 concentration --}}

                    {{-- ═══════════════════════════════════════════════════════
                         BILAN GLOBAL CONTREPARTIES (TOP 10)
                         Entrées depuis la contrepartie + sorties vers la contrepartie
                         ═══════════════════════════════════════════════════════ --}}
                    <div id="card-family-bilateral" class="toggle-card-section mt-4 border border-gray-200 rounded-xl p-5 bg-white shadow-sm">
                        <div class="font-medium text-sm">Bilan des flux entrants/sortants par contrepartie — Top 10</div>
                        <div class="mt-0.5 text-xs text-gray-500">
                            Période analysée complète&nbsp;: ce qui <span class="font-medium text-green-700">entre sur les comptes</span> depuis cette contrepartie, ce qui <span class="font-medium text-red-600">sort des comptes</span> vers cette contrepartie, et le net final.
                        </div>
                        @php
                            $labelOverrides = [
                                'PERSONNE_M_GIORDANO'             => 'M. GIORDANO Christian',
                                'COMPTE_COMMUN_GIORDANO'          => 'M. ou Mme GIORDANO (compte commun)',
                                'PERSONNE_LILIANE_GIORDANO_NOVAK' => 'Mme Liliane GIORDANO / NOVAK',
                            ];

                            $srcByKey = ($source_concentration ?? collect())->keyBy('key');
                            $benByKey = ($beneficiary_concentration ?? collect())->keyBy('key');
                            $allKeys = $srcByKey->keys()->merge($benByKey->keys())->unique()->values();

                            $allCounterpartyFlows = $allKeys
                                ->map(function ($key) use ($srcByKey, $benByKey, $labelOverrides) {
                                    $incoming = (float) (($srcByKey[$key]['amount'] ?? 0));
                                    $outgoing = (float) (($benByKey[$key]['amount'] ?? 0));
                                    $total = $incoming + $outgoing;

                                    $label = (string) (($benByKey[$key]['beneficiary'] ?? $srcByKey[$key]['source'] ?? $key));
                                    if (isset($labelOverrides[$key])) {
                                        $label = (string) $labelOverrides[$key];
                                    }

                                    return [
                                        'key'             => (string) $key,
                                        'label'           => $label,
                                        'incoming'        => $incoming,
                                        'outgoing'        => $outgoing,
                                        'net_account'     => $incoming - $outgoing,
                                        'net_counterparty'=> $outgoing - $incoming,
                                        'total'           => $total,
                                        'incoming_count'  => (int) (($srcByKey[$key]['count'] ?? 0)),
                                        'outgoing_count'  => (int) (($benByKey[$key]['count'] ?? 0)),
                                        'incoming_details'=> (array) (($srcByKey[$key]['details'] ?? [])),
                                        'outgoing_details'=> (array) (($benByKey[$key]['details'] ?? [])),
                                    ];
                                })
                                ->filter(fn($r) => $r['total'] > 0)
                                ->values();

                            $topLimit = 10;
                            $rankedByNetPerceived = $allCounterpartyFlows->sortByDesc('net_counterparty')->values();
                            $topCounterpartyFlows = $rankedByNetPerceived->take($topLimit);
                            $topCounterpartyByTotal = $allCounterpartyFlows->sortByDesc('total')->take($topLimit)->values();

                            $focusRows = $allCounterpartyFlows
                                ->whereIn('key', ['PERSONNE_M_GIORDANO', 'COMPTE_COMMUN_GIORDANO'])
                                ->values();

                            $displayCounterpartyFlows = $topCounterpartyFlows
                                ->merge($focusRows)
                                ->unique('key')
                                ->values();
                        @endphp

                        @if ($displayCounterpartyFlows->count() === 0)
                            <div class="mt-3 text-sm text-gray-500">Aucun flux détecté.</div>
                        @else
                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full table-fixed text-sm">
                                <colgroup>
                                    <col style="width:30%">
                                    <col style="width:15%">
                                    <col style="width:6%">
                                    <col style="width:15%">
                                    <col style="width:6%">
                                    <col style="width:14%">
                                    <col style="width:14%">
                                    <col style="width:10%">
                                </colgroup>
                                <thead>
                                    <tr class="text-left text-gray-500 text-xs uppercase tracking-wide border-b border-gray-200">
                                        <th class="py-2 pr-2">Contrepartie</th>
                                        <th class="py-2 pr-2 text-right text-green-700">Entrées depuis elle&nbsp;(€)</th>
                                        <th class="py-2 pr-1 text-right text-gray-400">Mvts</th>
                                        <th class="py-2 pr-2 text-right text-red-600">Sorties vers elle&nbsp;(€)</th>
                                        <th class="py-2 pr-1 text-right text-gray-400">Mvts</th>
                                        <th class="py-2 pr-2 text-right">Net pour les comptes&nbsp;(€)</th>
                                        <th class="py-2 pr-2 text-right">Net perçu par la contrepartie&nbsp;(€)</th>
                                        <th class="py-2 text-right text-gray-400">Total flux</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($displayCounterpartyFlows as $bf)
                                        @php
                                            $bfId = 'bf-det-'.md5($bf['key']);
                                            $netAccountPositive = $bf['net_account'] >= 0;
                                            $netCounterpartyPositive = $bf['net_counterparty'] >= 0;
                                        @endphp
                                        <tr class="hover:bg-gray-50 cursor-pointer" onclick="document.getElementById('{{ $bfId }}').classList.toggle('hidden')">
                                            <td class="py-2 pr-2 font-medium truncate" title="{{ $bf['label'] }}">{{ $bf['label'] }}</td>
                                            <td class="py-2 pr-2 text-right tabular-nums font-semibold {{ $bf['incoming'] > 0 ? 'text-green-700' : 'text-gray-300' }}">
                                                {{ $bf['incoming'] > 0 ? number_format($bf['incoming'], 2, ',', ' ') : '—' }}
                                            </td>
                                            <td class="py-2 pr-1 text-right tabular-nums text-gray-400 text-xs">{{ $bf['incoming_count'] ?: '—' }}</td>
                                            <td class="py-2 pr-2 text-right tabular-nums font-semibold {{ $bf['outgoing'] > 0 ? 'text-red-600' : 'text-gray-300' }}">
                                                {{ $bf['outgoing'] > 0 ? number_format($bf['outgoing'], 2, ',', ' ') : '—' }}
                                            </td>
                                            <td class="py-2 pr-1 text-right tabular-nums text-gray-400 text-xs">{{ $bf['outgoing_count'] ?: '—' }}</td>
                                            <td class="py-2 pr-2 text-right tabular-nums font-bold {{ $netAccountPositive ? 'text-green-700' : 'text-red-700' }}">
                                                {{ ($netAccountPositive ? '+' : '') . number_format($bf['net_account'], 2, ',', ' ') }}
                                            </td>
                                            <td class="py-2 pr-2 text-right tabular-nums font-bold {{ $netCounterpartyPositive ? 'text-red-700' : 'text-green-700' }}">
                                                {{ ($netCounterpartyPositive ? '+' : '') . number_format($bf['net_counterparty'], 2, ',', ' ') }}
                                            </td>
                                            <td class="py-2 text-right tabular-nums text-gray-500 text-xs">{{ number_format($bf['total'], 2, ',', ' ') }}</td>
                                        </tr>
                                        {{-- Détail dépliable --}}
                                        <tr id="{{ $bfId }}" class="hidden bg-gray-50">
                                            <td colspan="8" class="py-3 px-3">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    {{-- Entrées depuis cette contrepartie --}}
                                                    @if (!empty($bf['incoming_details']))
                                                    <div>
                                                        <div class="text-xs font-semibold text-green-700 mb-1">↙ Entrées depuis {{ $bf['label'] }}</div>
                                                        <table class="w-full table-fixed text-xs">
                                                            <colgroup><col style="width:22%"><col style="width:58%"><col style="width:20%"></colgroup>
                                                            <tbody class="divide-y">
                                                                @foreach (array_slice($bf['incoming_details'], 0, 10) as $sd)
                                                                    <tr>
                                                                        <td class="py-0.5 pr-2 whitespace-nowrap text-gray-500">{{ $sd['date'] ?? '—' }}</td>
                                                                        <td class="py-0.5 pr-2 truncate" title="{{ $sd['label_full'] ?? ($sd['label'] ?? '') }}">{{ $sd['label'] ?? '—' }}</td>
                                                                        <td class="py-0.5 text-right tabular-nums text-green-700 font-semibold whitespace-nowrap">{{ number_format((float)($sd['amount'] ?? 0), 2, ',', ' ') }}&nbsp;€</td>
                                                                    </tr>
                                                                @endforeach
                                                                @if (count($bf['incoming_details']) > 10)
                                                                    <tr><td colspan="3" class="py-1 text-gray-400 text-[10px]">... {{ count($bf['incoming_details']) - 10 }} opération(s) de plus</td></tr>
                                                                @endif
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    @endif
                                                    {{-- Sorties vers cette contrepartie --}}
                                                    @if (!empty($bf['outgoing_details']))
                                                    <div>
                                                        <div class="text-xs font-semibold text-red-600 mb-1">↗ Sorties vers {{ $bf['label'] }}</div>
                                                        <table class="w-full table-fixed text-xs">
                                                            <colgroup><col style="width:22%"><col style="width:58%"><col style="width:20%"></colgroup>
                                                            <tbody class="divide-y">
                                                                @foreach (array_slice($bf['outgoing_details'], 0, 10) as $bd)
                                                                    <tr>
                                                                        <td class="py-0.5 pr-2 whitespace-nowrap text-gray-500">{{ $bd['date'] ?? '—' }}</td>
                                                                        <td class="py-0.5 pr-2 truncate" title="{{ $bd['label_full'] ?? ($bd['label'] ?? '') }}">{{ $bd['label'] ?? '—' }}</td>
                                                                        <td class="py-0.5 text-right tabular-nums text-red-600 font-semibold whitespace-nowrap">{{ number_format((float)($bd['amount'] ?? 0), 2, ',', ' ') }}&nbsp;€</td>
                                                                    </tr>
                                                                @endforeach
                                                                @if (count($bf['outgoing_details']) > 10)
                                                                    <tr><td colspan="3" class="py-1 text-gray-400 text-[10px]">... {{ count($bf['outgoing_details']) - 10 }} opération(s) de plus</td></tr>
                                                                @endif
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-t-2 border-gray-300 bg-gray-50 text-sm font-semibold">
                                        <td class="py-2 pr-2">Total affiché (Top {{ $topLimit }} + lignes clés)</td>
                                        <td class="py-2 pr-2 text-right tabular-nums text-green-700">{{ number_format($displayCounterpartyFlows->sum('incoming'), 2, ',', ' ') }}&nbsp;€</td>
                                        <td class="py-2 pr-1 text-right text-gray-500 text-xs">{{ $displayCounterpartyFlows->sum('incoming_count') }}</td>
                                        <td class="py-2 pr-2 text-right tabular-nums text-red-600">{{ number_format($displayCounterpartyFlows->sum('outgoing'), 2, ',', ' ') }}&nbsp;€</td>
                                        <td class="py-2 pr-1 text-right text-gray-500 text-xs">{{ $displayCounterpartyFlows->sum('outgoing_count') }}</td>
                                        @php
                                            $totalNetAccount = $displayCounterpartyFlows->sum('net_account');
                                            $totalNetCounterparty = $displayCounterpartyFlows->sum('net_counterparty');
                                        @endphp
                                        <td class="py-2 pr-2 text-right tabular-nums font-bold {{ $totalNetAccount >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                            {{ ($totalNetAccount >= 0 ? '+' : '') . number_format($totalNetAccount, 2, ',', ' ') }}&nbsp;€
                                        </td>
                                        <td class="py-2 pr-2 text-right tabular-nums font-bold {{ $totalNetCounterparty >= 0 ? 'text-red-700' : 'text-green-700' }}">
                                            {{ ($totalNetCounterparty >= 0 ? '+' : '') . number_format($totalNetCounterparty, 2, ',', ' ') }}&nbsp;€
                                        </td>
                                        <td class="py-2 text-right tabular-nums text-gray-500 text-xs">{{ number_format($displayCounterpartyFlows->sum('total'), 2, ',', ' ') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="mt-2 text-[11px] text-gray-400">
                            ℹ️ Cliquer sur une ligne pour afficher le détail des opérations. &nbsp;|&nbsp;
                            "Entrées depuis elle" = crédits entrants sur vos comptes. &nbsp;|&nbsp;
                            "Sorties vers elle" = débits sortants de vos comptes. &nbsp;|&nbsp;
                            Classement = net perçu par la contrepartie (sorties - entrées).
                        </div>

                        <div class="mt-5 overflow-x-auto">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Classement alternatif : Top {{ $topLimit }} par flux total (entrées + sorties)</div>
                            <table class="w-full table-fixed text-sm">
                                <colgroup>
                                    <col style="width:42%">
                                    <col style="width:19%">
                                    <col style="width:19%">
                                    <col style="width:20%">
                                </colgroup>
                                <thead>
                                    <tr class="text-left text-gray-500 text-xs uppercase tracking-wide border-b border-gray-200">
                                        <th class="py-2 pr-2">Contrepartie</th>
                                        <th class="py-2 pr-2 text-right text-green-700">Entrées depuis elle&nbsp;(€)</th>
                                        <th class="py-2 pr-2 text-right text-red-600">Sorties vers elle&nbsp;(€)</th>
                                        <th class="py-2 text-right text-gray-500">Flux total&nbsp;(€)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($topCounterpartyByTotal as $rank)
                                        <tr>
                                            <td class="py-1.5 pr-2 truncate" title="{{ $rank['label'] }}">{{ $rank['label'] }}</td>
                                            <td class="py-1.5 pr-2 text-right tabular-nums text-green-700 font-medium">{{ number_format((float)($rank['incoming'] ?? 0), 2, ',', ' ') }}</td>
                                            <td class="py-1.5 pr-2 text-right tabular-nums text-red-600 font-medium">{{ number_format((float)($rank['outgoing'] ?? 0), 2, ',', ' ') }}</td>
                                            <td class="py-1.5 text-right tabular-nums font-semibold">{{ number_format((float)($rank['total'] ?? 0), 2, ',', ' ') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>{{-- /card-family-bilateral --}}

                    <div id="card-regular-inflows" class="toggle-card-section mt-4 border border-gray-200 rounded-xl p-5 bg-white shadow-sm" x-data="{ q: '', minAmount: 0 }">
                        <div class="flex items-start justify-between flex-wrap gap-3">
                            <div class="font-medium text-sm">Rentrées régulières (revenus, retraites, loyers...)</div>
                            @php
                                $inflowMonthlyTotal = ($regular_inflows ?? collect())->sum(fn($r) => (float)($r['avg_amount'] ?? 0));
                                $inflowPeriodTotal  = ($regular_inflows ?? collect())->sum(fn($r) => (float)($r['avg_amount'] ?? 0) * max(1, (int)($r['months'] ?? 1)));
                                $inflowSources      = ($regular_inflows ?? collect())->count();
                            @endphp
                            @if ($inflowSources > 0)
                            <div class="flex items-center gap-4 flex-wrap">
                                <div class="text-xs px-3 py-1.5 rounded-lg" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                                    <span class="text-gray-500">Revenu mensuel estimé</span>
                                    <span class="ml-2 font-semibold text-green-700 tabular-nums">{{ number_format($inflowMonthlyTotal, 2, ',', ' ') }} €</span>
                                </div>
                                <div class="text-xs px-3 py-1.5 rounded-lg" style="background:#fafaf8;border:1px solid #e5e1d8;">
                                    <span class="text-gray-500">Total encaissé sur la période</span>
                                    <span class="ml-2 font-semibold tabular-nums" style="color:#2E2A25;">{{ number_format($inflowPeriodTotal, 2, ',', ' ') }} €</span>
                                </div>
                                <div class="text-xs px-3 py-1.5 rounded-lg" style="background:#fafaf8;border:1px solid #e5e1d8;">
                                    <span class="text-gray-500">Sources</span>
                                    <span class="ml-2 font-semibold" style="color:#2E2A25;">{{ $inflowSources }}</span>
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <x-text-input x-model="q" type="text" class="block w-full" placeholder="Rechercher contrepartie / genre" />
                            <x-text-input x-model.number="minAmount" type="number" min="0" step="0.01" class="block w-full" placeholder="Montant moyen min" />
                        </div>
                        @if (($regular_inflows ?? collect())->count() === 0)
                            <div class="mt-2 text-sm text-gray-600">Aucune rentrée régulière détectée automatiquement.</div>
                        @else
                            <div class="mt-3 overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-600">
                                            <th class="py-2 pr-4">Contrepartie</th>
                                            <th class="py-2 pr-4">Genre</th>
                                            <th class="py-2 pr-4 text-right">Occ.</th>
                                            <th class="py-2 pr-4 text-right">Moyenne</th>
                                            <th class="py-2 pr-4 text-right">Médiane</th>
                                            <th class="py-2 pr-4">Dernière</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach (($regular_inflows ?? collect()) as $row)
                                            <tr x-show="(q === '' || (@js(mb_strtolower((string)($row['counterparty'] ?? ''))).includes(q.toLowerCase()) || @js(mb_strtolower((string)($row['kind_label'] ?? ''))).includes(q.toLowerCase()))) && ({{ (float)($row['avg_amount'] ?? 0) }} >= (minAmount || 0))">
                                                <td class="py-2 pr-4 max-w-md truncate" title="{{ $row['counterparty'] ?? '—' }}">{{ $row['counterparty'] ?? '—' }}</td>
                                                <td class="py-2 pr-4 whitespace-nowrap">
                                                    @if (!empty($row['income_category']))
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium"
                                                              style="background:#ecfdf5;color:#065f46;border:1px solid #6ee7b7;">
                                                            {{ $row['income_category']['label'] }}
                                                        </span>
                                                    @else
                                                        {{ $row['kind_label'] ?? '—' }}
                                                    @endif
                                                </td>
                                                <td class="py-2 pr-4 text-right tabular-nums">{{ (int)($row['occurrences'] ?? 0) }} / {{ (int)($row['months'] ?? 0) }}</td>
                                                <td class="py-2 pr-4 text-right tabular-nums text-green-700">{{ number_format((float)($row['avg_amount'] ?? 0), 2, ',', ' ') }}</td>
                                                <td class="py-2 pr-4 text-right tabular-nums">{{ number_format((float)($row['median_amount'] ?? 0), 2, ',', ' ') }}</td>
                                                <td class="py-2 pr-4 whitespace-nowrap">{{ $row['last_date'] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-t-2 border-gray-300 bg-gray-50 font-semibold text-sm">
                                            <td class="py-2 pr-4" colspan="3">Total revenus sur la période</td>
                                            <td class="py-2 pr-4 text-right tabular-nums text-green-700">{{ number_format($inflowMonthlyTotal, 2, ',', ' ') }} €/mois</td>
                                            <td class="py-2 pr-4 text-right tabular-nums" style="color:#2E2A25;">{{ number_format($inflowPeriodTotal, 2, ',', ' ') }} €</td>
                                            <td class="py-2 pr-4 text-xs text-gray-400">{{ $inflowSources }} source{{ $inflowSources > 1 ? 's' : '' }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div id="card-regular-outflows" class="toggle-card-section mt-4 border border-gray-200 rounded-xl p-5 bg-white shadow-sm" x-data="{ q: '', minAmount: 0 }">
                        <div class="font-medium text-sm">Sorties régulières (factures, internet, impôts, énergie...)</div>
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <x-text-input x-model="q" type="text" class="block w-full" placeholder="Rechercher contrepartie / genre" />
                            <x-text-input x-model.number="minAmount" type="number" min="0" step="0.01" class="block w-full" placeholder="Montant moyen min" />
                        </div>
                        @if (($regular_outflows ?? collect())->count() === 0)
                            <div class="mt-2 text-sm text-gray-600">Aucune sortie régulière détectée automatiquement.</div>
                        @else
                            <div class="mt-3 overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-600">
                                            <th class="py-2 pr-4">Contrepartie</th>
                                            <th class="py-2 pr-4">Genre</th>
                                            <th class="py-2 pr-4 text-right">Occ.</th>
                                            <th class="py-2 pr-4 text-right">Moyenne</th>
                                            <th class="py-2 pr-4 text-right">Médiane</th>
                                            <th class="py-2 pr-4">Dernière</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach (($regular_outflows ?? collect()) as $row)
                                            <tr x-show="(q === '' || (@js(mb_strtolower((string)($row['counterparty'] ?? ''))).includes(q.toLowerCase()) || @js(mb_strtolower((string)($row['kind_label'] ?? ''))).includes(q.toLowerCase()))) && ({{ (float)($row['avg_amount'] ?? 0) }} >= (minAmount || 0))">
                                                <td class="py-2 pr-4 max-w-md truncate" title="{{ $row['counterparty'] ?? '—' }}">{{ $row['counterparty'] ?? '—' }}</td>
                                                <td class="py-2 pr-4 whitespace-nowrap">
                                                    @if (!empty($row['income_category']))
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium"
                                                              style="background:#fff7ed;color:#9a3412;border:1px solid #fdba74;">
                                                            {{ $row['income_category']['label'] }}
                                                        </span>
                                                    @else
                                                        {{ $row['kind_label'] ?? '—' }}
                                                    @endif
                                                </td>
                                                <td class="py-2 pr-4 text-right tabular-nums">{{ (int)($row['occurrences'] ?? 0) }} / {{ (int)($row['months'] ?? 0) }}</td>
                                                <td class="py-2 pr-4 text-right tabular-nums text-red-700">{{ number_format((float)($row['avg_amount'] ?? 0), 2, ',', ' ') }}</td>
                                                <td class="py-2 pr-4 text-right tabular-nums">{{ number_format((float)($row['median_amount'] ?? 0), 2, ',', ' ') }}</td>
                                                <td class="py-2 pr-4 whitespace-nowrap">{{ $row['last_date'] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div id="card-regular-outliers" class="toggle-card-section mt-4 border border-gray-200 rounded-xl p-5 bg-white shadow-sm" x-data="{ q: '', minAmount: 0 }">
                        <div class="font-medium text-sm">Écarts sur mouvements réguliers (ce qui est à côté)</div>
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <x-text-input x-model="q" type="text" class="block w-full" placeholder="Rechercher série / libellé" />
                            <x-text-input x-model.number="minAmount" type="number" min="0" step="0.01" class="block w-full" placeholder="Montant min" />
                        </div>
                        @if (($regular_outliers ?? collect())->count() === 0)
                            <div class="mt-2 text-sm text-gray-600">Aucun écart notable détecté sur les séries régulières.</div>
                        @else
                            <div class="mt-3 overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-600">
                                            <th class="py-2 pr-4">Date</th>
                                            <th class="py-2 pr-4">Série</th>
                                            <th class="py-2 pr-4">Genre</th>
                                            <th class="py-2 pr-4 text-right">Montant</th>
                                            <th class="py-2 pr-4">Libellé</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach (($regular_outliers ?? collect()) as $row)
                                            <tr x-show="(q === '' || (@js(mb_strtolower((string)($row['series_label'] ?? ''))).includes(q.toLowerCase()) || @js(mb_strtolower((string)($row['label'] ?? ''))).includes(q.toLowerCase()))) && ({{ (float)($row['amount'] ?? 0) }} >= (minAmount || 0))">
                                                <td class="py-2 pr-4 whitespace-nowrap">{{ $row['date'] ?? '—' }}</td>
                                                <td class="py-2 pr-4 max-w-sm truncate" title="{{ $row['series_label'] ?? '—' }}">{{ $row['series_label'] ?? '—' }}</td>
                                                <td class="py-2 pr-4 whitespace-nowrap">
                                                    @if (!empty($row['series_income_category']))
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium"
                                                              style="background:#ecfdf5;color:#065f46;border:1px solid #6ee7b7;">
                                                            {{ $row['series_income_category']['label'] }}
                                                        </span>
                                                    @else
                                                        {{ $row['series_kind_label'] ?? '—' }}
                                                    @endif
                                                </td>
                                                <td class="py-2 pr-4 text-right tabular-nums {{ ($row['series_type'] ?? 'debit') === 'credit' ? 'text-green-700' : 'text-red-700' }}">{{ number_format((float)($row['amount'] ?? 0), 2, ',', ' ') }}</td>
                                                <td class="py-2 pr-4 max-w-md truncate" title="{{ $row['label'] ?? '—' }}">{{ $row['label'] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div id="card-cash-withdrawals" class="toggle-card-section mt-4 border border-gray-200 rounded-xl p-5 bg-white shadow-sm" x-data="{ q: '', minAmount: 0, hideZero: (localStorage.getItem('cash-hide-zero-months') === '1') }" x-init="$watch('hideZero', value => localStorage.setItem('cash-hide-zero-months', value ? '1' : '0'))">
                        <div class="font-medium text-sm">Retraits d’espèces: mensuel, moyenne et pics</div>
                        <div class="mt-1 text-xs text-gray-500">
                            Moyenne mensuelle: {{ number_format((float)($cash_monthly_average ?? 0), 2, ',', ' ') }} €
                            · Seuil pic: {{ number_format((float)($cash_peak_threshold ?? 0), 2, ',', ' ') }} €
                            · Pic mensuel: {{ $cash_peak_month['month'] ?? '—' }} ({{ number_format((float)($cash_peak_month['total'] ?? 0), 2, ',', ' ') }} €)
                        </div>

                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <x-text-input x-model="q" type="text" class="block w-full" placeholder="Rechercher mois/date/libellé" />
                            <x-text-input x-model.number="minAmount" type="number" min="0" step="0.01" class="block w-full" placeholder="Montant min" />
                        </div>
                        <div class="mt-2">
                            <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                                <input type="checkbox" x-model="hideZero" class="rounded border-gray-300" />
                                Masquer les mois à 0
                            </label>
                        </div>

                        @php
                            $cashRows = collect($cash_monthly_totals ?? [])->values();
                            $cashRowsNonZero = $cashRows->filter(fn ($row) => (float) ($row['total'] ?? 0) > 0)->values();
                            $cashCount = $cashRows->count();
                            $cashCountNonZero = $cashRowsNonZero->count();
                            $cashChartWidth = 980;
                            $cashChartHeight = 280;
                            $cashPadLeft = 60;
                            $cashPadRight = 24;
                            $cashPadTop = 20;
                            $cashPadBottom = 46;
                            $cashPlotWidth = $cashChartWidth - $cashPadLeft - $cashPadRight;
                            $cashPlotHeight = $cashChartHeight - $cashPadTop - $cashPadBottom;
                            $cashMaxValue = max(1.0, (float) ($cashRows->max('total') ?? 0));
                            $cashStepX = $cashCount > 0 ? ($cashPlotWidth / $cashCount) : 0;
                            $cashBarWidth = max(8, min(26, $cashStepX * 0.62));
                            $cashTickEvery = $cashCount > 48 ? 6 : ($cashCount > 24 ? 3 : 1);
                            $cashAvgValue = (float) ($cash_monthly_average ?? 0);
                            $cashAvgY = $cashPadTop + ($cashPlotHeight - ((min($cashAvgValue, $cashMaxValue) / $cashMaxValue) * $cashPlotHeight));

                            $cashMaxValueNonZero = max(1.0, (float) ($cashRowsNonZero->max('total') ?? 0));
                            $cashStepXNonZero = $cashCountNonZero > 0 ? ($cashPlotWidth / $cashCountNonZero) : 0;
                            $cashBarWidthNonZero = max(8, min(26, $cashStepXNonZero * 0.62));
                            $cashTickEveryNonZero = $cashCountNonZero > 48 ? 6 : ($cashCountNonZero > 24 ? 3 : 1);
                            $cashAvgYNonZero = $cashPadTop + ($cashPlotHeight - ((min($cashAvgValue, $cashMaxValueNonZero) / $cashMaxValueNonZero) * $cashPlotHeight));
                        @endphp

                        <div class="mt-3 border border-gray-200 rounded-xl p-4 bg-gray-50">
                            <div class="flex items-center gap-5 text-xs">
                                <span class="inline-flex items-center gap-1.5 text-gray-700">
                                    <svg width="16" height="10"><rect x="0" y="1" width="16" height="8" fill="#9CA3AF"/></svg>Mois standard
                                </span>
                                <span class="inline-flex items-center gap-1.5 text-orange-700">
                                    <svg width="16" height="10"><rect x="0" y="1" width="16" height="8" fill="#F97316"/></svg>Pic (≥ seuil)
                                </span>
                                <span class="inline-flex items-center gap-1.5 text-blue-700">
                                    <svg width="16" height="4"><line x1="0" y1="2" x2="16" y2="2" stroke="#2563EB" stroke-width="2" stroke-dasharray="4,2"/></svg>Moyenne mensuelle
                                </span>
                            </div>

                            @if ($cashCount === 0)
                                <div class="mt-2 text-sm text-gray-600">Pas de données de retraits d’espèces pour tracer le graphique.</div>
                            @else
                                <div class="mt-3 overflow-x-auto" x-show="!hideZero">
                                    <svg class="min-w-[920px] w-full" viewBox="0 0 {{ $cashChartWidth }} {{ $cashChartHeight }}" preserveAspectRatio="none" role="img" aria-label="Graphique mensuel des retraits d'espèces" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="0" y="0" width="{{ $cashChartWidth }}" height="{{ $cashChartHeight }}" fill="white"/>

                                        @for ($g = 0; $g <= 4; $g++)
                                            @php
                                                $yGrid = $cashPadTop + (($cashPlotHeight / 4) * $g);
                                                $labelValue = $cashMaxValue * (1 - ($g / 4));
                                            @endphp
                                            <line x1="{{ $cashPadLeft }}" y1="{{ $yGrid }}" x2="{{ $cashChartWidth - $cashPadRight }}" y2="{{ $yGrid }}" stroke="#E5E7EB" stroke-width="1"/>
                                            <text x="{{ $cashPadLeft - 6 }}" y="{{ $yGrid + 4 }}" text-anchor="end" font-size="10" fill="#6B7280">{{ number_format($labelValue, 0, ',', ' ') }}</text>
                                        @endfor

                                        <line x1="{{ $cashPadLeft }}" y1="{{ $cashPadTop }}" x2="{{ $cashPadLeft }}" y2="{{ $cashChartHeight - $cashPadBottom }}" stroke="#9CA3AF" stroke-width="1.2"/>
                                        <line x1="{{ $cashPadLeft }}" y1="{{ $cashChartHeight - $cashPadBottom }}" x2="{{ $cashChartWidth - $cashPadRight }}" y2="{{ $cashChartHeight - $cashPadBottom }}" stroke="#9CA3AF" stroke-width="1.2"/>

                                        <line x1="{{ $cashPadLeft }}" y1="{{ $cashAvgY }}" x2="{{ $cashChartWidth - $cashPadRight }}" y2="{{ $cashAvgY }}" stroke="#2563EB" stroke-width="1.5" stroke-dasharray="5,3" opacity="0.85"/>
                                        <text x="{{ $cashChartWidth - $cashPadRight - 2 }}" y="{{ $cashAvgY - 4 }}" text-anchor="end" font-size="9" fill="#1D4ED8" opacity="0.85">moyenne</text>

                                        @foreach ($cashRows as $index => $row)
                                            @php
                                                $total = (float) ($row['total'] ?? 0);
                                                $month = (string) ($row['month'] ?? '');
                                                $barH = ($total / $cashMaxValue) * $cashPlotHeight;
                                                $x = $cashPadLeft + ($index * $cashStepX) + max(0, (($cashStepX - $cashBarWidth) / 2));
                                                $y = $cashPadTop + ($cashPlotHeight - $barH);
                                                $isPeak = !empty($row['is_peak']);
                                            @endphp

                                            <rect x="{{ number_format($x, 2, '.', '') }}" y="{{ number_format($y, 2, '.', '') }}" width="{{ number_format($cashBarWidth, 2, '.', '') }}" height="{{ number_format(max(0.5, $barH), 2, '.', '') }}" fill="{{ $isPeak ? '#F97316' : '#9CA3AF' }}" rx="2">
                                                <title>{{ $month }} · {{ number_format($total, 2, ',', ' ') }} €{{ $isPeak ? ' · pic' : '' }}</title>
                                            </rect>

                                            @if ($index % $cashTickEvery === 0 || $index === ($cashCount - 1))
                                                @php
                                                    $xTick = $cashPadLeft + ($index * $cashStepX) + ($cashStepX / 2);
                                                @endphp
                                                <text x="{{ number_format($xTick, 2, '.', '') }}" y="{{ $cashChartHeight - 18 }}" text-anchor="middle" font-size="10" fill="#4B5563">{{ $month }}</text>
                                            @endif
                                        @endforeach
                                    </svg>
                                </div>
                                <div class="mt-3 overflow-x-auto" x-show="hideZero" x-cloak>
                                    @if ($cashCountNonZero === 0)
                                        <div class="text-sm text-gray-600">Tous les mois sont à 0 sur la période.</div>
                                    @else
                                        <svg class="min-w-[920px] w-full" viewBox="0 0 {{ $cashChartWidth }} {{ $cashChartHeight }}" preserveAspectRatio="none" role="img" aria-label="Graphique mensuel des retraits d'espèces (hors mois à 0)" xmlns="http://www.w3.org/2000/svg">
                                            <rect x="0" y="0" width="{{ $cashChartWidth }}" height="{{ $cashChartHeight }}" fill="white"/>

                                            @for ($g = 0; $g <= 4; $g++)
                                                @php
                                                    $yGrid = $cashPadTop + (($cashPlotHeight / 4) * $g);
                                                    $labelValue = $cashMaxValueNonZero * (1 - ($g / 4));
                                                @endphp
                                                <line x1="{{ $cashPadLeft }}" y1="{{ $yGrid }}" x2="{{ $cashChartWidth - $cashPadRight }}" y2="{{ $yGrid }}" stroke="#E5E7EB" stroke-width="1"/>
                                                <text x="{{ $cashPadLeft - 6 }}" y="{{ $yGrid + 4 }}" text-anchor="end" font-size="10" fill="#6B7280">{{ number_format($labelValue, 0, ',', ' ') }}</text>
                                            @endfor

                                            <line x1="{{ $cashPadLeft }}" y1="{{ $cashPadTop }}" x2="{{ $cashPadLeft }}" y2="{{ $cashChartHeight - $cashPadBottom }}" stroke="#9CA3AF" stroke-width="1.2"/>
                                            <line x1="{{ $cashPadLeft }}" y1="{{ $cashChartHeight - $cashPadBottom }}" x2="{{ $cashChartWidth - $cashPadRight }}" y2="{{ $cashChartHeight - $cashPadBottom }}" stroke="#9CA3AF" stroke-width="1.2"/>

                                            <line x1="{{ $cashPadLeft }}" y1="{{ $cashAvgYNonZero }}" x2="{{ $cashChartWidth - $cashPadRight }}" y2="{{ $cashAvgYNonZero }}" stroke="#2563EB" stroke-width="1.5" stroke-dasharray="5,3" opacity="0.85"/>
                                            <text x="{{ $cashChartWidth - $cashPadRight - 2 }}" y="{{ $cashAvgYNonZero - 4 }}" text-anchor="end" font-size="9" fill="#1D4ED8" opacity="0.85">moyenne</text>

                                            @foreach ($cashRowsNonZero as $index => $row)
                                                @php
                                                    $total = (float) ($row['total'] ?? 0);
                                                    $month = (string) ($row['month'] ?? '');
                                                    $barH = ($total / $cashMaxValueNonZero) * $cashPlotHeight;
                                                    $x = $cashPadLeft + ($index * $cashStepXNonZero) + max(0, (($cashStepXNonZero - $cashBarWidthNonZero) / 2));
                                                    $y = $cashPadTop + ($cashPlotHeight - $barH);
                                                    $isPeak = !empty($row['is_peak']);
                                                @endphp

                                                <rect x="{{ number_format($x, 2, '.', '') }}" y="{{ number_format($y, 2, '.', '') }}" width="{{ number_format($cashBarWidthNonZero, 2, '.', '') }}" height="{{ number_format(max(0.5, $barH), 2, '.', '') }}" fill="{{ $isPeak ? '#F97316' : '#9CA3AF' }}" rx="2">
                                                    <title>{{ $month }} · {{ number_format($total, 2, ',', ' ') }} €{{ $isPeak ? ' · pic' : '' }}</title>
                                                </rect>

                                                @if ($index % $cashTickEveryNonZero === 0 || $index === ($cashCountNonZero - 1))
                                                    @php
                                                        $xTick = $cashPadLeft + ($index * $cashStepXNonZero) + ($cashStepXNonZero / 2);
                                                    @endphp
                                                    <text x="{{ number_format($xTick, 2, '.', '') }}" y="{{ $cashChartHeight - 18 }}" text-anchor="middle" font-size="10" fill="#4B5563">{{ $month }}</text>
                                                @endif
                                            @endforeach
                                        </svg>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-600">
                                            <th class="py-2 pr-4">Mois</th>
                                            <th class="py-2 pr-4 text-right">Total retraits</th>
                                            <th class="py-2 pr-4 text-right">Nb</th>
                                            <th class="py-2 pr-4 text-right">Max retrait</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach (($cash_monthly_totals ?? collect()) as $row)
                                            <tr x-show="(q === '' || @js((string)($row['month'] ?? '')).includes(q)) && ({{ (float)($row['total'] ?? 0) }} >= (minAmount || 0)) && (!hideZero || {{ (float)($row['total'] ?? 0) }} > 0)" class="{{ !empty($row['is_peak']) ? 'bg-orange-50' : '' }}">
                                                <td class="py-2 pr-4 whitespace-nowrap">{{ $row['month'] ?? '—' }}</td>
                                                <td class="py-2 pr-4 text-right tabular-nums text-red-700">{{ number_format((float)($row['total'] ?? 0), 2, ',', ' ') }}</td>
                                                <td class="py-2 pr-4 text-right tabular-nums">{{ (int)($row['count'] ?? 0) }}</td>
                                                <td class="py-2 pr-4 text-right tabular-nums">{{ number_format((float)($row['max'] ?? 0), 2, ',', ' ') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-600">
                                            <th class="py-2 pr-4">Date</th>
                                            <th class="py-2 pr-4">Libellé</th>
                                            <th class="py-2 pr-4 text-right">Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @forelse (($cash_top_withdrawals ?? collect()) as $tx)
                                            <tr x-show="(q === '' || (@js((string)optional($tx->date)->format('Y-m-d')).includes(q) || @js(mb_strtolower((string)($tx->display_label_full ?? $tx->label ?? ''))).includes(q.toLowerCase()))) && ({{ abs((float)($tx->amount ?? 0)) }} >= (minAmount || 0))">
                                                <td class="py-2 pr-4 whitespace-nowrap">{{ optional($tx->date)->format('Y-m-d') }}</td>
                                                <td class="py-2 pr-4 max-w-md" title="{{ $tx->display_label_full ?? ($tx->label ?? '') }}">
                                                    <span>{{ $tx->display_label ?? ($tx->label ?? '—') }}</span>
                                                    @if (!empty($tx->display_label_truncated))
                                                        <button type="button" class="text-xs text-slate-600 underline ml-2" onclick="toggleFullLabel(this)" data-short="{{ e($tx->display_label ?? '') }}" data-full="{{ e($tx->display_label_full ?? '') }}">Voir libellé complet</button>
                                                    @endif
                                                </td>
                                                <td class="py-2 pr-4 text-right tabular-nums text-red-700">{{ number_format(abs((float)($tx->amount ?? 0)), 2, ',', ' ') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td class="py-2 pr-4 text-gray-600" colspan="3">Aucun retrait détecté.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <hr class="my-6" />

                    {{-- ====== FILTRES RAPIDES ====== --}}
                    <div x-data="{ synthOpen: false }">
                    <h3 class="font-semibold">Transactions</h3>

                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                        <span class="text-gray-500 self-center font-medium">Filtres rapides :</span>
                        @if ($case->death_date)
                            <a href="{{ route('cases.show', $case) }}?date_to={{ $case->death_date->copy()->subDay()->format('Y-m-d') }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                                📅 Avant décès ({{ $case->death_date->format('d/m/Y') }})
                            </a>
                            <a href="{{ route('cases.show', $case) }}?date_from={{ $case->death_date->format('Y-m-d') }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                                📅 Après décès
                            </a>
                        @endif
                        <a href="{{ route('cases.show', $case) }}?min_amount=5000"
                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                            💳 Opérations ≥ 5 000 €
                        </a>
                        <a href="{{ route('cases.show', $case) }}?min_amount=20000"
                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                            🏦 Montants ≥ 20 000 €
                        </a>
                        <a href="{{ route('cases.show', $case) }}?kind=cheque"
                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                            📑 Chèques uniquement
                        </a>
                        <a href="{{ route('cases.show', $case) }}?kind=cash_withdrawal"
                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                            💵 Retraits espèces
                        </a>
                        <a href="{{ route('cases.show', $case) }}?type=debit&min_amount=500"
                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                            👤 Sorties > 500 €
                        </a>

                        {{-- Bouton synthèse orale --}}
                        <button type="button" @click="synthOpen = !synthOpen"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md border border-green-500 bg-green-50 text-green-800 hover:bg-green-100 transition-colors font-medium">
                            🎙 Générer synthèse orale
                        </button>
                    </div>

                    {{-- ====== SYNTHÈSE ORALE ====== --}}
                    @php
                        $synthMonths    = $monthlyTotals->count();
                        $synthTotalC    = $monthlyTotals->sum(fn($m) => (float)($m['credits'] ?? 0));
                        $synthTotalD    = $monthlyTotals->sum(fn($m) => (float)($m['debits']  ?? 0));
                        $synthAnomalies = $monthlyTotals->filter(fn($m) => !empty($m['credit_anomaly']) || !empty($m['debit_anomaly']));
                        $synthAnomCount = $synthAnomalies->count();
                        $synthAnomCred  = $synthAnomalies->sum(fn($m) => (float)($m['credits'] ?? 0));
                        $synthAnomDeb   = $synthAnomalies->sum(fn($m) => (float)($m['debits']  ?? 0));
                        $synthAnomTotal = $synthAnomCred + $synthAnomDeb;
                        $synthFluxTotal = $synthTotalC + $synthTotalD;
                        $synthAnomPct   = $synthFluxTotal > 0 ? round(($synthAnomTotal / $synthFluxTotal) * 100, 1) : 0;
                        $synthMaxM      = $monthlyTotals->sortByDesc(fn($m) => max((float)($m['credits'] ?? 0), (float)($m['debits'] ?? 0)))->first();
                        $synthStartM    = $monthlyTotals->first()['month'] ?? '—';
                        $synthEndM      = $monthlyTotals->last()['month']  ?? '—';
                        $synthAvgC      = (float)($behavioral['avg_credits'] ?? 0);
                        $synthAvgD      = (float)($behavioral['avg_debits']  ?? 0);
                        $synthStdC      = (float)($behavioral['std_credits'] ?? 0);
                        $synthStdD      = (float)($behavioral['std_debits']  ?? 0);
                        $synthStability = ($synthStdC + $synthStdD) < (($synthAvgC + $synthAvgD) * 0.5) ? 'stable' : 'variable';
                    @endphp

                    <div x-show="synthOpen" x-cloak x-transition class="mt-3 border border-green-200 rounded-xl p-5 bg-green-50 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="font-semibold text-green-900 text-sm">🎙 Synthèse orale — à lire en rendez-vous</div>
                            <button type="button" @click="synthOpen = false" class="text-green-700 hover:text-green-900 text-xs">✕ Fermer</button>
                        </div>
                        <div class="mt-3 space-y-2 text-sm text-green-900 leading-relaxed">
                            <p>
                                «&nbsp;Sur <strong>{{ $synthMonths }} mois analysés</strong>
                                (de <strong>{{ $synthStartM }}</strong> à <strong>{{ $synthEndM }}</strong>),
                                les comptes présentent un total de
                                <strong>{{ number_format($synthTotalC, 2, ',', ' ') }} €</strong> de crédits
                                et <strong>{{ number_format($synthTotalD, 2, ',', ' ') }} €</strong> de débits,
                                soit un flux brut de <strong>{{ number_format($synthFluxTotal, 2, ',', ' ') }} €</strong>.&nbsp;»
                            </p>
                            @if ($synthAnomCount > 0)
                                <p>
                                    «&nbsp;<strong class="text-orange-800">{{ $synthAnomCount }} mois</strong> présentent un caractère statistiquement atypique
                                    (z-score supérieur à 2 écarts-types), représentant
                                    <strong class="text-orange-800">{{ number_format($synthAnomTotal, 2, ',', ' ') }} €</strong>
                                    soit <strong class="text-orange-800">{{ $synthAnomPct }} %</strong> des flux totaux.&nbsp;»
                                </p>
                                <p>
                                    «&nbsp;Le pic le plus élevé est observé en
                                    <strong>{{ $synthMaxM['month'] ?? '—' }}</strong> avec
                                    {{ number_format(max((float)($synthMaxM['credits'] ?? 0), (float)($synthMaxM['debits'] ?? 0)), 2, ',', ' ') }} €
                                    ({{ max((float)($synthMaxM['credits'] ?? 0), 0) > max((float)($synthMaxM['debits'] ?? 0), 0) ? 'crédit' : 'débit' }}).&nbsp;»
                                </p>
                            @else
                                <p>«&nbsp;Aucune anomalie statistique détectée automatiquement sur la période.&nbsp;»</p>
                            @endif
                            <p>
                                «&nbsp;La moyenne mensuelle est de
                                <strong>{{ number_format($synthAvgC, 2, ',', ' ') }} €</strong> en crédits
                                et <strong>{{ number_format($synthAvgD, 2, ',', ' ') }} €</strong> en débits.
                                Le profil d'activité est
                                @if ($synthStability === 'stable')
                                    <strong class="text-green-800">globalement stable</strong> — les écarts-types sont inférieurs à 50 % de la moyenne.
                                @else
                                    <strong class="text-orange-800">variable</strong> — les écarts-types sont supérieurs à 50 % de la moyenne, traduisant des ruptures de tendance significatives.
                                @endif
                                &nbsp;»
                            </p>
                            @if ($case->death_date)
                                <p class="text-gray-700 text-xs border-t border-green-200 pt-2 mt-2">
                                    ℹ Date de décès enregistrée : {{ $case->death_date->format('d/m/Y') }}.
                                    Utilisez les filtres «&nbsp;Avant décès&nbsp;» / «&nbsp;Après décès&nbsp;» ci-dessus pour isoler chaque période.
                                </p>
                            @endif
                        </div>
                    </div>

                    </div>{{-- /x-data synthOpen --}}

                    <div id="card-transactions" class="toggle-card-section mt-3 border border-gray-200 rounded-xl p-5 bg-white shadow-sm" x-data="{ showMotif: false }">
                        <form method="GET" action="{{ route('cases.show', $case) }}" class="grid grid-cols-1 md:grid-cols-10 gap-3 text-sm">
                            <div>
                                <x-input-label for="bank_name" :value="__('Banque')" />
                                <select id="bank_name" name="bank_name" class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm">
                                    <option value="" @selected(($tx_filters['bank_name'] ?? '') === '')>Toutes</option>
                                    @foreach (($account_filter_options['banks'] ?? collect()) as $bankName)
                                        <option value="{{ $bankName }}" @selected(($tx_filters['bank_name'] ?? '') === $bankName)>{{ $bankName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="account_profile" :value="__('Profil compte')" />
                                <select id="account_profile" name="account_profile" class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm">
                                    <option value="" @selected(($tx_filters['account_profile'] ?? '') === '')>Tous</option>
                                    <option value="personal" @selected(($tx_filters['account_profile'] ?? '') === 'personal')>Personnel</option>
                                    <option value="joint" @selected(($tx_filters['account_profile'] ?? '') === 'joint')>Commun</option>
                                </select>
                            </div>

                            <div>
                                <x-input-label for="bank_account_id" :value="__('Compte')" />
                                <select id="bank_account_id" name="bank_account_id" class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm">
                                    <option value="" @selected(($tx_filters['bank_account_id'] ?? '') === '')>Tous</option>
                                    @foreach (($account_filter_options['accounts'] ?? collect()) as $option)
                                        <option value="{{ $option['id'] }}" @selected((string)($tx_filters['bank_account_id'] ?? '') === (string)$option['id'])>{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="date_from" :value="__('Du')" />
                                <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="$tx_filters['date_from'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label for="date_to" :value="__('Au')" />
                                <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="$tx_filters['date_to'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label for="min_amount" :value="__('Montant min (valeur absolue)')" />
                                <x-text-input id="min_amount" name="min_amount" type="number" step="0.01" class="mt-1 block w-full" :value="$tx_filters['min_amount'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label for="max_amount" :value="__('Montant max (valeur absolue)')" />
                                <x-text-input id="max_amount" name="max_amount" type="number" step="0.01" class="mt-1 block w-full" :value="$tx_filters['max_amount'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label for="type" :value="__('Sens')" />
                                <select id="type" name="type" class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm">
                                    <option value="" @selected(($tx_filters['type'] ?? '') === '')>Tous</option>
                                    <option value="debit" @selected(($tx_filters['type'] ?? '') === 'debit')>Débit</option>
                                    <option value="credit" @selected(($tx_filters['type'] ?? '') === 'credit')>Crédit</option>
                                </select>
                            </div>
                            <div>
                                <x-input-label for="kind" :value="__('Catégorie')" />
                                <select id="kind" name="kind" class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm">
                                    <option value="" @selected(($tx_filters['kind'] ?? '') === '')>Toutes</option>
                                    <option value="transfer" @selected(($tx_filters['kind'] ?? '') === 'transfer')>Virement</option>
                                    <option value="cheque" @selected(($tx_filters['kind'] ?? '') === 'cheque')>Chèque</option>
                                    <option value="cash_withdrawal" @selected(($tx_filters['kind'] ?? '') === 'cash_withdrawal')>Retrait espèces</option>
                                    <option value="card" @selected(($tx_filters['kind'] ?? '') === 'card')>Carte bancaire</option>
                                </select>
                            </div>
                            <div>
                                <x-input-label for="score_min" :value="__('Score min')" />
                                <x-text-input id="score_min" name="score_min" type="number" min="0" max="100" step="1" class="mt-1 block w-full" :value="$tx_filters['score_min'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label for="q" :value="__('Recherche texte')" />
                                <x-text-input id="q" name="q" type="text" class="mt-1 block w-full" :value="$tx_filters['q'] ?? ''" placeholder="Libellé, motif, origine..." />
                            </div>

                            @php
                                $activeFilters = collect([
                                    'Du' => $tx_filters['date_from'] ?? null,
                                    'Au' => $tx_filters['date_to'] ?? null,
                                    'Banque' => $tx_filters['bank_name'] ?? null,
                                    'Profil compte' => ($tx_filters['account_profile'] ?? '') === 'joint' ? 'Commun' : ((($tx_filters['account_profile'] ?? '') === 'personal') ? 'Personnel' : null),
                                    'Compte' => $tx_filters['bank_account_id'] ?? null,
                                    'Montant min' => $tx_filters['min_amount'] ?? null,
                                    'Montant max' => $tx_filters['max_amount'] ?? null,
                                    'Sens' => $tx_filters['type'] ?? null,
                                    'Catégorie' => $tx_filters['kind'] ?? null,
                                    'Score min' => $tx_filters['score_min'] ?? null,
                                    'Texte' => $tx_filters['q'] ?? null,
                                ])->filter(fn ($value) => !is_null($value) && $value !== '');
                            @endphp

                            @if ($activeFilters->isNotEmpty())
                                <div class="md:col-span-6 text-xs text-gray-700 bg-green-50 border border-green-100 rounded-md px-3 py-2">
                                    Filtres actifs:
                                    @foreach ($activeFilters as $label => $value)
                                        <span class="inline-flex items-center px-2 py-0.5 ml-2 mt-1 rounded bg-white border border-green-200 whitespace-nowrap">{{ $label }}: {{ $value }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="md:col-span-6 flex flex-wrap items-center gap-3">
                                <x-primary-button>
                                    Filtrer
                                </x-primary-button>
                                <a class="text-sm text-gray-600 hover:underline" href="{{ route('cases.show', $case) }}">Réinitialiser</a>
                                <a class="inline-flex items-center px-3 py-2 text-xs border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50" href="{{ route('cases.transactions.export', array_merge(['case' => $case], array_filter($tx_filters ?? [], fn($value) => !is_null($value) && $value !== ''))) }}">
                                    Exporter sélection (.xlsx)
                                </a>
                                <button type="button" @click="showMotif = !showMotif" class="inline-flex items-center px-3 py-2 text-xs border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    <span x-text="showMotif ? 'Masquer la colonne Motif' : 'Afficher la colonne Motif'"></span>
                                </button>
                                <div class="text-sm text-gray-600">
                                    <span class="text-gray-500">("valeur absolue" = on ignore le signe: 50€ filtre -50€ et +50€)</span>
                                    Total débit: <span class="font-medium whitespace-nowrap">{{ number_format((float)($tx_totals['debit'] ?? 0), 2, ',', ' ') }}</span>
                                    · Total crédit: <span class="font-medium whitespace-nowrap">{{ number_format((float)($tx_totals['credit'] ?? 0), 2, ',', ' ') }}</span>
                                    · Net: <span class="font-medium whitespace-nowrap">{{ number_format((float)($tx_totals['net'] ?? 0), 2, ',', ' ') }}</span>
                                    · Lignes: <span class="font-medium">{{ (int)($tx_totals['count'] ?? 0) }}</span>
                                </div>
                            </div>
                        </form>

                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-600">
                                        <th class="py-2 pr-4">Date</th>
                                        <th class="py-2 pr-4">Sens</th>
                                        <th class="py-2 pr-4">Catégorie</th>
                                        <th class="py-2 pr-4">Origine</th>
                                        <th class="py-2 pr-4">Destinataire</th>
                                        <th class="py-2 pr-4" x-show="showMotif" x-cloak>Motif</th>
                                        <th class="py-2 pr-4">N° chèque</th>
                                        <th class="py-2 pr-4 text-right">Montant</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @forelse (($transactions ?? []) as $tx)
                                        <tr>
                                            <td class="py-3 pr-4 whitespace-nowrap">{{ optional($tx->date)->format('Y-m-d') }}</td>
                                            <td class="py-3 pr-4">{{ ($tx->type ?? '') === 'debit' ? 'débit' : (($tx->type ?? '') === 'credit' ? 'crédit' : '—') }}</td>
                                            <td class="py-3 pr-4">{{ $tx->kind ?? '—' }}</td>
                                            <td class="py-3 pr-4 whitespace-normal break-words" title="{{ $tx->origin ?? '' }}">{{ $tx->origin ?? '—' }}</td>
                                            <td class="py-3 pr-4 whitespace-normal break-words" title="{{ $tx->destination ?? '' }}">{{ $tx->destination ?? '—' }}</td>
                                            <td class="py-3 pr-4 whitespace-normal break-words max-w-[34rem]" x-show="showMotif" x-cloak title="{{ $tx->display_label_full ?? ($tx->motif ?? $tx->label) }}">
                                                <span>{{ $tx->display_label ?? ($tx->motif ?? $tx->label) }}</span>
                                                @if (!empty($tx->display_label_truncated))
                                                    <button type="button" class="text-xs text-slate-600 underline ml-2" onclick="toggleFullLabel(this)" data-short="{{ e($tx->display_label ?? '') }}" data-full="{{ e($tx->display_label_full ?? '') }}">Voir libellé complet</button>
                                                @endif
                                            </td>
                                            <td class="py-3 pr-4">{{ $tx->cheque_number ?? '—' }}</td>
                                            <td class="py-3 pr-4 text-right tabular-nums whitespace-nowrap">{{ number_format(abs((float)$tx->amount), 2, ',', ' ') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="py-3 pr-4 text-gray-600" colspan="8">Aucune transaction (ou aucun filtre ne correspond).</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if (isset($transactions) && method_exists($transactions, 'links'))
                            <div class="mt-4">
                                {{ $transactions->links() }}
                            </div>
                        @endif
                    </div>

                    <hr class="my-6" />

                    <h3 class="font-semibold">Assistant IA (demande libre)</h3>

                    <div id="card-ai-report" class="toggle-card-section mt-3 border border-gray-200 rounded-xl p-5 bg-white shadow-sm">
                        @if ($aiError)
                            <div class="mb-3 text-sm text-red-700">
                                {{ $aiError }}
                            </div>
                        @endif

                        <div class="mb-3 text-xs text-gray-600">
                            Cette section garde la dernière demande IA envoyée pour ce dossier, afin que tu voies toujours ce qui a été demandé et le résultat obtenu.
                        </div>

                        <form method="POST" action="{{ route('cases.ai', $case) }}" class="space-y-3">
                            @csrf
                            <div>
                                <x-input-label for="prompt" :value="__('Prompt ciblé (optionnel)')" />
                                <div class="mt-1.5 flex flex-wrap gap-1.5 mb-2">
                                    @foreach([
                                        'Analyse globale'          => '',
                                        'Focus succession'         => 'Concentre-toi sur les opérations inhabituelles dans les 6 mois avant et après toute date clé du dossier. Identifie les retraits espèces, chèques non justifiés et virements vers des tiers non identifiés. Formule de manière neutre pour un juge.',
                                        'Focus retraits espèces'   => 'Analyse tous les retraits espèces : fréquence, montants, évolution chronologique. Identifie les pics et comportements atypiques susceptibles d\'intéresser un notaire ou magistrat.',
                                        'Focus chèques suspects'   => 'Identifie tous les chèques émis. Classe par montant décroissant. Signale les séries inhabituelles ou les montants sans contrepartie apparente.',
                                        'Focus virements familiaux'=> 'Analyse les flux entre membres de la famille ou entités liées. Identifie donations potentielles, virements récurrents et transferts inhabituels pouvant caractériser un appauvrissement patrimonial.',
                                    ] as $qLabel => $qPrompt)
                                    <button type="button"
                                        onclick="document.getElementById('prompt').value={{ Js::from($qPrompt) }};document.getElementById('prompt').focus();"
                                        class="px-2.5 py-1 text-xs rounded border border-gray-200 bg-gray-50 hover:border-amber-400 hover:bg-amber-50 text-gray-600 font-medium">
                                        {{ $qLabel }}
                                    </button>
                                    @endforeach
                                </div>
                                <textarea id="prompt" name="prompt" rows="3" class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm" placeholder="Ex : Identifie les flux inhabituels dans les 3 mois précédant le décès, supérieurs à 1 000 €.">{{ old('prompt', (string) ($lastAi['prompt'] ?? '')) }}</textarea>
                            </div>
                            <x-primary-button>
                                Analyser avec IA
                            </x-primary-button>
                        </form>

                        @if ($ai)
                            <div class="mt-4 space-y-3 text-sm">
                                @if (($ai['summary'] ?? '') !== '')
                                    <div>
                                        <div class="font-medium">Résumé clair</div>
                                        <div class="text-gray-700">{{ $ai['summary'] }}</div>
                                    </div>
                                @endif

                                <div>
                                    <div class="font-medium">Points d’attention</div>
                                    @if (count(($ai['suspicious'] ?? [])) === 0)
                                        <div class="text-gray-600">—</div>
                                    @else
                                        <ul class="mt-1 list-disc ps-5 space-y-1">
                                            @foreach (($ai['suspicious'] ?? []) as $s)
                                                <li>{{ $s }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>

                                <div>
                                    <div class="font-medium">Filtres suggérés</div>
                                    @if (count(($ai['filters'] ?? [])) === 0)
                                        <div class="text-gray-600">—</div>
                                    @else
                                        @php
                                            $filterLabelMap = [
                                                'date_from' => 'Date début',
                                                'date_to' => 'Date fin',
                                                'min_amount' => 'Montant minimum',
                                                'max_amount' => 'Montant maximum',
                                                'type' => 'Sens',
                                                'kind' => 'Catégorie',
                                                'q' => 'Texte',
                                                'score_min' => 'Score minimum',
                                                'bank_name' => 'Banque',
                                                'account_profile' => 'Profil compte',
                                                'bank_account_id' => 'Compte',
                                            ];
                                            $kindLabelMap = [
                                                'transfer' => 'Virement',
                                                'cheque' => 'Chèque',
                                                'cash_withdrawal' => 'Retrait espèces',
                                                'card' => 'Carte bancaire',
                                            ];
                                            $typeLabelMap = [
                                                'debit' => 'Débit',
                                                'credit' => 'Crédit',
                                            ];
                                        @endphp
                                        <ul class="mt-1 space-y-1">
                                            @foreach (($ai['filters'] ?? []) as $filterKey => $filterValue)
                                                @php
                                                    $displayKey = $filterLabelMap[$filterKey] ?? $filterKey;
                                                    $displayValue = $filterValue;
                                                    if (is_string($filterValue) && $filterKey === 'kind') {
                                                        $displayValue = $kindLabelMap[$filterValue] ?? $filterValue;
                                                    }
                                                    if (is_string($filterValue) && $filterKey === 'type') {
                                                        $displayValue = $typeLabelMap[$filterValue] ?? $filterValue;
                                                    }
                                                @endphp
                                                <li class="text-gray-700">
                                                    <span class="font-medium">{{ $displayKey }}:</span>
                                                    {{ is_scalar($displayValue) || is_null($displayValue) ? ($displayValue === '' || is_null($displayValue) ? '—' : $displayValue) : json_encode($displayValue, JSON_UNESCAPED_UNICODE) }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <div id="card-accounts" class="toggle-card-section">
                    <h3 class="font-semibold">Comptes</h3>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <form method="POST" action="{{ route('bank-accounts.store', $case) }}" class="space-y-3">
                                @csrf

                                <div>
                                    <x-input-label for="bank_name" :value="__('Banque')" />
                                    <x-text-input id="bank_name" name="bank_name" type="text" class="mt-1 block w-full" required />
                                </div>

                                <div>
                                    <x-input-label for="iban_masked" :value="__('IBAN masqué (optionnel)')" />
                                    <x-text-input id="iban_masked" name="iban_masked" type="text" class="mt-1 block w-full" />
                                </div>

                                <div>
                                    <x-input-label for="account_holder" :value="__('Titulaire (optionnel)')" />
                                    <x-text-input id="account_holder" name="account_holder" type="text" class="mt-1 block w-full" />
                                </div>

                                <x-primary-button>
                                    {{ __('Ajouter compte') }}
                                </x-primary-button>
                            </form>
                        </div>

                        <div>
                            @if ($case->bankAccounts->count() === 0)
                                <p class="text-sm text-gray-600">Ajoute un compte pour pouvoir importer un relevé.</p>
                            @else
                                <div class="space-y-4">
                                    @foreach ($case->bankAccounts as $account)
                                        <div class="border border-gray-200 rounded-xl p-5 bg-white shadow-sm">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-medium">{{ $account->bank_name }}</div>
                                                    <div class="text-sm text-gray-600">{{ $account->iban_masked ?? '—' }}</div>
                                                </div>
                                            </div>

                                            <div class="mt-3">
                                                <form method="POST" action="{{ route('statements.store', $account) }}" enctype="multipart/form-data" class="space-y-2">
                                                    @csrf
                                                    <input type="file" name="statement" required class="block w-full text-sm" />
                                                    <p class="text-xs text-gray-500">MVP: CSV conseillé (colonnes: date,label,amount,balance_after).</p>
                                                    <x-primary-button>
                                                        {{ __('Uploader relevé') }}
                                                    </x-primary-button>
                                                </form>
                                            </div>

                                            <div class="mt-3 text-sm text-gray-600">
                                                Relevés: {{ $account->statements->count() }}
                                                @if ($account->statements->count() > 0)
                                                    <div class="mt-2 space-y-2">
                                                        @foreach ($account->statements->sortByDesc('created_at') as $st)
                                                            <div class="flex items-start justify-between gap-3 text-xs text-gray-500 border border-gray-100 rounded-lg px-3 py-2 bg-gray-50">
                                                                <div class="min-w-0 flex-1">
                                                                    <div class="font-medium text-gray-700 truncate">{{ $st->original_filename ?? 'relevé' }}</div>
                                                                    <div class="mt-0.5 flex flex-wrap gap-x-2 gap-y-0.5">
                                                                        <span>{{ $st->import_status ?? '—' }}</span>
                                                                        @if (!is_null($st->transactions_imported ?? null))
                                                                            <span>{{ $st->transactions_imported }} tx</span>
                                                                        @endif
                                                                        @if ($st->ocr_used)
                                                                            <span class="text-amber-600 font-medium">OCR</span>
                                                                        @endif
                                                                        @if ($st->imported_at)
                                                                            <span>{{ \Carbon\Carbon::parse($st->imported_at)->format('d/m/Y') }}</span>
                                                                        @endif
                                                                        @if ($st->size_bytes)
                                                                            <span>{{ number_format($st->size_bytes / 1024, 0, ',', ' ') }} Ko</span>
                                                                        @endif
                                                                    </div>
                                                                    @if ($st->import_error)
                                                                        <div class="mt-1 text-red-600">{{ Str::limit($st->import_error, 140) }}</div>
                                                                    @endif
                                                                </div>

                                                                <div class="flex items-center gap-2 shrink-0">
                                                                    @if (!empty($st->file_path))
                                                                        <a href="{{ route('statements.download', [$case, $st]) }}"
                                                                           target="_blank"
                                                                           class="inline-flex items-center gap-1 px-3 py-1 rounded text-[10px] font-medium"
                                                                           style="background:#f5f0e8;color:#6B4F2A;border:1px solid #d4b896;"
                                                                           title="Ouvrir le relevé PDF dans un nouvel onglet">
                                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 001.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"/><path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"/></svg>
                                                                            Voir
                                                                        </a>
                                                                    @endif

                                                                    <form method="POST" action="{{ route('statements.destroy', $st) }}" onsubmit="return confirm('Supprimer ce document ? Cette action retire aussi les transactions importées liées à ce relevé si tu les as nettoyées manuellement.');">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <x-danger-button class="px-3 py-1 text-[10px]">
                                                                            Supprimer
                                                                        </x-danger-button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach

                                    <div class="border border-gray-200 rounded-xl p-5 bg-gray-50 shadow-sm">
                                        <div class="font-medium text-sm">Diagnostic import (contrôle qualité)</div>
                                        <div class="text-xs text-gray-500 mt-1">Seuil montants élevés OCR: {{ number_format((float)($import_high_value_threshold ?? 20000), 0, ',', ' ') }} €</div>

                                        @if (($statement_diagnostics ?? collect())->count() === 0)
                                            <div class="mt-2 text-sm text-gray-600">Aucun relevé disponible pour diagnostic.</div>
                                        @else
                                            <div class="mt-3 overflow-x-auto">
                                                <table class="min-w-full text-xs">
                                                    <thead>
                                                        <tr class="text-left text-gray-600">
                                                            <th class="py-2 pr-3">Relevé</th>
                                                            <th class="py-2 pr-3">Statut</th>
                                                            <th class="py-2 pr-3 text-right">Tx importées</th>
                                                            <th class="py-2 pr-3 text-right">Montants élevés OCR</th>
                                                            <th class="py-2 pr-3 text-right">Montants élevés importés</th>
                                                            <th class="py-2 pr-3">Alerte</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y">
                                                        @foreach (($statement_diagnostics ?? collect()) as $diag)
                                                            @php
                                                                $st = $diag['statement'];
                                                            @endphp
                                                            <tr>
                                                                <td class="py-2 pr-3 align-top">
                                                                    <div class="font-medium text-gray-700">{{ $diag['bank_name'] ?? 'Compte' }}</div>
                                                                    <div class="text-gray-600 truncate max-w-[280px]" title="{{ $st->original_filename ?? '' }}">{{ $st->original_filename ?? 'relevé' }}</div>
                                                                </td>
                                                                <td class="py-2 pr-3 align-top">
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded border text-[10px] {{ ($st->import_status ?? '') === 'completed' ? 'bg-green-50 text-green-700 border-green-200' : ((($st->import_status ?? '') === 'failed') ? 'bg-red-50 text-red-700 border-red-200' : 'bg-gray-50 text-gray-700 border-gray-200') }}">
                                                                        {{ $st->import_status ?? '—' }}
                                                                    </span>
                                                                    @if (!empty($st->ocr_used))
                                                                        <span class="ml-1 text-[10px] text-gray-500">OCR</span>
                                                                    @endif
                                                                </td>
                                                                <td class="py-2 pr-3 align-top text-right tabular-nums">{{ (int)($st->transactions_imported ?? 0) }}</td>
                                                                <td class="py-2 pr-3 align-top text-right tabular-nums">
                                                                    {{ (int)($diag['ocr_high_values_count'] ?? 0) }}
                                                                    @if (($diag['ocr_high_values_count'] ?? 0) > 0)
                                                                        <div class="text-[10px] text-gray-500 whitespace-nowrap">max {{ number_format((float)($diag['ocr_high_values'][0] ?? 0), 2, ',', ' ') }}</div>
                                                                    @endif
                                                                </td>
                                                                <td class="py-2 pr-3 align-top text-right tabular-nums">{{ is_null($diag['imported_high_values_count']) ? '—' : (int)$diag['imported_high_values_count'] }}</td>
                                                                <td class="py-2 pr-3 align-top">
                                                                    @if (!empty($diag['suspect_missing']))
                                                                        <span class="inline-flex items-center px-2 py-0.5 rounded border text-[10px] bg-red-50 text-red-700 border-red-200">Montants élevés manquants</span>
                                                                    @elseif (($diag['ocr_high_values_count'] ?? 0) > 0)
                                                                        <span class="inline-flex items-center px-2 py-0.5 rounded border text-[10px] bg-green-50 text-green-700 border-green-200">Contrôle OK</span>
                                                                    @else
                                                                        <span class="text-[10px] text-gray-500">—</span>
                                                                    @endif
                                                                    @if (!empty($st->import_error))
                                                                        <div class="mt-1 text-[10px] text-red-700 max-w-[260px]">{{ Str::limit((string) $st->import_error, 160) }}</div>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div id="card-multiyear-control" class="toggle-card-section mt-6 border border-gray-200 rounded-xl p-5 bg-gray-50 shadow-sm text-sm" x-data="{ showMultiYear: false }">
                        <div class="flex items-center justify-between gap-3">
                            <div class="font-semibold text-gray-800">Contrôle multi-années & titulaires</div>
                            <button type="button" @click="showMultiYear = !showMultiYear" class="inline-flex items-center px-3 py-2 text-xs border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <span x-text="showMultiYear ? 'Masquer' : 'Voir le contrôle'"></span>
                            </button>
                        </div>

                        <div x-show="showMultiYear" class="mt-3">
                            <div class="text-gray-700">
                                Période couverte par les transactions importées:
                                <span class="font-medium">{{ $date_coverage['from'] ?? '—' }}</span>
                                →
                                <span class="font-medium">{{ $date_coverage['to'] ?? '—' }}</span>
                            </div>

                            @if (($account_insights ?? collect())->count() === 0)
                                <div class="mt-2 text-gray-600">Aucun compte détecté.</div>
                            @else
                                <div class="mt-3 space-y-3">
                                    @foreach (($account_insights ?? collect()) as $insight)
                                        <div class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-medium">{{ $insight['bank_name'] ?? 'Compte' }}</span>
                                                <span class="text-xs px-2 py-0.5 rounded border {{ !empty($insight['is_joint']) ? 'bg-yellow-50 text-yellow-700 border-yellow-200' : 'bg-green-50 text-green-700 border-green-200' }}">
                                                    {{ !empty($insight['is_joint']) ? 'Compte commun probable' : 'Compte personnel probable' }}
                                                </span>
                                                @if (!empty($insight['has_identifier_change']))
                                                    <span class="text-xs px-2 py-0.5 rounded border bg-red-50 text-red-700 border-red-200">Changement de numéro détecté</span>
                                                @endif
                                            </div>
                                            <div class="mt-2 text-xs text-gray-600">
                                                Période compte: {{ $insight['period_start'] ?? '—' }} → {{ $insight['period_end'] ?? '—' }} · Lignes: {{ $insight['transactions_count'] ?? 0 }}
                                            </div>
                                            <div class="mt-1 text-xs text-gray-600">
                                                Numéros détectés:
                                                @if (empty($insight['identifiers']))
                                                    <span class="font-medium">—</span>
                                                @else
                                                    @foreach (($insight['identifiers'] ?? []) as $id)
                                                        <span class="inline-flex items-center px-2 py-0.5 ml-1 mt-1 rounded bg-gray-50 border border-gray-200 whitespace-nowrap">{{ $id }}</span>
                                                    @endforeach
                                                @endif
                                            </div>
                                            <div class="mt-1 text-xs text-gray-600">
                                                Mentions titulaire(s):
                                                @if (empty($insight['holders']))
                                                    <span class="font-medium">—</span>
                                                @else
                                                    @foreach (($insight['holders'] ?? []) as $holder)
                                                        <span class="inline-flex items-center px-2 py-0.5 ml-1 mt-1 rounded bg-gray-50 border border-gray-200">{{ $holder }}</span>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="mt-4 pt-3 border-t border-gray-200">
                                <div class="font-medium text-gray-800">Changements détectés (chronologie)</div>
                                @if (($account_change_events ?? collect())->count() === 0)
                                    <div class="mt-2 text-gray-600">Aucun changement notable détecté sur numéro de compte / titulaires.</div>
                                @else
                                    <ul class="mt-2 space-y-1 text-xs text-gray-700">
                                        @foreach (($account_change_events ?? collect()) as $event)
                                            <li>
                                                <span class="font-medium whitespace-nowrap">{{ $event['date'] ?? '—' }}</span>
                                                · <span class="font-medium">{{ $event['bank_name'] ?? 'Compte' }}</span>
                                                · {{ $event['message'] ?? 'Changement détecté' }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
            </section>
                </div>
            </div>
        </div>
    </div>
<script>
function toggleFullLabel(button) {
    const shortText = button?.getAttribute('data-short') || '';
    const fullText = button?.getAttribute('data-full') || '';
    const container = button?.previousElementSibling;
    if (!container) {
        return;
    }

    const expanded = button.getAttribute('data-expanded') === '1';
    if (expanded) {
        container.textContent = shortText;
        button.textContent = 'Voir libellé complet';
        button.setAttribute('data-expanded', '0');
        return;
    }

    container.textContent = fullText;
    button.textContent = 'Réduire';
    button.setAttribute('data-expanded', '1');
}

function setPrintSections(checked) {
    const checkboxes = document.querySelectorAll('.print-section-checkbox');
    checkboxes.forEach((cb) => {
        cb.checked = !!checked;
    });
}

function printSelectedSections() {
    const selections = Array.from(document.querySelectorAll('.print-section-checkbox:checked'));
    if (selections.length === 0) {
        alert('Sélectionnez au moins une section à imprimer.');
        return;
    }

    const sectionsHtml = selections
        .map((checkbox) => document.getElementById(checkbox.dataset.target))
        .filter((node) => !!node)
        .map((node) => {
            const clone = node.cloneNode(true);
            // Forcer l'affichage : les cartes peuvent être masquées via display:none (toggle "Affichage des cartes")
            clone.style.display = 'block';
            clone.querySelectorAll('*').forEach((el) => {
                if (el.style && el.style.display === 'none') {
                    el.style.display = '';
                }
            });
            clone.querySelectorAll('input, select, textarea, button').forEach((el) => {
                if (el.tagName === 'INPUT' && (el.type === 'checkbox' || el.type === 'radio')) {
                    if (!el.checked) {
                        el.remove();
                    }
                } else {
                    el.remove();
                }
            });
            clone.querySelectorAll('[x-show], [x-data], [x-cloak], [x-model]').forEach((el) => {
                el.removeAttribute('x-show');
                el.removeAttribute('x-data');
                el.removeAttribute('x-cloak');
                el.removeAttribute('x-model');
            });
            // [@click] n'est pas un sélecteur CSS valide — traitement séparé
            clone.querySelectorAll('*').forEach((el) => {
                if (el.hasAttribute('@click')) el.removeAttribute('@click');
                if (el.hasAttribute('x-on:click')) el.removeAttribute('x-on:click');
            });
            clone.querySelectorAll('a').forEach((a) => {
                const span = document.createElement('span');
                span.textContent = a.textContent;
                a.replaceWith(span);
            });
            return clone.outerHTML;
        })
        .join('<hr style="margin:16px 0;border:none;border-top:1px solid #e5e7eb;">');

    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        alert('Impossible d\'ouvrir la fenêtre d\'impression. Vérifiez le bloqueur de popups.');
        return;
    }

    const title = 'Dossier #{{ $case->id }} - Impression personnalisée';
    printWindow.document.open();
    printWindow.document.write(`
        <!doctype html>
        <html lang="fr">
        <head>
            <meta charset="utf-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1" />
            <title>${title}</title>
            <style>
                body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; color:#111827; margin:24px; }
                h1 { font-size: 18px; margin: 0 0 12px 0; }
                table { width: 100%; border-collapse: collapse; font-size: 12px; }
                th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
                .text-right { text-align: right; }
                .text-xs { font-size: 11px; }
                .text-sm { font-size: 12px; }
                .font-medium, .font-semibold { font-weight: 600; }
                .text-gray-500, .text-gray-600, .text-gray-700, .text-gray-800, .text-gray-900 { color: #374151; }
                .bg-white { background: #fff; }
                .rounded-xl, .rounded-md { border-radius: 0; }
                .shadow-sm { box-shadow: none; }
                @media print {
                    .break-after { page-break-after: always; }
                }
            </style>
        </head>
        <body>
            <h1>${title}</h1>
            ${sectionsHtml}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
    }, 200);
}

function applyCardVisibility(cardId, isVisible) {
    const card = document.getElementById(cardId);
    if (!card) {
        return;
    }

    card.style.display = isVisible ? '' : 'none';
}

function setCardVisibility(cardId, isVisible) {
    const storageKey = 'card-visibility-' + cardId;
    localStorage.setItem(storageKey, isVisible ? '1' : '0');
    applyCardVisibility(cardId, isVisible);

    const checkbox = document.querySelector(`.card-visibility-checkbox[data-target="${cardId}"]`);
    if (checkbox) {
        checkbox.checked = !!isVisible;
    }
}

function setCardVisibilityForAll(isVisible) {
    const checkboxes = document.querySelectorAll('.card-visibility-checkbox');
    checkboxes.forEach((checkbox) => {
        const target = checkbox.getAttribute('data-target');
        if (target) {
            setCardVisibility(target, !!isVisible);
        }
    });
}

function initCardVisibilityControls() {
    const checkboxes = document.querySelectorAll('.card-visibility-checkbox');
    checkboxes.forEach((checkbox) => {
        const target = checkbox.getAttribute('data-target');
        if (!target) {
            return;
        }

        const storageKey = 'card-visibility-' + target;
        const stored = localStorage.getItem(storageKey);
        const isVisible = stored === null ? true : stored === '1';

        checkbox.checked = isVisible;
        applyCardVisibility(target, isVisible);

        checkbox.addEventListener('change', () => {
            setCardVisibility(target, checkbox.checked);
        });
    });
}

function initExceptionalSourceToggles() {
    const toggles = document.querySelectorAll('.tx-source-toggle');
    toggles.forEach((toggleButton) => {
        toggleButton.addEventListener('click', () => {
            const targetId = toggleButton.getAttribute('data-target');
            if (!targetId) {
                return;
            }

            const detailRow = document.getElementById(targetId);
            if (!detailRow) {
                return;
            }

            const shouldShow = detailRow.classList.contains('hidden');
            detailRow.classList.toggle('hidden', !shouldShow);
            toggleButton.setAttribute('aria-expanded', shouldShow ? 'true' : 'false');
        });
    });
}

function toggleBeneficiaryDetails(button) {
    const targetId = button?.getAttribute('data-target');
    if (!targetId) {
        return;
    }

    const detailRow = document.getElementById(targetId);
    if (!detailRow) {
        return;
    }

    detailRow.classList.toggle('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    initCardVisibilityControls();
    initExceptionalSourceToggles();
});

function openBilanConfig() {
    const m = document.getElementById('bilan-config-modal');
    m.classList.remove('hidden');
    m.style.display = 'flex';
}
function closeBilanConfig() {
    const m = document.getElementById('bilan-config-modal');
    m.classList.add('hidden');
    m.style.display = 'none';
}
function generateBilanFromConfig() {
    const config = {
        inclJustif: document.getElementById('bc-justif')?.checked !== false,
        inclAi:     document.getElementById('bc-ai')?.checked !== false,
    };
    closeBilanConfig();
    printBilanNotaire(config);
}
function printBilanNotaire(config) {
    config = config || { inclJustif: true, inclAi: true };
    const tpl = document.getElementById('bilan-notaire-template');
    if (!tpl) { alert('Modèle de bilan introuvable.'); return; }
    const wrapper = document.createElement('div');
    wrapper.innerHTML = tpl.innerHTML;
    if (!config.inclJustif) wrapper.querySelectorAll('.bilan-section-justif').forEach(el => el.remove());
    if (!config.inclAi)     wrapper.querySelectorAll('.bilan-section-ai').forEach(el => el.remove());
    const content = wrapper.innerHTML;
    const win = window.open('', '_blank');
    if (!win) { alert('Popup bloqué. Autorisez les popups pour ce site.'); return; }
    win.document.open();
    win.document.write(`<!doctype html><html lang="fr"><head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width,initial-scale=1"/>
        <title>Bilan Notaire/Avocat – ${document.title}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&display=swap" rel="stylesheet">
        <style>
            @page { size: A4; margin: 14mm 16mm; }
            @media print {
                body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                .page-break { page-break-before: always; break-before: page; padding-top: 12mm; }
                .no-print { display: none !important; }
            }
        </style>
    </head><body>${content}</body></html>`);
    win.document.close();
    setTimeout(() => { if (win.print) win.print(); }, 900);
}

function exportChartSVG() {
    const svg = document.getElementById('monthlyChartSVG');
    if (!svg) { alert('Graphique introuvable.'); return; }
    const serializer = new XMLSerializer();
    let source = serializer.serializeToString(svg);
    // Ajout déclaration XML et namespace SVG si absent
    if (!source.match(/^<svg[^>]+xmlns="http:\/\/www\.w3\.org\/2000\/svg"/)) {
        source = source.replace(/^<svg/, '<svg xmlns="http://www.w3.org/2000/svg"');
    }
    const blob = new Blob([source], { type: 'image/svg+xml;charset=utf-8' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'graphique-mensuel-{{ $case->id }}.svg';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>

{{-- ============================================================
     BILAN NOTAIRE / AVOCAT — template pré-rendu côté serveur
     Contenu stocké inerte dans <template>, cloné par printBilanNotaire()
     ============================================================ --}}
<template id="bilan-notaire-template">
@php
    $bilanScore        = is_null($case->global_score) ? null : (int) $case->global_score;
    $bilanTitle        = $case->title ?? 'Dossier';
    $bilanPeriodStart  = optional($case->analysis_period_start)->format('d/m/Y');
    $bilanPeriodEnd    = optional($case->analysis_period_end)->format('d/m/Y');
    $bilanDeathDate    = optional($case->death_date)->format('d/m/Y');
    $bilanDeceasedName = $case->deceased_name ?? null;
    $bilanNow          = now()->format('d/m/Y à H\hi');

    $bilanScoreColor = is_null($bilanScore) ? '#8A7E72'
        : ($bilanScore >= 75 ? '#C0392B' : ($bilanScore >= 50 ? '#C47D1E' : ($bilanScore >= 25 ? '#B8860B' : '#2E7D32')));
    $bilanLevelLabel = is_null($bilanScore) ? 'Non calculé'
        : ($bilanScore >= 75 ? 'FORTEMENT ATYPIQUE' : ($bilanScore >= 50 ? 'ATYPIQUE' : ($bilanScore >= 25 ? 'VIGILANCE' : 'CONFORME')));
    $bilanGaugeW     = is_null($bilanScore) ? 0 : min(100, $bilanScore);

    $bilanTopDebits = collect($beneficiary_concentration ?? [])->take(5)->values();
    $bilanTopCredits = collect($source_concentration ?? [])->take(5)->values();
    $bilanTopExc    = collect($exceptional_transactions ?? [])
        ->sortByDesc(fn($tx) => abs((float)($tx->amount ?? 0)))->take(10)->values();

    $bilanSensitive    = $behavioral['sensitive_stats'] ?? null;
    $bilanAiSummary    = $ai['summary'] ?? null;
    $bilanAiConclusion = $ai['conclusion'] ?? null;
    $bilanAiSuspicious = collect((array)($ai['suspicious'] ?? []))
        ->filter(fn($v) => is_string($v) && trim($v) !== '')->values();
    $bilanScoreItems = collect((array)($score_breakdown['items'] ?? []))
        ->filter(fn($item) => ((int)($item['points'] ?? 0)) > 0)->values();
    $bilanYearlyTotals = collect($yearly_totals ?? []);
@endphp
<style>
/* ── Base ─────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    color: #1C1916;
    background: #fff;
    font-size: 11pt;
    line-height: 1.55;
}
.page { max-width: 210mm; margin: 0 auto; padding: 10mm 14mm 14mm; }
.page-break { padding-top: 10mm; }

/* ── Header ────────────────────────────────────────────────── */
.doc-brand {
    font-size: 7.5pt; letter-spacing: 0.18em; text-transform: uppercase;
    color: #8A7E72; font-family: Georgia, serif; margin-bottom: 4px;
}
.doc-brand em { color: #C9A84C; font-style: normal; font-weight: 700; }
.doc-title {
    font-size: 22pt; font-weight: 300; color: #1C1916; line-height: 1.1; margin: 4px 0 2px;
}
.doc-subtitle { font-size: 9pt; color: #5C5449; font-style: italic; }
.gold-bar { height: 2px; background: linear-gradient(90deg,#C9A84C,#E0C278,#C9A84C); margin: 10px 0; }
.doc-meta { font-size: 7.5pt; color: #8A7E72; display: flex; justify-content: space-between; }

/* ── Section titles ────────────────────────────────────────── */
.sec {
    font-family: Georgia, serif; font-size: 9pt; font-weight: normal;
    text-transform: uppercase; letter-spacing: 0.14em; color: #1C1916;
    border-bottom: 1px solid #C9A84C; padding-bottom: 3px; margin: 18px 0 10px;
}

/* ── KPI grid ──────────────────────────────────────────────── */
.kpi-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
.kpi-box { border: 1px solid #EDE4D0; padding: 10px 12px; }
.kpi-label { font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.1em; color: #8A7E72; margin-bottom: 4px; }
.kpi-val { font-size: 17pt; color: #1C1916; line-height: 1.1; }
.kpi-sub { font-size: 8pt; color: #5C5449; margin-top: 3px; font-style: italic; }

/* ── Score display ─────────────────────────────────────────── */
.score-num { font-size: 38pt; font-weight: 300; line-height: 1; display: inline-block; }
.score-badge {
    display: inline-block; padding: 2px 10px; font-size: 8pt; font-weight: 700;
    letter-spacing: 0.08em; text-transform: uppercase; border-radius: 1px;
}
.bar-track { height: 5px; background: #EDE4D0; border-radius: 2px; margin-top: 6px; width: 100%; }
.bar-fill  { height: 5px; border-radius: 2px; }

/* ── Highlights ────────────────────────────────────────────── */
.hl { border-left: 3px solid #C9A84C; padding: 5px 10px; margin: 5px 0; font-size: 9.5pt; background: #FDFBF5; }

/* ── Score detail ──────────────────────────────────────────── */
.score-detail { border: 1px solid #EDE4D0; padding: 8px 12px; margin-top: 8px; }
.score-row { display: flex; justify-content: space-between; font-size: 8.5pt;
    padding: 2.5px 0; border-bottom: 1px dotted #EDE4D0; }

/* ── Tables ────────────────────────────────────────────────── */
table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 6px; }
thead tr { background: #1C1916; color: #E0C278; }
thead th {
    padding: 6px 9px; font-weight: normal; text-transform: uppercase;
    letter-spacing: 0.07em; font-size: 7.5pt; text-align: left;
}
thead th.r { text-align: right; }
tbody tr:nth-child(even) { background: #FDFBF5; }
tbody td { padding: 6px 9px; border-bottom: 1px solid #EDE4D0; vertical-align: middle; }
tbody td.r { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
tfoot td { border-top: 2px solid #1C1916; padding: 6px 9px; font-weight: 700; background: #F5F0E8; }
tfoot td.r { text-align: right; }

/* ── Rank dot ──────────────────────────────────────────────── */
.rk {
    display: inline-flex; align-items: center; justify-content: center;
    width: 19px; height: 19px; border-radius: 50%;
    background: #2E2A25; color: #E0C278; font-size: 8pt; font-weight: 700;
}
.rk-1 { background: #C9A84C; color: #1C1916; }

/* ── Inline bar ────────────────────────────────────────────── */
.ibar { height: 5px; border-radius: 2px; background: #C9A84C; }

/* ── Tags ──────────────────────────────────────────────────── */
.tag { display: inline-block; padding: 1px 6px; font-size: 8pt; font-weight: 700; border-radius: 1px; }
.td { background: #FEF2F2; color: #C0392B; border: 1px solid #FECACA; }
.tc { background: #F0FFF0; color: #2E7D32; border: 1px solid #A5D6A7; }
.tv { background: #F0F9FF; color: #1E40AF; border: 1px solid #BFDBFE; }
.tch{ background: #FEF9EC; color: #92400E; border: 1px solid #FDE68A; }
.te { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }

/* ── Yearly grid ───────────────────────────────────────────── */
.yr-row { display: flex; justify-content: space-between; font-size: 8.5pt; padding: 2.5px 0; border-bottom: 1px dotted #EDE4D0; }

/* ── AI block ──────────────────────────────────────────────── */
.ai-block { border-left: 3px solid #C9A84C; padding: 8px 12px; background: #FDFBF5; margin: 8px 0; font-size: 9.5pt; line-height: 1.65; color: #2E2A25; }

/* ── Footer notice ─────────────────────────────────────────── */
.legal { border: 1px solid #EDE4D0; padding: 10px 12px; background: #FDFBF5; margin-top: 32px; }
.legal-title { font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #5C5449; margin-bottom: 5px; }
.legal-text { font-size: 7.5pt; color: #8A7E72; line-height: 1.6; }

/* ── Logo ──────────────────────────────────────────────────── */
.logo-img  { height: 58px; width: auto; display: block; }
.logo-mini { height: 28px; width: auto; vertical-align: middle; }

/* ── Per-page footer bar ───────────────────────────────────── */
.bilan-footer {
    border-top: 1px solid #EDE4D0;
    margin-top: 18px;
    padding-top: 7px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}
.bilan-footer-text { font-size: 6.5pt; color: #8A7E72; line-height: 1.5; flex: 1; }
.bilan-footer-right { font-size: 6.5pt; color: #8A7E72; text-align: right; white-space: nowrap; }
</style>

<div class="page">

{{-- ═══════════════════════════════════════════════════ --}}
{{-- PAGE 1 — EN-TÊTE + DIAGNOSTIC                      --}}
{{-- ═══════════════════════════════════════════════════ --}}
{{-- En-tête page 1 avec logo --}}
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
    <img src="{{ asset('analytica-logo.png') }}?v={{ filemtime(public_path('analytica-logo.png')) }}" alt="Analytica" class="logo-img">
    <div style="text-align:right;">
        <div style="font-size:7.5pt;letter-spacing:0.15em;text-transform:uppercase;color:#C9A84C;font-weight:700;font-family:Georgia,serif;">CONFIDENTIEL</div>
        <div style="font-size:7pt;color:#8A7E72;">Bilan à destination des professionnels du droit &nbsp;·&nbsp; Généré le {{ $bilanNow }}</div>
    </div>
</div>
<div class="gold-bar" style="margin-bottom:8px;"></div>
<div class="doc-title">{{ $bilanTitle }}</div>
<p class="doc-subtitle">
    Bilan d'analyse — à destination des professionnels du droit (notaire / avocat)
    @if ($bilanDeceasedName)
        &nbsp;·&nbsp; Succession : <strong>{{ $bilanDeceasedName }}</strong>
        @if ($bilanDeathDate) &nbsp;·&nbsp; Décès : {{ $bilanDeathDate }} @endif
    @endif
</p>
<div class="gold-bar"></div>
<div class="doc-meta">
    <span>
        @if ($bilanPeriodStart && $bilanPeriodEnd)
            Période : {{ $bilanPeriodStart }} → {{ $bilanPeriodEnd }}
        @else
            Période : toutes dates
        @endif
        &nbsp;·&nbsp; {{ (int)($stats['total_transactions'] ?? 0) }} opérations analysées
        &nbsp;·&nbsp; {{ $case->bankAccounts->count() }} compte(s)
    </span>
    <span>Généré le {{ $bilanNow }}</span>
</div>

<div class="sec">1 — Diagnostic du dossier</div>

<div class="kpi-grid">
    {{-- Score --}}
    <div class="kpi-box" style="border-left: 3px solid {{ $bilanScoreColor }};">
        <div class="kpi-label">Score de risque global</div>
        <div style="display:flex;align-items:center;gap:12px;margin-top:4px;">
            <div class="score-num" style="color:{{ $bilanScoreColor }};">{{ $bilanScore ?? '—' }}</div>
            <div style="flex:1;">
                <span class="score-badge" style="background:{{ $bilanScoreColor }}22;color:{{ $bilanScoreColor }};border:1px solid {{ $bilanScoreColor }}66;">{{ $bilanLevelLabel }}</span>
                @if (!is_null($bilanScore))
                    <div class="bar-track">
                        <div class="bar-fill" style="width:{{ $bilanGaugeW }}%;background:{{ $bilanScoreColor }};"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:7pt;color:#8A7E72;margin-top:2px;">
                        <span>0 Conforme</span><span>50</span><span>100 Critique</span>
                    </div>
                @endif
            </div>
        </div>
        @if ($bilanScoreItems->isNotEmpty())
            <div class="score-detail">
                <div class="kpi-label" style="margin-bottom:5px;">Cotation détaillée</div>
                @foreach ($bilanScoreItems as $item)
                    <div class="score-row">
                        <span>{{ $item['label'] ?? '' }}</span>
                        <span style="font-weight:700;color:{{ ((int)($item['points'] ?? 0)) >= 10 ? '#C0392B' : '#B8860B' }};">+{{ (int)($item['points'] ?? 0) }} pts</span>
                    </div>
                @endforeach
                <div class="score-row" style="border-bottom:none;font-weight:700;padding-top:5px;">
                    <span>TOTAL</span>
                    <span style="color:{{ $bilanScoreColor }};">{{ (int)($score_breakdown['total'] ?? 0) }} / 100</span>
                </div>
            </div>
        @endif
    </div>

    {{-- Points d'attention + flux annuels --}}
    <div>
        <div class="kpi-box" style="margin-bottom:10px;">
            <div class="kpi-label" style="margin-bottom:6px;">Points d'attention principaux</div>
            @foreach ($miniHighlights as $hl)
                <div class="hl">{{ $hl }}</div>
            @endforeach
        </div>

        @if ($bilanSensitive && !is_null($bilanSensitive['change_pct'] ?? null))
            <div class="kpi-box" style="margin-bottom:10px;border-left:3px solid #C9A84C;">
                <div class="kpi-label">Variation période sensible</div>
                <div class="kpi-val" style="color:{{ ($bilanSensitive['change_pct'] >= 0) ? '#C0392B' : '#2E7D32' }};">
                    {{ ($bilanSensitive['change_pct'] >= 0 ? '+' : '') }}{{ $bilanSensitive['change_pct'] }}%
                </div>
                @if (!empty($bilanSensitive['window_label']))
                    <div class="kpi-sub">{{ $bilanSensitive['window_label'] }}</div>
                @endif
                @if (!empty($bilanSensitive['before_avg']) && !empty($bilanSensitive['after_avg']))
                    <div style="font-size:8pt;color:#5C5449;margin-top:4px;">
                        Avant : {{ number_format((float)$bilanSensitive['before_avg'], 0, ',', ' ') }} €/mois
                        → Après : {{ number_format((float)$bilanSensitive['after_avg'], 0, ',', ' ') }} €/mois
                    </div>
                @endif
            </div>
        @endif

        @if ($bilanYearlyTotals->isNotEmpty())
            <div class="kpi-box">
                <div class="kpi-label" style="margin-bottom:5px;">Flux annuels (crédits / débits)</div>
                @foreach ($bilanYearlyTotals->take(6) as $yr)
                    <div class="yr-row">
                        <span style="font-weight:700;">{{ $yr['year'] ?? '—' }}</span>
                        <span style="color:#2E7D32;">+{{ number_format((float)($yr['credit'] ?? 0), 0, ',', ' ') }} €</span>
                        <span style="color:#C0392B;">−{{ number_format((float)($yr['debit'] ?? 0), 0, ',', ' ') }} €</span>
                        <span style="color:#8A7E72;">
                            @php $yrNet = (float)($yr['credit'] ?? 0) - (float)($yr['debit'] ?? 0); @endphp
                            {{ $yrNet >= 0 ? '+' : '' }}{{ number_format($yrNet, 0, ',', ' ') }} €
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Analyse algorithmique — section optionnelle dans le bilan --}}
<div class="bilan-section-ai">
@if (!empty($bilanAiSummary) || !empty($bilanAiConclusion) || $bilanAiSuspicious->isNotEmpty())
    <div class="sec">Analyse algorithmique</div>
    @if (!empty($bilanAiSummary))
        <div class="ai-block">{{ $bilanAiSummary }}</div>
    @endif
    @if (!empty($bilanAiConclusion))
        <p style="font-size:9.5pt;color:#2E2A25;font-style:italic;border-left:3px solid #C9A84C;padding:6px 10px;background:#FDFBF5;margin:8px 0;">{{ $bilanAiConclusion }}</p>
    @endif
    @if ($bilanAiSuspicious->isNotEmpty())
        <div style="margin-top:4px;">
            @foreach ($bilanAiSuspicious as $line)
                <div style="font-size:9pt;padding:4px 0;border-bottom:1px dotted #EDE4D0;color:#2E2A25;">— {{ $line }}</div>
            @endforeach
        </div>
    @endif
@endif
</div>{{-- /bilan-section-ai --}}

{{-- Pied de page 1 --}}
<div class="bilan-footer">
    <div class="bilan-footer-text"><strong>Analytica</strong> — Document produit à titre informatif sur la base d'une analyse algorithmique. Ne constitue pas un acte authentique ni une expertise judiciaire. Toute interprétation engage exclusivement son auteur.</div>
    <div class="bilan-footer-right"><strong>CONFIDENTIEL</strong><br>Analytica-1.0</div>
</div>

{{-- ═══════════════════════════════════════════════════ --}}
{{-- PAGE 2 — FLUX FINANCIERS                           --}}
{{-- ═══════════════════════════════════════════════════ --}}
<div class="page-break">
<div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #EDE4D0;padding-bottom:5px;margin-bottom:12px;">
    <div style="display:flex;align-items:center;gap:10px;">
        <img src="{{ asset('analytica-logo.png') }}?v={{ filemtime(public_path('analytica-logo.png')) }}" alt="Analytica" class="logo-mini">
        <div style="font-size:13pt;font-weight:300;">{{ $bilanTitle }}</div>
    </div>
    <div style="font-size:7.5pt;color:#8A7E72;">ANALYTICA · Bilan Notaire/Avocat · p. 2</div>
</div>
<div class="gold-bar" style="margin-bottom:4px;"></div>

{{-- Top 5 bénéficiaires débits --}}
<div class="sec">2 — Principaux bénéficiaires de débits (Top 5)</div>
<p style="font-size:8.5pt;color:#5C5449;font-style:italic;margin-bottom:8px;">Entités ayant reçu des fonds depuis les comptes du dossier — classement par montant total encaissé.</p>

@if ($bilanTopDebits->isNotEmpty())
    @php $bilanConcTotal = $bilanTopDebits->sum(fn($b) => (float)($b['amount'] ?? 0)); @endphp
    <table>
        <thead>
            <tr>
                <th style="width:3%;">#</th>
                <th style="width:38%;">Bénéficiaire</th>
                <th class="r" style="width:16%;">Reçu (€)</th>
                <th class="r" style="width:9%;">% débits</th>
                <th class="r" style="width:8%;">Mvts</th>
                <th style="width:26%;">Concentration</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bilanTopDebits as $i => $bene)
                @php $pct = (float)($bene['share_pct'] ?? 0); @endphp
                <tr>
                    <td style="text-align:center;"><span class="rk {{ $i === 0 ? 'rk-1' : '' }}">{{ $i + 1 }}</span></td>
                    <td>
                        <strong>{{ $bene['beneficiary'] ?? 'Inconnu' }}</strong>
                        @if (!empty($bene['aliases']))
                            <div style="font-size:7.5pt;color:#8A7E72;margin-top:2px;">
                                OCR : {{ mb_strimwidth(implode(' · ', array_slice((array)($bene['aliases'] ?? []), 0, 2)), 0, 75, '…') }}
                            </div>
                        @endif
                    </td>
                    <td class="r" style="font-weight:700;color:#C0392B;">{{ number_format((float)($bene['amount'] ?? 0), 2, ',', ' ') }}</td>
                    <td class="r">{{ number_format($pct, 1, ',', ' ') }}%</td>
                    <td class="r">{{ (int)($bene['count'] ?? 0) }}</td>
                    <td>
                        <div style="height:5px;background:#EDE4D0;border-radius:2px;">
                            <div class="ibar" style="width:{{ min(100,$pct) }}%;background:#C0392B;"></div>
                        </div>
                        <span style="font-size:7.5pt;color:#8A7E72;">{{ number_format($pct,1,',','') }}%</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total Top 5</td>
                <td class="r">{{ number_format($bilanConcTotal, 2, ',', ' ') }} €</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
@else
    <p style="font-size:9pt;color:#5C5449;font-style:italic;">Aucune donnée de concentration disponible.</p>
@endif

{{-- Top 5 sources crédits --}}
<div class="sec" style="margin-top:22px;">3 — Principales sources de crédits (Top 5)</div>
<p style="font-size:8.5pt;color:#5C5449;font-style:italic;margin-bottom:8px;">Origines des fonds encaissés sur les comptes du dossier.</p>

@if ($bilanTopCredits->isNotEmpty())
    <table>
        <thead>
            <tr>
                <th style="width:3%;">#</th>
                <th style="width:42%;">Source / émetteur</th>
                <th class="r" style="width:18%;">Encaissé (€)</th>
                <th class="r" style="width:9%;">%</th>
                <th class="r" style="width:8%;">Mvts</th>
                <th style="width:20%;">Concentration</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bilanTopCredits->take(5) as $i => $src)
                @php $srcPct = (float)($src['share_pct'] ?? 0); @endphp
                <tr>
                    <td style="text-align:center;"><span class="rk {{ $i === 0 ? 'rk-1' : '' }}">{{ $i + 1 }}</span></td>
                    <td><strong>{{ $src['source'] ?? ($src['beneficiary'] ?? 'Inconnu') }}</strong></td>
                    <td class="r" style="font-weight:700;color:#2E7D32;">{{ number_format((float)($src['amount'] ?? 0), 2, ',', ' ') }}</td>
                    <td class="r">{{ number_format($srcPct, 1, ',', ' ') }}%</td>
                    <td class="r">{{ (int)($src['count'] ?? 0) }}</td>
                    <td>
                        <div style="height:5px;background:#EDE4D0;border-radius:2px;">
                            <div class="ibar" style="width:{{ min(100,$srcPct) }}%;background:#2E7D32;"></div>
                        </div>
                        <span style="font-size:7.5pt;color:#8A7E72;">{{ number_format($srcPct,1,',','') }}%</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <p style="font-size:9pt;color:#5C5449;font-style:italic;">Aucune source de crédit identifiée.</p>
@endif

{{-- Pied de page 2 --}}
<div class="bilan-footer">
    <div class="bilan-footer-text"><strong>Analytica</strong> — Document produit à titre informatif sur la base d'une analyse algorithmique. Ne constitue pas un acte authentique ni une expertise judiciaire. Toute interprétation engage exclusivement son auteur.</div>
    <div class="bilan-footer-right"><strong>CONFIDENTIEL</strong><br>Analytica-1.0</div>
</div>
</div>{{-- /page-break 2 --}}

{{-- ═══════════════════════════════════════════════════ --}}
{{-- PAGE 3 — MONTANTS EXCEPTIONNELS                    --}}
{{-- ═══════════════════════════════════════════════════ --}}
<div class="page-break">
<div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #EDE4D0;padding-bottom:5px;margin-bottom:12px;">
    <div style="display:flex;align-items:center;gap:10px;">
        <img src="{{ asset('analytica-logo.png') }}?v={{ filemtime(public_path('analytica-logo.png')) }}" alt="Analytica" class="logo-mini">
        <div style="font-size:13pt;font-weight:300;">{{ $bilanTitle }}</div>
    </div>
    <div style="font-size:7.5pt;color:#8A7E72;">ANALYTICA · Bilan Notaire/Avocat · p. 3</div>
</div>
<div class="gold-bar" style="margin-bottom:4px;"></div>

<div class="sec">4 — Top 10 montants exceptionnels (≥&nbsp;{{ number_format((float)($exceptional_threshold ?? 7000), 0, ',', ' ') }}&nbsp;€)</div>
<p style="font-size:8.5pt;color:#5C5449;font-style:italic;margin-bottom:8px;">
    Mouvements unitaires les plus significatifs — débits et crédits. Classement par montant décroissant. Ces flux nécessitent un justificatif.
</p>

@if ($bilanTopExc->isNotEmpty())
    <table>
        <thead>
            <tr>
                <th style="width:3%;">#</th>
                <th style="width:10%;">Date</th>
                <th style="width:8%;">Sens</th>
                <th style="width:9%;">Type</th>
                <th>Libellé</th>
                <th class="r" style="width:14%;">Montant (€)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bilanTopExc as $i => $tx)
                @php
                    $txUp  = mb_strtoupper($tx->label ?? '');
                    $txKnd = $tx->kind ?? '';
                    if (str_contains($txUp,'CHEQUE') || str_contains($txUp,'CHQ') || $txKnd === 'cheque') {
                        $txTag = 'tch'; $txTagLbl = 'Chèque';
                    } elseif (str_contains($txUp,'ESPECE') || str_contains($txUp,'DAB') || str_contains($txUp,'RETRAIT') || in_array($txKnd,['cash_withdrawal','cash'])) {
                        $txTag = 'te'; $txTagLbl = 'Espèces';
                    } else {
                        $txTag = 'tv'; $txTagLbl = 'Virement';
                    }
                    $txDir = ($tx->type ?? '') === 'debit' ? 'debit' : 'credit';
                    $txAmt = abs((float)($tx->amount ?? 0));
                    $txLbl = cleanBankLabel($tx->display_label ?? ($tx->label ?: ''));
                    $txRowBg = ($i % 2 === 0) ? '' : 'background:#FDFBF5;';
                @endphp
                <tr style="{{ $txRowBg }}">
                    <td style="text-align:center;color:#8A7E72;font-size:8pt;font-weight:700;">{{ $i + 1 }}</td>
                    <td style="white-space:nowrap;">{{ optional($tx->date)->format('d/m/Y') }}</td>
                    <td>
                        <span class="tag {{ $txDir === 'debit' ? 'td' : 'tc' }}">
                            {{ $txDir === 'debit' ? '↓ Débit' : '↑ Crédit' }}
                        </span>
                    </td>
                    <td><span class="tag {{ $txTag }}">{{ $txTagLbl }}</span></td>
                    <td style="font-size:8.5pt;">{{ mb_strimwidth($txLbl ?: '—', 0, 95, '…') }}</td>
                    <td class="r" style="font-weight:700;font-size:10.5pt;color:{{ $txDir === 'debit' ? '#C0392B' : '#2E7D32' }};">
                        {{ $txDir === 'debit' ? '−' : '+' }}{{ number_format($txAmt, 2, ',', ' ') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Total débits exceptionnels</td>
                <td colspan="2" class="r" style="color:#C0392B;">
                    −{{ number_format($bilanTopExc->where('type','debit')->sum(fn($t)=>abs((float)($t->amount??0))),2,',', ' ') }} €
                </td>
            </tr>
        </tfoot>
    </table>
@else
    <p style="font-size:9pt;color:#5C5449;font-style:italic;">Aucun montant exceptionnel détecté avec le seuil actuel.</p>
@endif

{{-- Notice légale renforcée --}}
<div class="legal" style="margin-top:36px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
        <div class="legal-title" style="margin-bottom:0;">Avertissement — Limites et conditions d'utilisation</div>
        <img src="{{ asset('analytica-logo.png') }}?v={{ filemtime(public_path('analytica-logo.png')) }}" alt="Analytica" style="height:36px;width:auto;">
    </div>
    <p class="legal-text" style="margin-bottom:5px;">
        <strong>Nature du document.</strong> Ce document est produit à titre d'aide à la décision par l'outil Analytica (version algorithmique : Analytica-1.0).
        Il repose exclusivement sur l'analyse statistique et automatisée des relevés bancaires fournis par le mandant.
        Il <strong>ne constitue pas</strong> un acte authentique, un rapport d'expertise judiciaire, ni un avis juridique.
        Il ne saurait se substituer à une expertise comptable, patrimoniale ou judiciaire réalisée par un professionnel habilité.
    </p>
    <p class="legal-text" style="margin-bottom:5px;">
        <strong>Absence de garantie.</strong> Les résultats sont de nature descriptive et probabiliste. Ils peuvent être affectés
        par la qualité des documents source (lisibilité, complétude des relevés), par des erreurs de lecture optique (OCR),
        ou par des informations absentes du dossier. Analytica ne garantit ni l'exhaustivité ni l'exactitude absolue des données traitées.
        Aucun résultat ne vaut qualification juridique automatique.
    </p>
    <p class="legal-text" style="margin-bottom:5px;">
        <strong>Limitation de responsabilité.</strong> Le producteur de ce document, ainsi que l'éditeur du logiciel Analytica,
        déclinent toute responsabilité quant aux décisions prises sur la base de son contenu, aux erreurs ou omissions éventuelles,
        et aux conséquences juridiques, patrimoniales ou pécuniaires qui pourraient en découler.
        Toute interprétation, utilisation ou transmission engage exclusivement la responsabilité du professionnel destinataire.
    </p>
    <p class="legal-text" style="margin-bottom:5px;">
        <strong>Confidentialité &amp; protection des données (RGPD).</strong> Ce document contient des données personnelles et financières
        couvertes par le secret professionnel. Il est strictement réservé au(x) destinataire(s) désigné(s) et ne peut être
        communiqué à des tiers sans autorisation écrite préalable. Toute diffusion non autorisée est interdite et susceptible
        d'engager la responsabilité civile et pénale de son auteur (art. 226-13 C. pén.).
    </p>
    <p class="legal-text">
        <strong>Usage professionnel exclusif.</strong> Ce document est destiné à des professionnels du droit (notaires, avocats, magistrats,
        experts judiciaires) agissant dans un cadre légal défini. Il n'est pas destiné à être produit en justice sans validation
        préalable par un professionnel qualifié.
        &nbsp;&nbsp;<strong>Analytica</strong> · Analytica-1.0 · Document généré le {{ $bilanNow }}.
    </p>
</div>
</div>{{-- /page-break 3 --}}

{{-- ═══════════════════════════════════════════════════ --}}
{{-- PAGE 4 — DEMANDE DE JUSTIFICATIFS                  --}}
{{-- ═══════════════════════════════════════════════════ --}}
@php
    $bilanJustifCheques = collect($exceptional_transactions ?? [])
        ->filter(fn($tx) => ($tx->type ?? '') === 'debit')
        ->filter(function($tx) {
            $up = mb_strtoupper($tx->label ?? '');
            return str_contains($up,'CHEQUE') || str_contains($up,'CHQ') || ($tx->kind ?? '') === 'cheque';
        })
        ->sortByDesc(fn($tx) => abs((float)($tx->amount ?? 0)))->values();

    $bilanJustifVirements = collect($exceptional_transactions ?? [])
        ->filter(fn($tx) => ($tx->type ?? '') === 'debit')
        ->filter(function($tx) {
            $up = mb_strtoupper($tx->label ?? '');
            $k  = $tx->kind ?? '';
            return !str_contains($up,'CHEQUE') && !str_contains($up,'CHQ') && $k !== 'cheque'
                && !str_contains($up,'ESPECE') && !str_contains($up,'DAB') && !str_contains($up,'RETRAIT')
                && !in_array($k, ['cash_withdrawal','cash']);
        })
        ->sortByDesc(fn($tx) => abs((float)($tx->amount ?? 0)))->values();

    $bilanJustifEspeces = collect($exceptional_transactions ?? [])
        ->filter(fn($tx) => ($tx->type ?? '') === 'debit')
        ->filter(function($tx) {
            $up = mb_strtoupper($tx->label ?? '');
            $k  = $tx->kind ?? '';
            return str_contains($up,'ESPECE') || str_contains($up,'DAB') || str_contains($up,'RETRAIT')
                || in_array($k, ['cash_withdrawal','cash']);
        })
        ->sortByDesc(fn($tx) => abs((float)($tx->amount ?? 0)))->values();

    $bilanJustifAll = $bilanJustifCheques->merge($bilanJustifVirements)->merge($bilanJustifEspeces);
@endphp
<div class="page-break bilan-section-justif">
<div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #EDE4D0;padding-bottom:5px;margin-bottom:12px;">
    <div style="display:flex;align-items:center;gap:10px;">
        <img src="{{ asset('analytica-logo.png') }}?v={{ filemtime(public_path('analytica-logo.png')) }}" alt="Analytica" class="logo-mini">
        <div style="font-size:13pt;font-weight:300;">{{ $bilanTitle }}</div>
    </div>
    <div style="font-size:7.5pt;color:#8A7E72;">ANALYTICA · Bilan Notaire/Avocat · p. 4</div>
</div>
<div class="gold-bar" style="margin-bottom:4px;"></div>

<div class="sec">5 — Demande de pièces justificatives</div>
<p style="font-size:9pt;color:#2E2A25;line-height:1.7;margin-bottom:6px;">
    Dans le cadre de l'analyse des relevés de compte de <strong>{{ $bilanTitle }}</strong>, les opérations
    ci-dessous ont été identifiées au regard de leur montant unitaire (seuil&nbsp;:&nbsp;{{ number_format((float)($exceptional_threshold ?? 7000),0,',',' ') }}&nbsp;€) ou de leur nature.
    Conformément aux bonnes pratiques en matière d'analyse patrimoniale, il est recommandé de solliciter
    les pièces justificatives correspondantes afin d'en permettre la qualification juridique.
</p>
<p style="font-size:8pt;color:#8A7E72;font-style:italic;border-left:3px solid #EDE4D0;padding:5px 10px;margin-bottom:12px;">
    Ces éléments sont communiqués à titre documentaire et ne préjugent pas de la nature des opérations
    ni de la responsabilité des parties. Le présent document ne vaut pas acte authentique ni expertise judiciaire.
</p>

@if ($bilanJustifCheques->isNotEmpty())
<div style="margin-top:12px;">
    <div style="font-size:8.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#1C1916;border-left:3px solid #C9A84C;padding-left:8px;margin-bottom:6px;">a) Chèques émis</div>
    <p style="font-size:8pt;color:#5C5449;font-style:italic;margin-bottom:5px;">Le chèque ne permettant pas l'identification automatique du bénéficiaire final, il est recommandé de produire les talons de chèquier, les avis de débit et tout justificatif de la contrepartie.</p>
    <table>
        <thead><tr>
            <th style="width:11%;">Date</th><th style="width:13%;">N° Chèque</th>
            <th>Libellé OCR (normalisé)</th>
            <th class="r" style="width:14%;">Montant</th>
            <th style="width:20%;">Pièce requise</th>
        </tr></thead>
        <tbody>
        @foreach ($bilanJustifCheques as $tx)
        <tr>
            <td>{{ optional($tx->date)->format('d/m/Y') }}</td>
            <td style="font-size:8pt;color:#5C5449;">{{ $tx->cheque_number ?: '—' }}</td>
            <td style="font-size:8.5pt;">{{ mb_strimwidth(cleanBankLabel($tx->label ?: ''), 0, 82, '…') }}</td>
            <td class="r" style="font-weight:700;color:#C0392B;">−{{ number_format(abs((float)$tx->amount),2,',',' ') }} €</td>
            <td style="font-size:7.5pt;color:#5C5449;">Talon / identité bénéficiaire</td>
        </tr>
        @endforeach
        </tbody>
        <tfoot><tr>
            <td colspan="3">Total chèques ({{ $bilanJustifCheques->count() }} opér.)</td>
            <td class="r" style="color:#C0392B;">−{{ number_format($bilanJustifCheques->sum(fn($t)=>abs((float)($t->amount??0))),2,',',' ') }} €</td>
            <td></td>
        </tr></tfoot>
    </table>
</div>
@endif

@if ($bilanJustifVirements->isNotEmpty())
<div style="margin-top:16px;">
    <div style="font-size:8.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#1C1916;border-left:3px solid #C9A84C;padding-left:8px;margin-bottom:6px;">b) Virements débit significatifs</div>
    <p style="font-size:8pt;color:#5C5449;font-style:italic;margin-bottom:5px;">Ces sorties de fonds d'un montant unitaire supérieur au seuil d'alerte appellent la vérification de leur objet, contrepartie et destination (contrat, facture, acte, convention de prêt…).</p>
    <table>
        <thead><tr>
            <th style="width:11%;">Date</th>
            <th>Bénéficiaire / motif (libellé OCR normalisé)</th>
            <th class="r" style="width:14%;">Montant</th>
            <th style="width:22%;">Pièce requise</th>
        </tr></thead>
        <tbody>
        @foreach ($bilanJustifVirements as $tx)
        <tr>
            <td>{{ optional($tx->date)->format('d/m/Y') }}</td>
            <td style="font-size:8.5pt;">{{ mb_strimwidth(cleanBankLabel($tx->label ?: ''), 0, 92, '…') }}</td>
            <td class="r" style="font-weight:700;color:#C0392B;">−{{ number_format(abs((float)$tx->amount),2,',',' ') }} €</td>
            <td style="font-size:7.5pt;color:#5C5449;">Contrat / facture / objet</td>
        </tr>
        @endforeach
        </tbody>
        <tfoot><tr>
            <td colspan="2">Total virements ({{ $bilanJustifVirements->count() }} opér.)</td>
            <td class="r" style="color:#C0392B;">−{{ number_format($bilanJustifVirements->sum(fn($t)=>abs((float)($t->amount??0))),2,',',' ') }} €</td>
            <td></td>
        </tr></tfoot>
    </table>
</div>
@endif

@if ($bilanJustifEspeces->isNotEmpty())
<div style="margin-top:16px;">
    <div style="font-size:8.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#1C1916;border-left:3px solid #C9A84C;padding-left:8px;margin-bottom:6px;">c) Retraits espèces significatifs</div>
    <p style="font-size:8pt;color:#5C5449;font-style:italic;margin-bottom:5px;">Les espèces ne laissent pas de trace de bénéficiaire final. Ces retraits sont signalés à titre documentaire ; leur destination éventuelle pourrait nécessiter un éclaircissement.</p>
    <table>
        <thead><tr>
            <th style="width:11%;">Date</th>
            <th>Libellé — Point de retrait</th>
            <th class="r" style="width:14%;">Montant</th>
            <th style="width:25%;">Observation</th>
        </tr></thead>
        <tbody>
        @foreach ($bilanJustifEspeces as $tx)
        <tr>
            <td>{{ optional($tx->date)->format('d/m/Y') }}</td>
            <td style="font-size:8.5pt;">{{ mb_strimwidth(cleanBankLabel($tx->label ?: ''), 0, 92, '…') }}</td>
            <td class="r" style="font-weight:700;color:#C0392B;">−{{ number_format(abs((float)$tx->amount),2,',',' ') }} €</td>
            <td style="font-size:7.5pt;color:#5C5449;">Espèces — destination inconnue</td>
        </tr>
        @endforeach
        </tbody>
        <tfoot><tr>
            <td colspan="2">Total espèces ({{ $bilanJustifEspeces->count() }} opér.)</td>
            <td class="r" style="color:#C0392B;">−{{ number_format($bilanJustifEspeces->sum(fn($t)=>abs((float)($t->amount??0))),2,',',' ') }} €</td>
            <td></td>
        </tr></tfoot>
    </table>
</div>
@endif

@if ($bilanJustifAll->isNotEmpty())
<div style="border:1px solid #C9A84C;padding:12px 14px;margin-top:18px;background:#FDFBF5;">
    <div style="font-size:8.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.09em;color:#1C1916;margin-bottom:8px;">Synthèse — opérations appelant un justificatif</div>
    @if ($bilanJustifCheques->isNotEmpty())
    <div style="display:flex;justify-content:space-between;font-size:9pt;padding:3px 0;border-bottom:1px dotted #EDE4D0;">
        <span>Chèques émis ({{ $bilanJustifCheques->count() }} opér.)</span>
        <span style="font-weight:700;color:#C0392B;">−{{ number_format($bilanJustifCheques->sum(fn($t)=>abs((float)($t->amount??0))),0,',',' ') }} €</span>
    </div>
    @endif
    @if ($bilanJustifVirements->isNotEmpty())
    <div style="display:flex;justify-content:space-between;font-size:9pt;padding:3px 0;border-bottom:1px dotted #EDE4D0;">
        <span>Virements significatifs ({{ $bilanJustifVirements->count() }} opér.)</span>
        <span style="font-weight:700;color:#C0392B;">−{{ number_format($bilanJustifVirements->sum(fn($t)=>abs((float)($t->amount??0))),0,',',' ') }} €</span>
    </div>
    @endif
    @if ($bilanJustifEspeces->isNotEmpty())
    <div style="display:flex;justify-content:space-between;font-size:9pt;padding:3px 0;border-bottom:1px dotted #EDE4D0;">
        <span>Retraits espèces ({{ $bilanJustifEspeces->count() }} opér.)</span>
        <span style="font-weight:700;color:#C0392B;">−{{ number_format($bilanJustifEspeces->sum(fn($t)=>abs((float)($t->amount??0))),0,',',' ') }} €</span>
    </div>
    @endif
    <div style="display:flex;justify-content:space-between;font-size:10.5pt;padding:6px 0 0;font-weight:700;">
        <span>TOTAL DÉBITS À JUSTIFIER</span>
        <span style="color:#C0392B;">−{{ number_format($bilanJustifAll->sum(fn($t)=>abs((float)($t->amount??0))),0,',',' ') }} €</span>
    </div>
</div>
@else
<div style="font-size:9pt;color:#5C5449;font-style:italic;margin-top:10px;">Aucune opération exceptionnelle de type débit identifiée avec le seuil actuel.</div>
@endif


{{-- Pied de page 4 --}}
<div class="bilan-footer">
    <div class="bilan-footer-text"><strong>Analytica</strong> — Document produit à titre informatif. Ne constitue pas un acte authentique ni une expertise judiciaire. La liste des opérations ci-dessus est fournie à titre indicatif et ne préjuge pas de leur nature juridique.</div>
    <div class="bilan-footer-right"><strong>CONFIDENTIEL</strong><br>Analytica-1.0</div>
</div>

</div>{{-- /page-break 4 --}}

</div>{{-- /page --}}
</template>

{{-- ============================================================
     MODAL DE CONFIGURATION — BILAN NOTAIRE / AVOCAT
     ============================================================ --}}
<div id="bilan-config-modal" class="hidden fixed inset-0 z-50" style="display:none;align-items:center;justify-content:center;background:rgba(28,25,22,0.72);">
    <div style="background:#fff;border:1px solid rgba(201,168,76,0.55);max-width:560px;width:95%;max-height:90vh;overflow-y:auto;padding:26px 28px 22px;box-shadow:0 24px 64px rgba(0,0,0,0.38);">

        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
            <div>
                <div style="font-size:7.5pt;letter-spacing:0.18em;text-transform:uppercase;color:#8A7E72;font-family:Georgia,serif;">Analytica &nbsp;·&nbsp; <span style="color:#C9A84C;font-weight:700;">Configuration</span></div>
                <div style="font-family:'Cormorant Garamond',Georgia,serif;font-size:18pt;font-weight:300;color:#1C1916;line-height:1.1;margin-top:4px;">Bilan Notaire / Avocat</div>
            </div>
            <button type="button" onclick="closeBilanConfig()" style="background:none;border:none;font-size:22pt;color:#8A7E72;cursor:pointer;line-height:1;padding:0;">&times;</button>
        </div>
        <div style="height:2px;background:linear-gradient(90deg,#C9A84C,#E0C278,#C9A84C);margin-bottom:16px;"></div>

        {{-- Sections --}}
        <div style="margin-bottom:16px;">
            <div style="font-size:7.5pt;letter-spacing:0.13em;text-transform:uppercase;color:#5C5449;margin-bottom:9px;font-weight:600;">Sections à inclure</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:7px;font-size:9pt;">
                <label style="display:flex;align-items:center;gap:8px;color:#8A7E72;">
                    <input type="checkbox" checked disabled> Page 1 — Diagnostic &amp; score
                </label>
                <label style="display:flex;align-items:center;gap:8px;color:#8A7E72;">
                    <input type="checkbox" checked disabled> Page 2 — Bénéficiaires &amp; sources
                </label>
                <label style="display:flex;align-items:center;gap:8px;color:#8A7E72;">
                    <input type="checkbox" checked disabled> Page 3 — Montants exceptionnels
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" id="bc-justif" checked> Page 4 — Demande de justificatifs
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" id="bc-ai" {{ $ai ? 'checked' : '' }}> Analyse algorithmique
                    @if (!$ai) <span style="font-size:7.5pt;color:#8A7E72;">(non disponible)</span> @endif
                </label>
            </div>
        </div>

        <div style="height:1px;background:#EDE4D0;margin-bottom:14px;"></div>

        {{-- Bloc IA --}}
        <div style="border:1px solid #EDE4D0;padding:14px;margin-bottom:16px;">
            <div style="font-size:7.5pt;letter-spacing:0.13em;text-transform:uppercase;color:#5C5449;margin-bottom:10px;font-weight:600;">⚡ Analyse IA ciblée</div>

            @if ($ai)
                <div style="font-size:8.5pt;color:#2E7D32;margin-bottom:8px;padding:5px 9px;background:#F0FFF0;border-left:3px solid #A5D6A7;">
                    ✓ Analyse disponible
                    @if (!empty($lastAi['ran_at'])) &nbsp;— générée le {{ \Carbon\Carbon::parse($lastAi['ran_at'])->format('d/m/Y \à H\hi') }} @endif
                </div>
            @else
                <div style="font-size:8.5pt;color:#8A7E72;margin-bottom:8px;padding:6px 9px;background:#FDFBF5;border-left:3px solid #EDE4D0;">
                    Aucune analyse IA disponible. Lancez-en une ci-dessous puis revenez générer le bilan.
                </div>
            @endif

            <div style="font-size:8.5pt;color:#5C5449;margin-bottom:6px;">Choisissez un prompt type ou rédigez le vôtre&nbsp;:</div>
            <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:8px;">
                @foreach([
                    'Analyse globale'          => '',
                    'Focus succession'         => 'Concentre-toi sur les opérations inhabituelles dans les 6 mois avant et après toute date clé du dossier. Identifie les retraits espèces, chèques non justifiés et virements vers des tiers non identifiés. Formule de manière neutre pour un juge.',
                    'Focus retraits espèces'   => 'Analyse tous les retraits espèces : fréquence, montants, évolution chronologique. Identifie les pics et comportements atypiques susceptibles d\'intéresser un notaire ou magistrat.',
                    'Focus chèques suspects'   => 'Identifie tous les chèques émis. Classe par montant décroissant. Signale les séries inhabituelles ou les montants sans contrepartie apparente.',
                    'Focus virements familiaux'=> 'Analyse les flux entre membres de la famille ou entités liées. Identifie donations potentielles, virements récurrents et transferts inhabituels pouvant caractériser un appauvrissement patrimonial.',
                ] as $bcLbl => $bcPmt)
                    <button type="button"
                        onclick="document.getElementById('bc-modal-prompt').value={{ Js::from($bcPmt) }};document.getElementById('bc-modal-prompt').focus();"
                        style="padding:3px 10px;font-size:8pt;border:1px solid #EDE4D0;background:#fff;cursor:pointer;font-family:inherit;border-radius:1px;"
                        onmouseover="this.style.borderColor='#C9A84C';this.style.background='#FDFBF5';"
                        onmouseout="this.style.borderColor='#EDE4D0';this.style.background='#fff';">
                        {{ $bcLbl }}
                    </button>
                @endforeach
            </div>
            <form method="POST" action="{{ route('cases.ai', $case) }}">
                @csrf
                <textarea id="bc-modal-prompt" name="prompt" rows="3"
                    style="width:100%;padding:8px;font-size:9pt;font-family:inherit;border:1px solid #EDE4D0;resize:vertical;outline:none;"
                    onfocus="this.style.borderColor='#C9A84C'" onblur="this.style.borderColor='#EDE4D0'"
                    placeholder="Ex : Identifie les flux inhabituels dans les 3 mois précédant le décès, supérieurs à 1 000 €.">{{ old('prompt', (string)($lastAi['prompt'] ?? '')) }}</textarea>
                <button type="submit"
                    style="margin-top:8px;width:100%;padding:8px 16px;background:linear-gradient(135deg,#2E2A25,#1C1916);color:#E0C278;border:1px solid rgba(201,168,76,0.4);font-size:8.5pt;letter-spacing:0.06em;cursor:pointer;font-family:inherit;">
                    Lancer l'analyse IA →
                    <span style="font-size:7.5pt;opacity:0.65;margin-left:6px;">(la page se recharge, rouvrez ensuite ce bilan)</span>
                </button>
            </form>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
            <button type="button" onclick="closeBilanConfig()"
                style="padding:8px 18px;border:1px solid #EDE4D0;background:#fff;font-size:9pt;cursor:pointer;font-family:inherit;"
                onmouseover="this.style.background='#FDFBF5'" onmouseout="this.style.background='#fff'">
                Annuler
            </button>
            <button type="button" onclick="generateBilanFromConfig()"
                style="padding:9px 24px;background:linear-gradient(135deg,#2E2A25,#1C1916);color:#E0C278;border:1px solid rgba(201,168,76,0.45);font-size:10pt;font-weight:600;letter-spacing:0.05em;cursor:pointer;font-family:inherit;">
                Générer le bilan PDF ↓
            </button>
        </div>
    </div>
</div>
</x-app-layout>
