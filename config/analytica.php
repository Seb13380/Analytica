<?php

return [
    'storage_disk' => env('ANALYTICA_STORAGE_DISK', 's3'),

    // Derive a stable 32-byte key from APP_KEY if not provided.
    'encryption_key' => env('ANALYTICA_FILE_ENCRYPTION_KEY'),

    'case_expiration_months' => (int) env('ANALYTICA_CASE_EXPIRATION_MONTHS', 24),

    'encryption' => [
        'algorithm' => 'aes-256-gcm',
        'nonce_bytes' => 12,
        'tag_bytes' => 16,
    ],

    'ocr_url' => env('ANALYTICA_OCR_URL'),

    'import' => [
        'mode' => env('ANALYTICA_IMPORT_MODE', 'expert'),
        'strict' => (bool) env('ANALYTICA_IMPORT_STRICT', true),
        'min_confidence' => (int) env('ANALYTICA_IMPORT_MIN_CONFIDENCE', 45),
        'high_value_threshold' => (float) env('ANALYTICA_IMPORT_HIGH_VALUE_THRESHOLD', 20000),
        'auto_analyze' => (bool) env('ANALYTICA_IMPORT_AUTO_ANALYZE', false),
        // Heuristiques de déduplication DB (same-day + high-value fuzzy).
        // Activé : nécessaire pour les relevés BNP multi-PDFs dont les périodes se chevauchent
        // (stmt10 et stmt11 couvrent tous deux 2021-2022). La comparaison utilise date exacte
        // + montant exact + similarité de libellé pour éviter les faux positifs.
        'allow_db_dedup_heuristics' => (bool) env('ANALYTICA_IMPORT_ALLOW_DB_DEDUP_HEURISTICS', true),
        // Ratio minimum transactions nouvelles / existantes avant d'autoriser le cleanup range.
        // Protège contre les re-imports partiels (OCR timeout, retry attempt 2) qui effaceraient
        // plus de données qu'ils n'en remplacent.
        'cleanup_min_coverage_ratio' => (float) env('ANALYTICA_IMPORT_CLEANUP_MIN_COVERAGE_RATIO', 0.80),
        // Seuil de confiance OCR : si le texte OCR live produit moins de X% des transactions
        // extraites du texte OCR mis en cache (extracted_text déjà stocké), utiliser le cache.
        'ocr_cache_fallback_ratio' => (float) env('ANALYTICA_IMPORT_OCR_CACHE_FALLBACK_RATIO', 0.50),
    ],

    'ai' => [
        'enabled' => (bool) env('ANALYTICA_AI_ENABLED', false),
        'auto_after_case_analysis' => (bool) env('ANALYTICA_AI_AUTO_AFTER_CASE_ANALYSIS', true),
        'openai_base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'openai_model' => env('ANALYTICA_AI_MODEL', 'gpt-4.1-mini'),
        'max_transactions' => (int) env('ANALYTICA_AI_MAX_TRANSACTIONS', 300),
        'timeout_seconds' => (int) env('ANALYTICA_AI_TIMEOUT', 45),
    ],

    'beneficiary_alias_clusters' => [
        [
            // Me MILLIET Géraldine — notaire (vente immobilière, plus-values)
            // OCR variants: "MILLIET ET GERALDINE" (ET parasite), "M:LL:ET", "MILL:ET"
            // Placé EN PREMIER pour être prioritaire sur GIORDANO_NOVAK quand le motif
            // contient "GIORDANO" (ex: "prix de vente à Mr GIORDANO").
            'key' => 'NOTAIRE_MILLIET_GERALDINE',
            'label' => 'Me MILLIET Géraldine (notaire)',
            'tokens' => ['MILLIET'],
            'min_match' => 1,
            'query' => 'MILLIET GERALDINE notaire',
        ],
        [
            'key' => 'PERSONNE_GIORDANO_NOVAK',
            'label' => 'Groupe GIORDANO / NOVAK (à ventiler)',
            'tokens' => array_values(array_filter(array_map('trim', explode(',', (string) env('ANALYTICA_BENEFICIARY_ALIAS_GIORDANO_NOVAK', 'GIORDANO,NOVAK,LILIANE,ANTHONY,EMILIE'))))),
            'min_match' => max(1, (int) env('ANALYTICA_BENEFICIARY_ALIAS_GIORDANO_NOVAK_MIN_MATCH', 1)),
            'query' => (string) env('ANALYTICA_BENEFICIARY_ALIAS_GIORDANO_NOVAK_QUERY', 'GIORDANO NOVAK Liliane Anthony Emilie'),
        ],
        [
            'key' => 'ASSURANCE_MATMUT',
            'label' => 'Assurance / MATMUT',
            // Ne faire correspondre MATMUT QUE si le mot "MATMUT" apparaît littéralement.
            // Les tokens génériques ASSURANCE/SINISTRE/FEU étaient bien trop larges et
            // agrégaient sous MATMUT toute transaction contenant simplement le mot "assurance".
            'tokens' => array_values(array_filter(array_map('trim', explode(',', (string) env('ANALYTICA_BENEFICIARY_ALIAS_ASSURANCE_MATMUT', 'MATMUT'))))),
            'min_match' => max(1, (int) env('ANALYTICA_BENEFICIARY_ALIAS_ASSURANCE_MATMUT_MIN_MATCH', 1)),
            'query' => (string) env('ANALYTICA_BENEFICIARY_ALIAS_ASSURANCE_MATMUT_QUERY', 'MATMUT'),
        ],
    ],
];
