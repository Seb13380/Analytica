<?php

namespace App\Services;

use App\Models\BankAccount;
use Carbon\Carbon;

class StatementImportService
{
    /**
     * Parse decrypted statement bytes.
     *
     * For MVP: supports CSV (comma/semicolon) with headers including: date, label, amount, balance_after.
     *
     * @return array<int, array{date:string,label:string,normalized_label:string,amount:string,type:string,balance_after:?string,beneficiary_detected:bool,rule_flags:array}>
     */
    public function parseTransactions(string $bytes): array
    {
        $lines = preg_split('/\R/u', $bytes) ?: [];
        $lines = array_values(array_filter($lines, fn ($l) => trim((string) $l) !== ''));

        if ($lines === []) {
            return [];
        }

        $delimiter = str_contains($lines[0], ';') ? ';' : ',';
        $header = str_getcsv($lines[0], $delimiter);
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);

        $idxDate = array_search('date', $header, true);
        $idxLabel = array_search('label', $header, true);
        $idxAmount = array_search('amount', $header, true);
        $idxBalance = array_search('balance_after', $header, true);

        if ($idxDate === false || $idxLabel === false || $idxAmount === false) {
            return [];
        }

        $out = [];
        for ($i = 1; $i < count($lines); $i++) {
            $row = str_getcsv($lines[$i], $delimiter);
            if (!isset($row[$idxDate], $row[$idxLabel], $row[$idxAmount])) {
                continue;
            }

            $date = $this->parseDate($row[$idxDate]);
            $label = Normalization::cleanLabel((string) $row[$idxLabel]);
            $amount = Normalization::parseAmount((string) $row[$idxAmount]);

            if ($date === null || $amount === null || $label === '') {
                continue;
            }

            $type = ((float) $amount) >= 0 ? 'credit' : 'debit';
            $amount = $this->enforceAmountSignByType($amount, $type);
            $normalized = Normalization::normalizeLabel($label);
            $balanceAfter = null;
            if ($idxBalance !== false && isset($row[$idxBalance])) {
                $balanceAfter = Normalization::parseAmount((string) $row[$idxBalance]);
            }

            $structured = $this->extractStructuredFields($label, $type);

            $out[] = [
                'date' => $date,
                'label' => $label,
                'normalized_label' => $normalized,
                'amount' => $amount,
                'type' => $type,
                'balance_after' => $balanceAfter,
                'beneficiary_detected' => $normalized !== '',
                'rule_flags' => [],
                ...$structured,
            ];
        }

        return $out;
    }

    /**
     * Final post-processing for high-precision imports.
     *
     * - Enforce sign coherence with debit/credit type
     * - Score confidence and attach quality flags
     * - Sort chronologically
     * - Remove strict duplicates
     * - Optionally keep only transactions above min confidence
     *
     * @param  array<int, array<string,mixed>>  $transactions
     * @return array<int, array<string,mixed>>
     */
    public function finalizeTransactions(array $transactions, ?string $statementText = null, ?int $minConfidence = null): array
    {
        $period = $statementText ? $this->extractStatementPeriod($statementText) : null;
        $prepared = [];

        foreach ($transactions as $tx) {
            $date = (string) ($tx['date'] ?? '');
            $label = (string) ($tx['label'] ?? '');
            $type = (string) ($tx['type'] ?? 'credit');
            $amount = (string) ($tx['amount'] ?? '0');

            if ($date === '' || $label === '') {
                continue;
            }

            $type = $type === 'debit' ? 'debit' : 'credit';
            $amount = $this->enforceAmountSignByType($amount, $type);

            $tx['type'] = $type;
            $tx['amount'] = $amount;
            $tx['normalized_label'] = (string) ($tx['normalized_label'] ?? Normalization::normalizeLabel($label));

            $confidenceData = $this->scoreTransactionConfidence($tx, $period);
            $meta = is_array($tx['meta'] ?? null) ? $tx['meta'] : [];
            $meta['confidence'] = $confidenceData['score'];
            $meta['quality_flags'] = $confidenceData['flags'];
            if ($period !== null) {
                $meta['statement_period'] = $period;
            }
            $tx['meta'] = $meta;

            $prepared[] = $tx;
        }

        usort($prepared, function ($a, $b) {
            return strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''));
        });

        $unique = [];
        $seen = [];

        foreach ($prepared as $tx) {
            $fingerprint = implode('|', [
                (string) ($tx['date'] ?? ''),
                (string) ($tx['type'] ?? ''),
                (string) ($tx['amount'] ?? ''),
                (string) ($tx['normalized_label'] ?? ''),
            ]);

            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;

            $score = (int) (($tx['meta']['confidence'] ?? 0));
            $amountAbs = abs((float) ($tx['amount'] ?? 0));
            $mustKeepHighValue = $amountAbs >= 50000;

            if (is_int($minConfidence) && $score < $minConfidence && !$mustKeepHighValue) {
                continue;
            }

            if ($mustKeepHighValue && is_int($minConfidence) && $score < $minConfidence) {
                $meta = is_array($tx['meta'] ?? null) ? $tx['meta'] : [];
                $flags = is_array($meta['quality_flags'] ?? null) ? $meta['quality_flags'] : [];
                $flags[] = 'high_value_force_kept';
                $meta['quality_flags'] = array_values(array_unique($flags));
                $tx['meta'] = $meta;
            }

            $unique[] = $tx;
        }

        return $unique;
    }

    /**
     * Generic text parser for PDFs.
     *
     * Strategy (MVP): line-by-line regex to detect a date + an amount, remaining text is label.
     * Works best for text-based PDFs; scanned PDFs need OCR.
     *
     * @return array<int, array{date:string,label:string,normalized_label:string,amount:string,type:string,balance_after:?string,beneficiary_detected:bool,rule_flags:array}>
     */
    public function parseTransactionsFromText(string $text, ?int $defaultYear = null): array
    {
        $defaultYear = $this->resolveDefaultYearFromText($text, $defaultYear);

        // Extract the full statement period to handle year-boundary statements
        // (e.g. "du 22 décembre 2020 au 22 janvier 2021").
        $period = $this->extractStatementPeriod($text);

        if ($this->isLikelyBnp($text)) {
            $bnp = $this->parseBnpOcrText($text, $defaultYear, $period);
            if ($bnp !== []) {
                return $bnp;
            }
        }

        $genericOcr = $this->parseNoisyBankOcrText($text, $defaultYear, $period);
        if ($genericOcr !== []) {
            return $genericOcr;
        }

        $lines = preg_split('/\R/u', $text) ?: [];
        $lines = array_values(array_filter($lines, fn ($l) => trim((string) $l) !== ''));

        $out = [];

        foreach ($lines as $line) {
            $line = Normalization::cleanLabel((string) $line);
            if ($line === '') {
                continue;
            }

            if ($this->isLikelyMetadataLine($line)) {
                continue;
            }

            $date = null;
            if (preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', $line, $m)) {
                $date = $this->parseDate($m[1], $defaultYear);
            } elseif (preg_match('/\b(\d{2}[\/\-.]\d{2}[\/\-.]\d{2,4})\b/', $line, $m)) {
                $date = $this->parseDate($m[1], $defaultYear);
            } elseif (preg_match('/\b(\d{2}[\/\-.]\d{2})\b/', $line, $m)) {
                $date = $this->parseDate($m[1], $defaultYear);
            }

            if ($date === null) {
                continue;
            }

            // Amount: pick last amount-like token in the line.
            // NOTE: PCRE2 in PHP does not support \u{...} inside regex patterns; use \x{...} with /u instead.
            // Prefer amounts with cents, but also capture grouped large amounts (e.g. 180 000 or 180 000 EUR).
            preg_match_all('/-?\d{1,3}(?:[\s\x{00A0}.]\d{3})*(?:[\.,]\d{2})|-?\d{1,3}(?:[\s\x{00A0}.]\d{3})+(?:\s?(?:€|EUR))?/u', $line, $matches);
            $rawAmounts = $matches[0] ?? [];
            if ($rawAmounts === []) {
                continue;
            }

            $rawAmount = null;
            for ($i = count($rawAmounts) - 1; $i >= 0; $i--) {
                $candidate = trim((string) $rawAmounts[$i]);
                $candidate = preg_replace('/\s?(€|EUR)$/iu', '', $candidate) ?? $candidate;
                if ($this->isDateLikeAmountToken($candidate)) {
                    continue;
                }
                $rawAmount = $candidate;
                break;
            }

            if ($rawAmount === null) {
                continue;
            }

            $amount = Normalization::parseAmount($rawAmount);
            if ($amount === null) {
                continue;
            }

            if (! $this->isPlausibleTransactionAmount($amount)) {
                continue;
            }

            // Heuristics for debit: look for a leading '-' or common debit markers.
            $isDebit = str_starts_with(trim($rawAmount), '-') || preg_match('/\bDEBIT\b|\bDR\b/i', $line);
            if ($isDebit && !str_starts_with($amount, '-')) {
                $amount = '-'.$amount;
            }

            $type = $this->inferBnpType($label, $amount);
            $amount = $this->enforceAmountSignByType($amount, $type);

            // Label = line without date and the chosen amount.
            $label = trim(str_replace([$rawAmount], [''], $line));
            $label = preg_replace('/\b'.preg_quote($date, '/').'\b/', '', $label) ?? $label;
            $label = Normalization::cleanLabel($label);

            if ($label === '') {
                $label = '—';
            }

            $normalized = Normalization::normalizeLabel($label);

            $structured = $this->extractStructuredFields($label, $type);

            $out[] = [
                'date' => $date,
                'label' => $label,
                'normalized_label' => $normalized,
                'amount' => $amount,
                'type' => $type,
                'balance_after' => null,
                'beneficiary_detected' => $normalized !== '',
                'rule_flags' => [],
                ...$structured,
            ];
        }

        return $out;
    }

    /**
     * Generic OCR parser for noisy scanned statements (multi-bank fallback).
     *
     * @return array<int, array{date:string,label:string,normalized_label:string,amount:string,type:string,balance_after:?string,beneficiary_detected:bool,rule_flags:array,kind:?string,origin:?string,destination:?string,motif:?string,cheque_number:?string,meta:array}>
     */
    private function parseNoisyBankOcrText(string $text, ?int $defaultYear = null, ?array $period = null): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        $blocks = [];
        $currentBlock = [];

        foreach ($lines as $rawLine) {
            $line = Normalization::cleanLabel((string) $rawLine);
            if ($line === '') {
                continue;
            }

            if ($this->isLikelyMetadataLine($line)) {
                continue;
            }

            $upper = mb_strtoupper($line);
            $isAnchor = preg_match('/^(\d{2}[\/\-.]\d{2}(?:[\/\-.]\d{2,4})?)\b/', $line) === 1
                || preg_match('/\bDU\s*[0-3]\d[01]\d\d{2}\b/u', $upper) === 1
                || preg_match('/\bECH\/[0-3]\d[01]\d\d{2}\b/u', $upper) === 1
                || preg_match('/\b(PRLV|VIR(?:EMENT)?|VERSEMENT|SEPA|FACTURE|CARTE|CB\b|CHEQUE|CH[ÉE]QUE|RETRAIT|FRAIS|COTISATION|ECHEANCE|ÉCHÉANCE|REMBOURS)\b/u', $upper) === 1;

            if ($isAnchor) {
                if ($currentBlock !== []) {
                    $blocks[] = $currentBlock;
                }
                $currentBlock = [$line];
                continue;
            }

            if ($currentBlock !== []) {
                $currentBlock[] = $line;
            }
        }

        if ($currentBlock !== []) {
            $blocks[] = $currentBlock;
        }

        $out = [];
        $seen = [];

        foreach ($blocks as $blockLines) {
            $date = $this->extractBnpDateFromBlock($blockLines, $defaultYear, $period);
            if ($date === null) {
                continue;
            }

            $amountData = $this->extractBnpAmountFromBlock($blockLines);
            if ($amountData === null || ! $this->isPlausibleTransactionAmount($amountData['amount'])) {
                continue;
            }

            $rawLabel = implode(' ', $blockLines);
            if ($amountData['raw'] !== '') {
                $rawLabel = preg_replace('/'.preg_quote($amountData['raw'], '/').'/', '', $rawLabel, 1) ?? $rawLabel;
            }

            $label = Normalization::cleanLabel($rawLabel);
            if ($label === '' || $this->isLikelyMetadataLine($label)) {
                continue;
            }

            $type = $this->inferBnpType($label, $amountData['amount']);
            $signedAmount = $this->enforceAmountSignByType($amountData['amount'], $type);
            $normalized = Normalization::normalizeLabel($label);

            if ($normalized === '' || preg_match('/^[0-9 ]+$/', $normalized)) {
                continue;
            }

            $structured = $this->extractStructuredFields($label, $type);
            $tx = [
                'date' => $date,
                'label' => $label,
                'normalized_label' => $normalized,
                'amount' => $signedAmount,
                'type' => $type,
                'balance_after' => null,
                'beneficiary_detected' => $normalized !== '',
                'rule_flags' => [],
                ...$structured,
            ];

            $fingerprint = $tx['date'].'|'.$tx['amount'].'|'.$tx['normalized_label'];
            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $out[] = $tx;
        }

        return $out;
    }

    private function isLikelyBnp(string $text): bool
    {
        $t = mb_strtoupper($text);

        return str_contains($t, 'BNP')
            || str_contains($t, 'PARIBAS')
            || str_contains($t, 'BNP PARIBAS')
            || str_contains($t, 'RELEVE DE COMPTE')
            || str_contains($t, 'RELEVÉ DE COMPTE')
            || (str_contains($t, 'NATURE DES OPERATIONS') && str_contains($t, 'VALEUR') && (str_contains($t, 'DEBIT') || str_contains($t, 'DÉBIT')) && (str_contains($t, 'CREDIT') || str_contains($t, 'CRÉDIT')));
    }

    /**
     * BNP Paribas (scan OCR) parser.
     *
     * Typical layout contains columns: Date | Libellé | Débit | Crédit.
     * OCR may merge spaces; this parser tries to extract the last 1-2 amounts.
     *
     * @return array<int, array{date:string,label:string,normalized_label:string,amount:string,type:string,balance_after:?string,beneficiary_detected:bool,rule_flags:array}>
     */
    private function parseBnpOcrText(string $text, ?int $defaultYear = null, ?array $period = null): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        $blocks = [];
        $currentBlock = [];
        $currentContext = null;

        foreach ($lines as $rawLine) {
            $line = Normalization::cleanLabel((string) $rawLine);
            if ($line === '') {
                continue;
            }

            $upper = mb_strtoupper($line);

            if ($this->isBnpNoiseLine($upper)) {
                continue;
            }

            if ($this->isLikelyMetadataLine($line)) {
                continue;
            }

            if (preg_match('/^FACTURE\(S\)\s+CARTE\b/u', $upper)) {
                $currentContext = $line;
                continue;
            }

            if ($this->isBnpTransactionAnchor($line, $upper)) {
                if ($currentBlock !== []) {
                    $blocks[] = [
                        'context' => $currentContext,
                        'lines' => $currentBlock,
                    ];
                }

                $currentBlock = [$line];
                continue;
            }

            if ($currentBlock !== []) {
                $currentBlock[] = $line;
            }
        }

        if ($currentBlock !== []) {
            $blocks[] = [
                'context' => $currentContext,
                'lines' => $currentBlock,
            ];
        }

        $out = [];
        $seen = [];

        foreach ($blocks as $block) {
            $tx = $this->buildBnpTransactionFromBlock($block['lines'], $defaultYear, $block['context'], $period);
            if ($tx === null) {
                continue;
            }

            $fingerprint = $tx['date'].'|'.$tx['amount'].'|'.$tx['normalized_label'];
            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $out[] = $tx;
        }

        return $out;
    }

    private function isBnpNoiseLine(string $upper): bool
    {
        if (str_contains($upper, 'DATE') && (str_contains($upper, 'DEBIT') || str_contains($upper, 'DÉBIT')) && (str_contains($upper, 'CREDIT') || str_contains($upper, 'CRÉDIT'))) {
            return true;
        }

        if (str_contains($upper, 'SOLDE CREDITEUR AU') || str_contains($upper, 'SOLDE CRÉDITEUR AU') || str_contains($upper, 'SOLDE DEBITEUR AU') || str_contains($upper, 'SOLDE DÉBITEUR AU')) {
            return true;
        }

        if (str_contains($upper, 'BNP PARIBAS RELEVE DE COMPTE') || str_contains($upper, 'RELEVE DE COMPTE CHEQUES') || str_contains($upper, 'RELEVÉ DE COMPTE CHÈQUES')) {
            return true;
        }

        if (str_contains($upper, 'MONNAIE DU COMPTE') || str_contains($upper, 'RIB :') || str_contains($upper, 'IBAN :') || str_contains($upper, 'BIC :')) {
            return true;
        }

        // BNP Paribas bank identification footer (appears on every page):
        // "BNP PARIBAS SA au capital de 468 663 799 € - Siège social : 46 bd des Italiens...
        //  ...RCS Paris n° 662 042 449 - ORIAS n° 07 029 735"
        // The ORIAS number 07 029 735 = 7,029,735 must NOT be parsed as a transaction amount.
        if ((str_contains($upper, 'BNP PARIBAS SA') || str_contains($upper, 'BNP PARIBAS S.A')) &&
            (str_contains($upper, 'CAPITAL') || str_contains($upper, 'CAP') || str_contains($upper, 'ORIAS') || str_contains($upper, 'RCS') || str_contains($upper, 'SIEGE') || str_contains($upper, 'SIEG'))) {
            return true;
        }

        // "TOTAL DES OPERATIONS" summary lines.
        if (preg_match('/\bTOTAL\s+DES\s+OP[EÉ]RATIONS\b/u', $upper)) {
            return true;
        }

        if (preg_match('/^P\.\s*\d+\/\d+/u', $upper)) {
            return true;
        }

        return false;
    }

    private function isLikelyMetadataLine(string $line): bool
    {
        $upper = mb_strtoupper($line);

        if (preg_match('/\b(SOLDE\s+(CREDITEUR|CRÉDITEUR|DEBITEUR|DÉBITEUR)|TOTAL\s+(DEBIT|DÉBIT|CREDIT|CRÉDIT)|NOUVEAU\s+SOLDE|ANCIEN\s+SOLDE)\b/u', $upper)) {
            return true;
        }

        if (preg_match('/\b(IBAN|BIC|RIB|N[°O]\s*COMPTE|NUMERO\s+DE\s+COMPTE|NUMÉRO\s+DE\s+COMPTE)\b/u', $upper)) {
            return true;
        }

        if (preg_match('/\b(RELEVE\s+DE\s+COMPTE|RELEVÉ\s+DE\s+COMPTE|MONNAIE\s+DU\s+COMPTE|AGENCE|SERVICE\s+CLIENT|TEL\.?|TÉL\.?|WWW\.|MABANQUE)\b/u', $upper)) {
            return true;
        }

        if (preg_match('/\b(CHEMIN|IMPASSE|AVENUE|RUE|BOULEVARD|CODE\s+POSTAL)\b/u', $upper) && preg_match('/\b\d{5}\b/', $upper)) {
            return true;
        }

        if (preg_match('/^\d{5}\s+[A-Z\- ]+$/u', $upper)) {
            return true;
        }

        return false;
    }

    private function isPlausibleTransactionAmount(string $amount): bool
    {
        $value = abs((float) $amount);

        return $value >= 0.01 && $value <= 10000000;
    }

    private function isBnpTransactionAnchor(string $line, string $upper): bool
    {
        if (preg_match('/^\d{2}[\/\-.]\d{2}(?:[\/\-.]\d{2,4})?\b/', $line)) {
            return true;
        }

        // OCR artifact: "DDMM |" or "DDMM :" where the "." separator was lost
        // and "|" or ":" is a BNP column separator (e.g. "1002 | PRLV SEPA GROUPAMA...").
        if (preg_match('/^\d{4}\s*[|!:]\s/u', $line)) {
            return true;
        }

        if (preg_match('/^DU\s*[0-3]\d[01]\d\d{2}\b/u', $upper)) {
            return true;
        }

        if (preg_match('/\bECH\/[0-3]\d[01]\d\d{2}\b/u', $upper)) {
            return true;
        }

        return preg_match('/^(VER(?:EMENT)?|VIR(?:EMENT)?|PRLV|CHEQUE|CH[ÉE]QUE|RETRAIT|REMBOURST|ECHEANCE|ÉCHEANCE|COMMISSIONS)\b/u', $upper) === 1;
    }

    /**
     * @param array<int, string> $blockLines
     * @return array{date:string,label:string,normalized_label:string,amount:string,type:string,balance_after:?string,beneficiary_detected:bool,rule_flags:array,kind:?string,origin:?string,destination:?string,motif:?string,cheque_number:?string,meta:array}|null
     */
    private function buildBnpTransactionFromBlock(array $blockLines, ?int $defaultYear, ?string $context, ?array $period = null): ?array
    {
        if ($blockLines === []) {
            return null;
        }

        $date = $this->extractBnpDateFromBlock($blockLines, $defaultYear, $period);
        if ($date === null) {
            return null;
        }

        // If the anchor line itself is a SEPA reference line (ECH/DDMMYY :D EMETTEUR/...),
        // the real amount is NOT on the first line — it's on a continuation line.
        // Skip straight to strategy 3 (scan continuation lines for the amount).
        $anchorIsRefLine = count($blockLines) > 1 && $this->isBnpReferenceLine($blockLines[0]);

        $amountData = $this->extractBnpAmountFromBlock($blockLines, $anchorIsRefLine);
        if ($amountData === null) {
            return null;
        }

        $rawLabel = implode(' ', $blockLines);

        if (is_string($context) && $context !== '' && str_starts_with(mb_strtoupper($blockLines[0]), 'DU ')) {
            $rawLabel = $context.' '.$rawLabel;
        }

        if ($amountData['raw'] !== '') {
            $rawLabel = preg_replace('/'.preg_quote($amountData['raw'], '/').'/', '', $rawLabel, 1) ?? $rawLabel;
        }

        $label = Normalization::cleanLabel($rawLabel);
        if ($label === '') {
            return null;
        }

        $type = $this->inferBnpType($label, $amountData['amount']);
        $signedAmount = $this->enforceAmountSignByType($amountData['amount'], $type);
        $normalized = Normalization::normalizeLabel($label);
        $structured = $this->extractStructuredFields($label, $type);

        return [
            'date' => $date,
            'label' => $label,
            'normalized_label' => $normalized,
            'amount' => $signedAmount,
            'type' => $type,
            'balance_after' => null,
            'beneficiary_detected' => $normalized !== '',
            'rule_flags' => [],
            ...$structured,
        ];
    }

    /**
     * @param array<int, string> $blockLines
     */
    private function extractBnpDateFromBlock(array $blockLines, ?int $defaultYear, ?array $period = null): ?string
    {
        $anchor = $blockLines[0] ?? '';

        if (preg_match('/^(\d{2}[\/\-.]\d{2}(?:[\/\-.]\d{2,4})?)\b/', $anchor, $m)) {
            $parsed = $this->parseDateWithPeriod($m[1], $defaultYear, $period);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        // OCR artifact: "1002 | ..." where 10.02 lost its dot separator.
        if (preg_match('/^(\d{2})(\d{2})\s*[|!:]/u', $anchor, $m)) {
            $parsed = $this->parseDateWithPeriod($m[1].'.'.$m[2], $defaultYear, $period);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        if (preg_match('/\bDU\s*([0-3]\d)([01]\d)(\d{2})\b/u', mb_strtoupper($anchor), $m)) {
            $parsed = $this->parseDdmmyyDate($m[1], $m[2], $m[3], $defaultYear, $period);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        if (preg_match('/\bECH\/([0-3]\d)([01]\d)(\d{2})\b/u', mb_strtoupper($anchor), $m)) {
            $parsed = $this->parseDdmmyyDate($m[1], $m[2], $m[3], $defaultYear, $period);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        foreach ($blockLines as $line) {
            $upper = mb_strtoupper($line);

            if (preg_match('/\bDU\s*([0-3]\d)([01]\d)(\d{2})\b/u', $upper, $m)) {
                $parsed = $this->parseDdmmyyDate($m[1], $m[2], $m[3], $defaultYear, $period);
                if ($parsed !== null) {
                    return $parsed;
                }
            }

            if (preg_match('/\bECH\/([0-3]\d)([01]\d)(\d{2})\b/u', $upper, $m)) {
                $parsed = $this->parseDdmmyyDate($m[1], $m[2], $m[3], $defaultYear, $period);
                if ($parsed !== null) {
                    return $parsed;
                }
            }

            if (preg_match('/\b(\d{2}[\/\-.]\d{2}[\/\-.]\d{2,4})\b/', $line, $m)) {
                $parsed = $this->parseDateWithPeriod($m[1], $defaultYear, $period);
                if ($parsed !== null) {
                    return $parsed;
                }
            }

            if (preg_match('/\b(\d{2}[\/\-.]\d{2})\b/', $line, $m)) {
                $parsed = $this->parseDateWithPeriod($m[1], $defaultYear, $period);
                if ($parsed !== null) {
                    return $parsed;
                }
            }
        }

        return null;
    }

    /**
     * Parse a date string with period-aware year resolution.
     *
     * For French bank statements, transaction lines only show dd.mm (day and month).
     * The year must be inferred. This method tries all candidate years that fall within
     * or adjacent to the statement period, picking the best fit.
     *
     * @param array{start:string,end:string}|null $period
     */
    private function parseDateWithPeriod(string $raw, ?int $defaultYear, ?array $period): ?string
    {
        // If no period or raw date already has a full year, fall back to simple parseDate.
        if ($period === null || preg_match('/\d{4}/', $raw)) {
            return $this->parseDate($raw, $defaultYear);
        }

        // Extract day and month from "dd.mm" or "dd/mm" or "dd-mm".
        if (!preg_match('/^(\d{2})[\/\-.](\d{2})$/', trim($raw), $m)) {
            return $this->parseDate($raw, $defaultYear);
        }

        $day   = (int) $m[1];
        $month = (int) $m[2];

        if ($day < 1 || $day > 31 || $month < 1 || $month > 12) {
            return null;
        }

        $startYear = (int) substr((string) $period['start'], 0, 4);
        $endYear   = (int) substr((string) $period['end'], 0, 4);
        $startDate = $period['start'];
        $endDate   = $period['end'];

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
    }

    /**
     * @param array<int, string> $blockLines
     * @return array{amount:string,raw:string}|null
     */
    private function extractBnpAmountFromBlock(array $blockLines, bool $skipFirstLine = false): ?array
    {
        $firstLine = $blockLines[0] ?? '';

        // Strategy 1: BNP canonical format is "DD.MM  LABEL  DD.MM  AMOUNT" on the first line.
        // This is ALWAYS tried first, even for ECH/EMETTEUR lines, because the amount column
        // is still present after the valeur date on the same anchor line (visible in the table).
        // This strategy is safe: it requires TWO date patterns, so reference-only lines won't match.
        $afterSecondDate = $this->extractAmountAfterSecondDate($firstLine);
        if ($afterSecondDate !== null) {
            return $afterSecondDate;
        }

        if (!$skipFirstLine) {
            // Strategy 2: Take the RIGHTMOST plausible amount on the first line only
            // (avoids absorbing numbers from continuation lines that contain SEPA references).
            // Skipped when the anchor line is itself a reference line (ECH/EMETTEUR lines
            // that may not have a valeur date — amount is then on a continuation line).
            $firstLineCandidates = $this->extractAmountCandidatesFromLine($firstLine);
            if ($firstLineCandidates !== []) {
                // Rightmost = highest offset; break ties by preferring smaller values
                // (reference numbers tend to be large; real transaction amounts tend to be smaller).
                usort($firstLineCandidates, fn (array $a, array $b) => ($b['offset'] <=> $a['offset']) ?: ($a['abs'] <=> $b['abs']));

                return [
                    'amount' => (string) $firstLineCandidates[0]['amount'],
                    'raw'    => (string) $firstLineCandidates[0]['raw'],
                ];
            }
        }

        // Strategy 3: Scan continuation lines, skipping any that look like SEPA/IBAN reference lines.
        // For continuation lines, also try "amount after second date" first.
        $allCandidates = [];
        foreach ($blockLines as $i => $line) {
            if ($i === 0) {
                continue; // first line already tried above (or intentionally skipped)
            }
            if ($this->isBnpReferenceLine($line)) {
                continue;
            }
            $afterDate = $this->extractAmountAfterSecondDate($line);
            if ($afterDate !== null) {
                return $afterDate;
            }
            foreach ($this->extractAmountCandidatesFromLine($line) as $candidate) {
                $allCandidates[] = $candidate;
            }
        }

        if ($allCandidates !== []) {
            usort($allCandidates, fn (array $a, array $b) => ($b['offset'] <=> $a['offset']) ?: ($a['abs'] <=> $b['abs']));

            return [
                'amount' => (string) $allCandidates[0]['amount'],
                'raw'    => (string) $allCandidates[0]['raw'],
            ];
        }

        return null;
    }

    /**
     * BNP format: "DD.MM  LABEL  DD.MM  AMOUNT"
     * Finds the amount that comes right after the second date-like token on the line.
     *
     * @return array{amount:string,raw:string}|null
     */
    private function extractAmountAfterSecondDate(string $line): ?array
    {
        // Find all date-like positions (dd.mm or dd/mm) in the line.
        preg_match_all('/\b\d{2}[\/\-.]\d{2}\b/', $line, $dateMatches, PREG_OFFSET_CAPTURE);
        $dates = $dateMatches[0] ?? [];

        if (count($dates) < 2) {
            return null;
        }

        // Take the position right after the LAST date occurrence.
        $lastDate   = $dates[count($dates) - 1];
        $afterOffset = (int) $lastDate[1] + mb_strlen((string) $lastDate[0]);
        $remainder   = mb_substr($line, $afterOffset);

        $candidates = $this->extractAmountCandidatesFromLine($remainder);
        if ($candidates === []) {
            return null;
        }

        // Take the leftmost (first) amount after the date.
        usort($candidates, fn (array $a, array $b) => $a['offset'] <=> $b['offset']);

        return [
            'amount' => (string) $candidates[0]['amount'],
            'raw'    => (string) $candidates[0]['raw'],
        ];
    }

    /**
     * Returns true if the line looks like a SEPA/IBAN reference line rather than a transaction line.
     * Such lines contain mandate codes, IBAN fragments, or long alphanumeric reference strings
     * that would produce false amount candidates.
     */
    private function isBnpReferenceLine(string $line): bool
    {
        $upper = mb_strtoupper($line);

        // Lines starting with known reference prefixes.
        if (preg_match('/^\s*(MDT|REF|EMETTEUR|ÉMETTEUR|MANDAT|BIC|IBAN|RIB|LIB)\b/u', $upper)) {
            return true;
        }

        // Lines containing SEPA mandate or reference patterns.
        if (preg_match('/\b(MDT\/|REF\/|EMETTEUR\/|ÉMETTEUR\/|LIB\/)\+{0,2}/u', $upper)) {
            return true;
        }

        // Lines that contain IBAN-like sequences (2 letters + 2 digits + many chars).
        if (preg_match('/\b[A-Z]{2}\d{2}[A-Z0-9]{8,}\b/u', $upper)) {
            return true;
        }

        // Lines with very long unbroken alphanumeric tokens (reference IDs).
        if (preg_match('/[A-Z0-9]{15,}/u', $upper)) {
            return true;
        }

        return false;
    }

    /**
     * Parse a DDMMYY date (BNP card transaction format: "DU DDMMYY").
     * Prepends "20" to form a 4-digit year, but validates against period/defaultYear context.
     * If the computed year is implausibly far in the future (OCR corruption e.g. "5"→"8"),
     * falls back to period-aware resolution from just DD.MM.
     */
    private function parseDdmmyyDate(string $dd, string $mm, string $yy, ?int $defaultYear, ?array $period): ?string
    {
        $fullYear = 2000 + (int) $yy;
        $currentYear = (int) date('Y');

        // Sanity: years far in the future are OCR corruptions (e.g. DU 190428 → 2028 when real = 2025).
        // Use a 2-year forward tolerance from current date.
        if ($fullYear > $currentYear + 1) {
            // Fall back to DD.MM with period-based year resolution.
            return $this->parseDateWithPeriod($dd.'.'.$mm, $defaultYear, $period);
        }

        return $this->parseDate(sprintf('%s/%s/%d', $dd, $mm, $fullYear));
    }

    /**
     * @return array{amount:string,raw:string}|null
     */
    private function extractLastAmountFromLine(string $line): ?array
    {
        $candidates = $this->extractAmountCandidatesFromLine($line);
        if ($candidates === []) {
            return null;
        }

        $selected = $candidates[count($candidates) - 1];

        return [
            'amount' => (string) $selected['amount'],
            'raw' => (string) $selected['raw'],
        ];
    }

    /**
     * @return array<int, array{amount:string,raw:string,abs:float,offset:int}>
     */
    private function extractAmountCandidatesFromLine(string $line): array
    {
        // Amounts on BNP statements ALWAYS have 2 decimal places (e.g. 49,89 / 1 000,00 / 1.000,00).
        // We require a decimal separator + 2 digits to avoid matching bare reference numbers.
        // The (?<!\d) lookbehind prevents matching a digit group that is embedded inside a larger
        // integer (e.g. "2904 102.73" must not produce "904 102.73" = 904,102.73 — the real
        // amount is the rightmost "102.73" and "904" leaks from the valeur-date column "2904").
        preg_match_all('/(?<!\d)-?(?:\d{1,3}(?:[\s\x{00A0}.]\d{3})+|\d+)[,.]\d{2}(?!\d)/u', $line, $matches, PREG_OFFSET_CAPTURE);
        $tokens = $matches[0] ?? [];
        if ($tokens === []) {
            return [];
        }

        $out = [];
        foreach ($tokens as $tokenData) {
            $rawToken = trim((string) ($tokenData[0] ?? ''));
            $offset = (int) ($tokenData[1] ?? 0);
            $rawToken = preg_replace('/\s?(€|EUR)$/iu', '', $rawToken) ?? $rawToken;

            if ($rawToken === '' || $this->isDateLikeAmountToken($rawToken)) {
                continue;
            }

            $normalizedToken = $this->normalizeOcrAmountToken($rawToken);
            $parsed = Normalization::parseAmount($normalizedToken);

            if ($parsed === null || ! $this->isPlausibleTransactionAmount($parsed)) {
                continue;
            }

            $out[] = [
                'amount' => $parsed,
                'raw' => $rawToken,
                'abs' => abs((float) $parsed),
                'offset' => $offset,
            ];
        }

        return $out;
    }

    private function isDateLikeAmountToken(string $token): bool
    {
        $clean = trim($token);
        if (!preg_match('/^(\d{2})[\/\-.](\d{2})$/', $clean, $m)) {
            return false;
        }

        $day = (int) $m[1];
        $month = (int) $m[2];

        return $day >= 1 && $day <= 31 && $month >= 1 && $month <= 12;
    }

    private function normalizeOcrAmountToken(string $raw): string
    {
        return strtr($raw, [
            'O' => '0',
            'o' => '0',
            'D' => '0',
            'l' => '1',
            'I' => '1',
            '|' => '1',
        ]);
    }

    private function inferBnpType(string $label, string $amount): string
    {
        $upper = mb_strtoupper($label);

        if (preg_match('/\b(VER\s+SEPA\s+RECU|VIR\s+SEPA\s+RECU|RECU\b|REÇU\b|REMBOURST)\b/u', $upper)) {
            return 'credit';
        }

        if (preg_match('/\b(PRLV|FACTURE|CHEQUE|CH[ÉE]QUE|RETRAIT|ECHEANCE|ÉCHÉANCE|COMMISSIONS)\b/u', $upper)) {
            return 'debit';
        }

        return ((float) $amount) >= 0 ? 'credit' : 'debit';
    }

    /**
     * @return array{start:string,end:string}|null
     */
    private function extractStatementPeriod(string $text): ?array
    {
        $normalized = mb_strtolower($text);
        $normalized = str_replace(["\n", "\r"], ' ', $normalized);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        if (preg_match('/du\s+(\d{2}[\/\-.]\d{2}[\/\-.]\d{2,4})\s+au\s+(\d{2}[\/\-.]\d{2}[\/\-.]\d{2,4})/u', $normalized, $m)) {
            $start = $this->parseDate($m[1]);
            $end = $this->parseDate($m[2]);
            if ($start && $end) {
                return ['start' => $start, 'end' => $end];
            }
        }

        // Month names may be corrupted by OCR: "janv'er", "avr'l", "ma'", "ju'n", etc.
        // Allow apostrophes (straight ' and curly \x{2019}) and hyphens inside the month token.
        if (preg_match('/du\s+(\d{1,2})\s+([\pL\'\x{2019}\-]+)\s+(20\d{2})\s+au\s+(\d{1,2})\s+([\pL\'\x{2019}\-]+)\s+(20\d{2})/u', $normalized, $m)) {
            $start = $this->parseFrenchMonthDate((int) $m[1], (string) $m[2], (int) $m[3]);
            $end = $this->parseFrenchMonthDate((int) $m[4], (string) $m[5], (int) $m[6]);
            if ($start && $end) {
                return ['start' => $start, 'end' => $end];
            }
        }

        return null;
    }

    private function parseFrenchMonthDate(int $day, string $monthName, int $year): ?string
    {
        // OCR corrupts month names: "janv'er", "avr'l", "ma'", "ju'n", "d'cembre", etc.
        // Strategy: strip all non-alpha chars, normalise accents, then find the closest
        // canonical month name using character prefix overlap + Levenshtein distance.

        // Step 1: strip everything except letters, lowercase, normalise accents.
        $clean = mb_strtolower($monthName);
        $clean = strtr($clean, [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'û' => 'u', 'ù' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ]);
        $clean = preg_replace('/[^a-z]/u', '', $clean) ?? $clean;

        // Step 2: canonical list (ASCII, lowercase) with their month numbers.
        $canonical = [
            'janvier'   => 1,
            'fevrier'   => 2,
            'mars'      => 3,
            'avril'     => 4,
            'mai'       => 5,
            'juin'      => 6,
            'juillet'   => 7,
            'aout'      => 8,
            'septembre' => 9,
            'octobre'   => 10,
            'novembre'  => 11,
            'decembre'  => 12,
        ];

        // Step 3: exact match first.
        if (isset($canonical[$clean])) {
            $month = $canonical[$clean];
        } else {
            // Step 4: Levenshtein-based fuzzy match.
            // Short months (≤ 4 chars: mai, mars, juin, aout) allow max 1 edit.
            // Longer months allow max 2 edits.
            // Tiebreak: longer shared prefix wins.
            $best      = null;
            $bestScore = PHP_INT_MAX;

            foreach ($canonical as $name => $num) {
                $dist       = levenshtein($clean, $name);
                $maxAllowed = strlen($name) <= 4 ? 1 : 2;

                if ($dist > $maxAllowed) {
                    continue;
                }

                // Shared prefix length (tiebreak: more = better).
                $prefix = 0;
                $minLen = min(strlen($clean), strlen($name));
                while ($prefix < $minLen && $clean[$prefix] === $name[$prefix]) {
                    $prefix++;
                }

                // Lower dist wins; more shared prefix breaks ties.
                $score = $dist * 100 - $prefix;
                if ($score < $bestScore) {
                    $bestScore = $score;
                    $best      = $num;
                }
            }

            $month = $best;
        }

        if ($month === null || $day < 1 || $day > 31) {
            return null;
        }

        try {
            return Carbon::createFromDate($year, $month, $day)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string,mixed> $tx
     * @param array{start:string,end:string}|null $period
     * @return array{score:int,flags:array<int,string>}
     */
    private function scoreTransactionConfidence(array $tx, ?array $period): array
    {
        $score = 100;
        $flags = [];

        $label = (string) ($tx['label'] ?? '');
        $type = (string) ($tx['type'] ?? '');
        $amount = abs((float) ($tx['amount'] ?? 0));
        $date = (string) ($tx['date'] ?? '');

        if (mb_strlen(trim($label)) < 8) {
            $score -= 20;
            $flags[] = 'label_too_short';
        }

        if ($amount < 0.01) {
            $score -= 40;
            $flags[] = 'amount_too_small';
        }

        $upper = mb_strtoupper($label);
        if ($type === 'debit' && preg_match('/\b(RECU|REÇU|REMBOURST|VERSEMENT\s+RECU|VIR\s+SEPA\s+RECU)\b/u', $upper)) {
            $score -= 18;
            $flags[] = 'type_mismatch_hint_credit';
        }

        if ($type === 'credit' && preg_match('/\b(PRLV|FACTURE|CHEQUE|CH[ÉE]QUE|RETRAIT|FRAIS|COMMISSIONS)\b/u', $upper)) {
            $score -= 18;
            $flags[] = 'type_mismatch_hint_debit';
        }

        if ($period !== null && $date !== '') {
            try {
                $txDate = Carbon::parse($date);
                $start = Carbon::parse($period['start'])->subDays(10);
                $end = Carbon::parse($period['end'])->addDays(10);

                if ($txDate->lt($start) || $txDate->gt($end)) {
                    $score -= 30;
                    $flags[] = 'outside_statement_period';
                }
            } catch (\Throwable) {
                $score -= 10;
                $flags[] = 'date_parse_uncertain';
            }
        }

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'flags' => $flags,
        ];
    }

    private function enforceAmountSignByType(string $amount, string $type): string
    {
        $value = abs((float) $amount);
        $signed = $type === 'debit' ? -$value : $value;

        return number_format($signed, 2, '.', '');
    }

    public function verifyBalanceCoherence(array $transactions): array
    {
        // Adds rule flag 'balance_incoherent' where balance_after doesn't match previous balance.
        $previousBalance = null;
        foreach ($transactions as $i => $tx) {
            if (!isset($tx['balance_after']) || $tx['balance_after'] === null) {
                continue;
            }

            $balanceAfter = (float) $tx['balance_after'];
            $amount = (float) $tx['amount'];

            if ($previousBalance !== null) {
                $expected = $previousBalance + $amount;
                if (abs($expected - $balanceAfter) > 0.02) {
                    $transactions[$i]['rule_flags']['balance_incoherent'] = true;
                }
            }

            $previousBalance = $balanceAfter;
        }

        return $transactions;
    }

    private function parseDate(string $raw, ?int $defaultYear = null): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // dd/mm or dd-mm or dd.mm (no year)
        if ($defaultYear !== null && preg_match('/^(\d{2})[\/\-.](\d{2})$/', $raw, $m)) {
            $raw = sprintf('%02d/%02d/%04d', (int) $m[1], (int) $m[2], $defaultYear);
        }

        // Normalize separators for common formats.
        $raw = str_replace('.', '/', $raw);

        // NEVER try m/d/Y (American format) — all French bank statements use d/m/Y.
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd/m/y', 'd-m-y'] as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, $raw);
                if ($dt === false) {
                    continue;
                }
                // Handle 2-digit years by pushing into 2000s.
                if ($dt->year < 1970) {
                    $dt = $dt->addYears(2000 - $dt->year);
                }
                return $dt->toDateString();
            } catch (\Throwable) {
                // continue
            }
        }

        return null;
    }

    private function resolveDefaultYearFromText(string $text, ?int $defaultYear): ?int
    {
        $period = $this->extractStatementPeriod($text);
        if ($period !== null) {
            // Use the END year of the statement period as default year.
            // This handles statements that span a year boundary correctly
            // because the END date is typically the most recent month.
            $periodYear = (int) substr((string) $period['end'], 0, 4);
            if ($periodYear >= 2000 && $periodYear <= (int) now()->format('Y')) {
                // Always trust the OCR-extracted period year over a filename timestamp.
                return $periodYear;
            }
        }

        // If no period found, try any 4-digit year in the first 1000 chars (header area).
        $header = mb_substr($text, 0, 1000);
        if (preg_match('/\b(20[012]\d)\b/u', $header, $m)) {
            $year = (int) $m[1];
            if ($year >= 2000 && $year <= (int) now()->format('Y')) {
                return $year;
            }
        }

        return $defaultYear;
    }

    /**
     * Detect high-value amount candidates seen in OCR text on transaction-like lines.
     * Used as a guardrail: if OCR clearly contains large movements but parser misses them,
     * import should be flagged for manual review.
     *
     * @return array<int,float>
     */
    public function detectHighValueAmountsFromText(string $text, float $threshold = 20000.0): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        $amounts = [];

        foreach ($lines as $rawLine) {
            $line = Normalization::cleanLabel((string) $rawLine);
            if ($line === '' || $this->isLikelyMetadataLine($line) || $this->isBnpNoiseLine(mb_strtoupper($line))) {
                continue;
            }

            $upper = mb_strtoupper($line);

            // Skip lines that look like SEPA reference / loan reference lines
            // (they contain large embedded numbers that are NOT transaction amounts).
            if ($this->isBnpReferenceLine($line)) {
                continue;
            }

            $hasDateAnchor = preg_match('/^\s*\d{2}[\/\-.]\d{2}(?:[\/\-.]\d{2,4})?\b/u', $line) === 1;
            $hasTxnHint = preg_match('/\b(VER(?:EMENT)?|VIR(?:EMENT)?|PRLV|SEPA|CHEQUE|CH[ÉE]QUE|RETRAIT|REMBOURST|FACTURE|CARTE|ECHEANCE|ÉCHÉANCE)\b/u', $upper) === 1;

            if (! $hasDateAnchor && ! $hasTxnHint) {
                continue;
            }

            if ($hasDateAnchor) {
                // Use the same strategy as the parser: find the amount that appears
                // AFTER the second date occurrence on the line.
                // This avoids picking up loan contract numbers and reference IDs
                // embedded in the line text (e.g. "PRET 00702 61072635").
                $amountData = $this->extractAmountAfterSecondDate($line);
                if ($amountData !== null) {
                    $value = abs((float) $amountData['amount']);
                    if ($value >= $threshold) {
                        $amounts[] = round($value, 2);
                    }
                }
            } else {
                // No date anchor (e.g. a continuation line with a keyword) –
                // fall back to full-line scan but keep only plausible amounts.
                foreach ($this->extractAmountCandidatesFromLine($line) as $candidate) {
                    $value = abs((float) ($candidate['amount'] ?? 0));
                    if ($value >= $threshold) {
                        $amounts[] = round($value, 2);
                    }
                }
            }
        }

        $amounts = array_values(array_unique($amounts));
        rsort($amounts, SORT_NUMERIC);

        return $amounts;
    }

    /**
     * Extract structured fields from a transaction label (heuristics).
     *
     * @return array{kind:?string,origin:?string,destination:?string,motif:?string,cheque_number:?string,meta:array}
     */
    private function extractStructuredFields(string $label, string $type): array
    {
        $norm = Normalization::normalizeLabel($label);

        $kind = null;
        $origin = null;
        $destination = null;
        $motif = $label;
        $chequeNumber = null;

        if (preg_match('/\b(CHEQUE|CH[EÉ]QUE)\b/', $norm)) {
            $kind = 'cheque';

            if (preg_match('/\bN\s*[°O]?\s*(\d{4,10})\b/', $norm, $m) || preg_match('/\bCHEQUE\s+(\d{4,10})\b/', $norm, $m)) {
                $chequeNumber = $m[1];
            }
        } elseif (preg_match('/\b(RETRAIT|DAB|ATM)\b/', $norm)) {
            $kind = 'cash_withdrawal';
        } elseif (preg_match('/\b(VIREMENT|VIR\b|SEPA)\b/', $norm)) {
            $kind = 'transfer';

            // Very simple origin/destination heuristics.
            if ($type === 'credit' && preg_match('/\bDE\s+(.+)$/u', $label, $m)) {
                $origin = Normalization::cleanLabel($m[1]);
            }
            if ($type === 'debit' && preg_match('/\bVERS\s+(.+)$/u', $label, $m)) {
                $destination = Normalization::cleanLabel($m[1]);
            }
        } else {
            $kind = 'other';
        }

        if ($kind === 'transfer') {
            if ($type === 'credit' && $origin === null) {
                $origin = $label;
            }
            if ($type === 'debit' && $destination === null) {
                $destination = $label;
            }
        }

        return [
            'kind' => $kind,
            'origin' => $origin,
            'destination' => $destination,
            'motif' => $motif,
            'cheque_number' => $chequeNumber,
            'meta' => [],
        ];
    }
}
