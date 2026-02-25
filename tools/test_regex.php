<?php
// Test the (?<!\d) lookbehind fix
$lines = [
    "29.04 | ViR SEPA RECU /DE ANGDM /MOT:F DUNE: 2904 102.73",
    "29.04 | ViR SEPA RECU /DE ANGDM /MOT:F DUNE: 29 04 102.73",
    "18 000.00",   // legit large amount
    "1 234.56",    // legit normal amount
    "904 102.73",  // standalone (no leading digit) - should still match
    "6 283.12",    // legit total
    "10 000.00",   // legit 10k
    "135 512.50",  // legit 135k
];

echo "=== NEW regex (with (?<!\\d) lookbehind) ===\n";
foreach($lines as $line) {
    preg_match_all('/(?<!\d)-?(?:\d{1,3}(?:[\s\x{00A0}.]\d{3})+|\d+)[,.]\d{2}(?!\d)/u', $line, $m);
    echo "  INPUT: $line\n  MATCHES: ".implode(', ', $m[0])."\n\n";
}
