<?php

namespace App\Services;

class Normalization
{
    public static function cleanLabel(string $label): string
    {
        $label = preg_replace('/\s+/u', ' ', trim($label)) ?? trim($label);
        $label = str_replace(["\u{00A0}"], ' ', $label);

        return trim($label);
    }

    public static function normalizeLabel(string $label): string
    {
        $label = self::cleanLabel($label);
        $label = mb_strtoupper($label);
        $label = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label) ?: $label;
        $label = preg_replace('/[^A-Z0-9 ]+/', ' ', $label) ?? $label;
        $label = preg_replace('/\s+/u', ' ', $label) ?? $label;

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
