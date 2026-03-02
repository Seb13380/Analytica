<?php
/**
 * Patch: StatementImportService date parser — fix cross-year confusion.
 *
 * ROOT CAUSE: extractBnpDateFromBlock Priority 2 passes the global $period
 * (from the FIRST page) to parseDateWithPeriod even when rollingYear is 2021+.
 * parseDateWithPeriod loops only over years in the PERIOD RANGE (e.g. 2020) and
 * ignores the passed effectiveYear → assigns 2020-MM-DD to 2021 transactions.
 *
 * FIX 1 (extractBnpDateFromBlock Priority 2):
 *   — blockYear (explicit DDMMYY in block): direct construction, no period.
 *   — localPeriod (block's own "du X au Y"): use parseDateWithPeriod with LOCAL period only.
 *   — rollingYear: direct construction (do NOT pass stale global period).
 *   — effectiveYear / last-resort: original behaviour preserved.
 *
 * FIX 2 (parseDateWithPeriod):
 *   Include $defaultYear in the candidate year loop even when outside the period range.
 *   Add a strong preference bias for $defaultYear (90% delta discount) so confirmed
 *   rolling years beat stale period-based candidates when no date falls inside the window.
 */

$file = __DIR__ . '/../app/Services/StatementImportService.php';
$c = file_get_contents($file);
if ($c === false) {
    echo "ERROR: cannot read $file\n"; exit(1);
}

// ─────────────────────────────────────────────────────────────────────────────
// FIX 1 — Priority 2 block
// ─────────────────────────────────────────────────────────────────────────────
$marker = '        // ── Priority 2: bare DD.MM on anchor, year from blockYear/rollingYear ───';
$pos = strpos($c, $marker);
if ($pos === false) {
    echo "FIX1: marker NOT FOUND\n"; exit(1);
}

// Find the end of Priority 2 block (up to Priority 3 comment)
$endMarker = '        // ── Priority 3: OCR artifact';
$endPos = strpos($c, $endMarker, $pos);
if ($endPos === false) {
    echo "FIX1: end-marker NOT FOUND\n"; exit(1);
}

$oldBlock = substr($c, $pos, $endPos - $pos);

$newBlock = <<<'PHP'
        // ── Priority 2: bare DD.MM on anchor, year from blockYear/rollingYear ───
        //
        // KEY RULE for multi-year combined PDFs:
        //  — blockYear (explicit DDMMYY found in this block): trusted directly.
        //  — localPeriod ("du X au Y" found INSIDE this block): used for year-boundary
        //    disambiguation (e.g. Dec→Jan crossing within the same page).
        //  — rollingYear (propagated from prior blocks): trusted directly.
        //    Do NOT pass it through parseDateWithPeriod with the GLOBAL period: that
        //    period comes from the FIRST page of the document and is stale for pages
        //    2–N. A 2021 rollingYear through period{2020-12} silently returns 2020-MM-DD.
        //  — Global effectivePeriod: only used as last resort when no year context exists.
        if (preg_match('/^(\d{2})[\/\-.](\d{2})\b/u', $anchor, $m)) {
            $day   = (int) $m[1];
            $month = (int) $m[2];
            if ($day >= 1 && $day <= 31 && $month >= 1 && $month <= 12) {
                $ddmm = sprintf('%02d.%02d', $day, $month);

                // 2a. blockYear: explicit DDMMYY in this block — highest trust.
                if ($blockYear !== null) {
                    $candidate = sprintf('%04d-%02d-%02d', $blockYear, $month, $day);
                    try { \Carbon\Carbon::parse($candidate); return $candidate; } catch (\Throwable) {}
                }

                // 2b. localPeriod (block's own "du X au Y") — correct for year-boundary pages.
                if ($localPeriod !== null) {
                    $parsed = $this->parseDateWithPeriod($ddmm, $effectiveYear, $localPeriod);
                    if ($parsed !== null) {
                        return $parsed;
                    }
                }

                // 2c. rollingYear: direct construction — do NOT use stale global period here.
                if ($rollingYear !== null) {
                    $candidate = sprintf('%04d-%02d-%02d', $rollingYear, $month, $day);
                    try { \Carbon\Carbon::parse($candidate); return $candidate; } catch (\Throwable) {}
                }

                // 2d. effectiveYear (= defaultYear from first-page): direct construction.
                if ($effectiveYear !== null) {
                    $candidate = sprintf('%04d-%02d-%02d', $effectiveYear, $month, $day);
                    try {
                        \Carbon\Carbon::parse($candidate);
                        return $candidate;
                    } catch (\Throwable) {
                        // invalid calendar date (e.g. 31 April) — fall through
                    }
                }

                // 2e. No year context — last resort, global period-aware resolution.
                $parsed = $this->parseDateWithPeriod($ddmm, $defaultYear, $effectivePeriod);
                if ($parsed !== null) {
                    return $parsed;
                }
            }
        }

PHP;

$c = substr($c, 0, $pos) . $newBlock . substr($c, $endPos);
echo "FIX1: Priority-2 block replaced OK\n";

// ─────────────────────────────────────────────────────────────────────────────
// FIX 2 — parseDateWithPeriod: include $defaultYear in candidate loop
// ─────────────────────────────────────────────────────────────────────────────
$oldInner = <<<'PHPOLD'
        // Build candidate dates for every year in the period range (usually 1 or 2 years).
        $bestDate = null;
        $bestDelta = PHP_INT_MAX;

        for ($y = $startYear; $y <= $endYear; $y++) {
            $candidate = sprintf('%04d-%02d-%02d', $y, $month, $day);
            try {
                $dt = Carbon::parse($candidate);
                // Score by distance from the statement window.
                $startTs = Carbon::parse($startDate)->timestamp;
                $endTs   = Carbon::parse($endDate)->timestamp;
                $ts      = $dt->timestamp;

                if ($ts >= $startTs && $ts <= $endTs) {
                    // Perfect: inside the statement window.
                    return $candidate;
                }

                $delta = min(abs($ts - $startTs), abs($ts - $endTs));
                if ($delta < $bestDelta) {
                    $bestDelta = $delta;
                    $bestDate  = $candidate;
                }
            } catch (\Throwable) {
                // invalid date (e.g. 31 April) — skip
            }
        }

        // If no year in the period range matched, fall back to default year.
        return $bestDate ?? $this->parseDate($raw, $defaultYear);
PHPOLD;

$newInner = <<<'PHPNEW'
        // Build candidate years: period range + $defaultYear if outside range.
        // This ensures that when a confirmed rollingYear (passed as $defaultYear) differs
        // from the (potentially stale) period range, it is still evaluated as a candidate.
        $yearsToTry = range($startYear, $endYear);
        if ($defaultYear !== null && !in_array($defaultYear, $yearsToTry, true)) {
            $yearsToTry[] = $defaultYear;
        }

        $bestDate     = null;
        $bestDelta    = PHP_INT_MAX;
        $bestInWindow = null; // best candidate inside the statement window

        foreach ($yearsToTry as $y) {
            $candidate = sprintf('%04d-%02d-%02d', $y, $month, $day);
            try {
                $dt      = Carbon::parse($candidate);
                $startTs = Carbon::parse($startDate)->timestamp;
                $endTs   = Carbon::parse($endDate)->timestamp;
                $ts      = $dt->timestamp;

                if ($ts >= $startTs && $ts <= $endTs) {
                    // Perfect: inside the statement window.
                    // Prefer defaultYear when tie (deterministic).
                    if ($bestInWindow === null || $y === $defaultYear) {
                        $bestInWindow = $candidate;
                    }
                    continue;
                }

                $delta = min(abs($ts - $startTs), abs($ts - $endTs));
                // Strong bias for defaultYear/rollingYear: 90 % discount on distance.
                // This prevents a stale global period (e.g. 2020-12) from beating a
                // confirmed rolling year (e.g. 2021) when neither falls in the window.
                if ($y === $defaultYear) {
                    $delta = (int) ($delta * 0.1);
                }
                if ($delta < $bestDelta) {
                    $bestDelta = $delta;
                    $bestDate  = $candidate;
                }
            } catch (\Throwable) {
                // invalid date (e.g. 31 April) — skip
            }
        }

        // A date inside the statement window wins over the proximity fallback.
        return $bestInWindow ?? $bestDate ?? $this->parseDate($raw, $defaultYear);
PHPNEW;

if (strpos($c, $oldInner) !== false) {
    $c = str_replace($oldInner, $newInner, $c);
    echo "FIX2: parseDateWithPeriod year loop replaced OK\n";
} else {
    echo "FIX2: inner loop pattern NOT FOUND — skipping\n";
}

// Write result
if (file_put_contents($file, $c) === false) {
    echo "ERROR: cannot write $file\n"; exit(1);
}
echo "Done. File written.\n";
