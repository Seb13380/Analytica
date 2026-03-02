<?php

namespace App\Services;

class Normalization
{
    public static function cleanLabel(string $label): string
    {
        $label = preg_replace('/\s+/u', ' ', trim($label)) ?? trim($label);
        $label = str_replace(["\u{00A0}"], ' ', $label);

        // ── OCR repair: colon between two letters is always a misread of 'i' ──
        // e.g. "G:ordano" → "Giordano", "Adhes:on" → "Adhesion", "Em:s" → "Emis",
        // "Comm:ss:ons" → "Commissions", "Cot:sat:on" → "Cotisation", "V:rement" → "Virement"
        // Safe globally: a colon inside a word is never valid in French/SEPA labels.
        // Apply repeatedly until stable (handles "Comm:ss:ons" needing 2 passes).
        $prev = null;
        while ($prev !== $label) {
            $prev = $label;
            $label = preg_replace('/([A-Za-zÀ-ÿ]):([A-Za-zÀ-ÿ])/u', '$1i$2', $label) ?? $label;
        }

        // ── OCR repair: colon at word-start followed by a letter → 'I' + letter ──
        // e.g. "SCT :NST EMIS" → "SCT INST EMIS"  (the colon-after-space case)
        $label = preg_replace('/(?<=\s):([A-Za-zÀ-ÿ])/u', 'I$1', $label) ?? $label;

        // ── OCR repair: CHH + R → CHR (serif font misread in names like CHRISTIAN) ──
        // "CHHRISTIAN" → "CHRISTIAN", "CHHR" → "CHR"
        $label = preg_replace('/\bCHH([A-Za-zÀ-ÿ])/u', 'CH$1', $label) ?? $label;

        // ── OCR repair: apostrophe as 'i' in very common BNP banking words ──
        // Targeted to avoid breaking legitimate L'Atelier, C'est, etc.
        $label = preg_replace("/\\bV'R\\b/u", 'VIR', $label) ?? $label;
        $label = preg_replace("/\\bV'r\\b/u", 'Vir', $label) ?? $label;
        $label = preg_replace("/\\bCL'ENT/iu", 'CLIENT', $label) ?? $label;
        $label = preg_replace("/\\bL'L(?=ANE|'ANE)/iu", 'LIL', $label) ?? $label;

        // ── OCR repair: apostrophe replacing a missing 'i' in known proper names ──
        // "Lil'ane" → "Liliane", "Chr'stian" / "Chr'st'an" → "Christian"
        // Scoped to known names only — apostrophe has legitimate uses (L'Atelier).
        $label = preg_replace("/\\bLil'ane\\b/iu", 'Liliane', $label) ?? $label;
        $label = preg_replace("/\\bChr'st(?:i|')?an\\b/iu", 'Christian', $label) ?? $label;
        $label = preg_replace("/\\bG'ordano\\b/iu", 'Giordano', $label) ?? $label;
        $label = preg_replace("/\\bAnthon'y?\\b/iu", 'Anthony', $label) ?? $label;
        $label = preg_replace("/\\bImmob'l(?:ier|'er)?\\b/iu", 'Immobilier', $label) ?? $label;

        // ── OCR dictionary: word-level corrections for persistent misreads ──
        $label = self::applyOcrDictionary($label);

        return trim($label);
    }

    /**
     * Word-level OCR correction dictionary.
     * Applies known OCR → correct word mappings, case-insensitive, preserving original case.
     * Entries are ordered from longest to shortest to avoid partial matches.
     */
    private static function applyOcrDictionary(string $label): string
    {
        // Format: 'OCR_MISREAD' => 'CORRECTION'
        // Matched as whole words (\b boundaries), case-insensitive.
        // Replacement preserves the case pattern of the original match.
        static $dict = [
            // ── Banking operation keywords ──
            'VREMENT'       => 'VIREMENT',
            'VREMENTS'      => 'VIREMENTS',
            'VIREMANT'      => 'VIREMENT',
            'RETRAÎT'       => 'RETRAIT',
            'RETRAÎT'       => 'RETRAIT',   // UTF-8 Î variant
            'RETRAÏTS'      => 'RETRAITS',
            'RETRAÎT'       => 'RETRAIT',
            'PRLVEMENT'     => 'PRELEVEMENT',
            'PRÉLÈVMENT'    => 'PRELEVEMENT',
            'PRELEVMENT'    => 'PRELEVEMENT',
            'REMBOURSMENT'  => 'REMBOURSEMENT',
            'REMBORSEMENT'  => 'REMBOURSEMENT',
            'COMISSION'     => 'COMMISSION',
            'COMMISION'     => 'COMMISSION',
            'COTISATON'     => 'COTISATION',
            'COTSATION'     => 'COTISATION',
            'ADHÉSION'      => 'ADHESION',
            'ADHESON'       => 'ADHESION',
            'PRESTATON'     => 'PRESTATION',
            'PRESTAION'     => 'PRESTATION',
            'PRESTATOIN'    => 'PRESTATION',
            'FACURE'        => 'FACTURE',
            'FACUTRE'       => 'FACTURE',
            'ASSURANSE'     => 'ASSURANCE',
            'MUTELLE'       => 'MUTUELLE',
            'ABONNMENT'     => 'ABONNEMENT',
            'ABONEMENT'     => 'ABONNEMENT',
            'IDENTITES'     => 'IDENTITES',   // already correct after ':' fix
            // ── Proper names ──
            'GIORDAMO'      => 'GIORDANO',
            'GIORDAÑO'      => 'GIORDANO',
            'GORDANO'       => 'GIORDANO',
            'GORDAN0'       => 'GIORDANO',
            "CHRST'AN"      => 'CHRISTIAN',
            'CHRSTIAN'      => 'CHRISTIAN',
            'LILANE'        => 'LILIANE',
            "L'LANE"        => 'LILIANE',
            'LILIAN'        => 'LILIANE',  // truncated OCR
            'ANTHOMY'       => 'ANTHONY',
            'EMMILE'        => 'EMILIE',
            'EMILE'         => 'EMILE',   // keep (could be correct)
            'NOVACK'        => 'NOVAK',
            'N0VAK'         => 'NOVAK',   // zero instead of O
            // ── Common institutions / merchants ──
            'MALAKOF'       => 'MALAKOFF',
            'MALAKOFS'      => 'MALAKOFF',
            'HUMANIS'       => 'HUMANIS',  // correct
            'HUMANÏS'       => 'HUMANIS',
            'SOCETE'        => 'SOCIETE',
            'SOCIÉTE'       => 'SOCIETE',
            'SCOCIETE'      => 'SOCIETE',
            'SCALAPAY'      => 'SCALAPAY', // correct
            'PARIBAS'       => 'PARIBAS',  // correct
            'KLARNA'        => 'KLARNA',   // correct
            'IMOBILIER'     => 'IMMOBILIER',
            'IMOBIL'        => 'IMMOBIL',
            // ── OCR number-in-word confusions ──
            'l0CATION'      => 'LOCATION', // l=l, 0=O
            // ── Notaire + lieux ──
            'MILLIET ET GERALDINE' => 'MILLIET GERALDINE', // ET parasite entre nom et prénom
            'MAÎTRE'        => 'MAITRE',
            'PUILOUB:ER'    => 'PUILOUBIER',
            'PU:LOUBIER'    => 'PUILOUBIER',
            'PUILOUBIER'    => 'PUILOUBIER',  // correct
        ];

        foreach ($dict as $wrong => $right) {
            $pattern = '/\b' . preg_quote($wrong, '/') . '\b/iu';
            if (preg_match($pattern, $label)) {
                $label = preg_replace($pattern, $right, $label) ?? $label;
            }
        }

        return $label;
    }

    public static function normalizeLabel(string $label): string
    {
        $label = self::cleanLabel($label);
        $label = mb_strtoupper($label);
        $label = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label) ?: $label;
        $label = preg_replace('/[^A-Z0-9 ]+/', ' ', $label) ?? $label;
        $label = preg_replace('/\s+/u', ' ', $label) ?? $label;

        // Repair common OCR splits in proper names caused by apostrophes/colons being
        // replaced with spaces (e.g. "G'ORDANO" → "G ORDANO" after char stripping).
        $label = preg_replace('/\bGI?\s+ORDANO\b/', 'GIORDANO', $label) ?? $label;
        $label = preg_replace('/\bNO\s+VAK\b/', 'NOVAK', $label) ?? $label;
        $label = preg_replace('/\bLIL\s+I?ANE\b/', 'LILIANE', $label) ?? $label;
        $label = preg_replace('/\bAN\s+THONY\b/', 'ANTHONY', $label) ?? $label;
        $label = preg_replace('/\bVHRISTIAN\b/', 'CHRISTIAN', $label) ?? $label;

        // Repair OCR misreads of known mining institutions.
        // "CANSS MENES" / "CANSS MENES" → "CANSS MINES"  (E lu à la place de I)
        $label = preg_replace('/\bCANSS\s+M[EE]N[EE]S\b/', 'CANSS MINES', $label) ?? $label;
        // "MM AG RC ARRCO" → "MM AGRC ARRCO" (espace parasite dans l'acronyme)
        $label = preg_replace('/\bAG\s+RC\b/', 'AGRC', $label) ?? $label;
        $label = preg_replace('/\bAGE\s+RC\b/', 'AGERC', $label) ?? $label;

        return trim($label);
    }

    public static function parseAmount(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // Handle formats like "1 234,56" or "1,234.56" or "-1234.56".
        $raw = str_replace(["\u{00A0}", ' '], '', $raw);

        $hasComma = str_contains($raw, ',');
        $hasDot = str_contains($raw, '.');

        if ($hasComma && $hasDot) {
            // Assume comma is thousands separator, dot is decimal.
            $raw = str_replace(',', '', $raw);
        } elseif ($hasComma && !$hasDot) {
            // Assume comma is decimal.
            $raw = str_replace(',', '.', $raw);
        }

        if (!preg_match('/^-?\d+(\.\d{1,2})?$/', $raw)) {
            return null;
        }

        return $raw;
    }
}
