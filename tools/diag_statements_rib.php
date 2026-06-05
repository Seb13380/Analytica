<?php
/**
 * Diagnostic : extraire le RIB et le type de CHAQUE relevé individuellement.
 */
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

foreach (\App\Models\Statement::orderBy('id')->get() as $stmt) {
    $text  = $stmt->extracted_text ?? '';
    $upper = mb_strtoupper($text);
    $lines = array_slice(explode("\n", $upper), 0, 15);

    // RIB en format français : 5+5+11+2 chiffres
    $rib = null;
    if (preg_match('/\bRIB\s*[_:\|]?\s*([0-9]{5}\s+[0-9]{5}\s+[0-9]{11}\s+[0-9]{2})/u', $upper, $m)) {
        $rib = trim($m[1]);
    } elseif (preg_match('/\bRIB\s*[_:\|]?\s*([0-9]{5}\s+[0-9]{5}\s+[0-9]{8,13}\s+[0-9]{2})/u', $upper, $m)) {
        $rib = trim($m[1]);
    }

    $isJoint   = (bool) preg_match('/\bM\.?\s+(?:OU|ET)\s+MME\.?\b/u', $upper);
    $isSavings = (bool) preg_match('/\bLIVRET\b|\bLIVRET DEV\b|\bLDD\b/u', implode("\n", $lines));

    echo "── Statement #{$stmt->id} (compte #{$stmt->bank_account_id}) ──\n";
    echo "  RIB: " . ($rib ?? '(non trouvé)') . "\n";
    echo "  Joint: " . ($isJoint ? 'OUI' : 'non') . "  |  Livret: " . ($isSavings ? 'OUI' : 'non') . "\n";
    echo "  Premières lignes:\n";
    foreach ($lines as $l) {
        $l = trim($l);
        if ($l !== '') echo "    > $l\n";
    }
    echo "\n";
}
