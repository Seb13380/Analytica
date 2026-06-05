<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

foreach (\App\Models\Statement::orderBy('id')->get() as $stmt) {
    $text = $stmt->extracted_text ?? '';
    echo "── Statement #{$stmt->id} (compte #{$stmt->bank_account_id}) ──\n";
    // Chercher les lignes qui ressemblent à un intitulé de relevé
    $lines = array_slice(explode("\n", mb_strtoupper($text)), 0, 50);
    foreach ($lines as $i => $l) {
        $l = trim($l);
        if (preg_match('/\bRELEVE\b|\bCOMPTE\b|\bLIVRET\b|\bEPARGNE\b|\bCHEQUES?\b|\bCOURANT\b|\bEPARGNE\b|\bDURABLE\b/u', $l) && strlen($l) > 5) {
            echo "  L{$i}: $l\n";
        }
    }
    echo "\n";
}
