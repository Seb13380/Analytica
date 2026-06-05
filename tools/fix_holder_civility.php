<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cv = 'M(?:ONS(?:IEUR)?|ME|\.)?\.?|MME\.?|MADAME|MR\.?';

foreach (\App\Models\BankAccount::orderBy('id')->get() as $account) {
    $stmt = \App\Models\Statement::where('bank_account_id', $account->id)->first();
    if (!$stmt) { echo "Compte #{$account->id}: pas de relevé\n"; continue; }

    $text  = $stmt->extracted_text ?? '';
    $upper = mb_strtoupper($text);
    $lines = array_slice(explode("\n", $upper), 0, 80);

    $holder = null;

    // Format BNP Succession → garde juste le nom (pas de civilité)
    if (preg_match('/Succession\s+de\s*:\s*(?:M(?:ONS(?:IEUR)?|ME|\.)?\.?|MME\.?|MADAME|MR\.?)\s+([A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ\- ]{1,50}?)(?:\s+n[eé]\b.*)?$/iu', $text, $m)) {
        $name = preg_replace('/\s+né\b.*/iu', '', trim($m[1]));
        $holder = mb_convert_case(preg_replace('/\s+/', ' ', trim($name)), MB_CASE_TITLE, 'UTF-8');
    }

    if (!$holder) {
        $lines = explode("\n", $upper);  // scan tout le texte
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) < 5 || strlen($line) > 80) continue;

            // "M OU MME CHRISTIAN GIORDANO" → "M Ou Mme Christian Giordano"
            // Tolère les artefacts OCR dans le nom (G:ORDANO, CHR:STIAN, etc.)
            if (preg_match('/^((?:' . $cv . ')\s+(?:OU|ET)\s+(?:' . $cv . '))\s+([A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ\:\- ]{2,50})$/u', $line, $m)) {
                // Normaliser la civilité : M ou Mme / M et Mme
                $rawCiv   = preg_replace('/\s+/', ' ', trim($m[1]));
                $civility = preg_replace_callback('/\b(OU|ET|MME|MR|M)\b/u', function($c) {
                    return match(strtoupper($c[1])) {
                        'OU','ET' => strtolower($c[1]),
                        'MME'     => 'Mme',
                        'MR'      => 'M.',
                        default   => 'M',
                    };
                }, mb_strtoupper($rawCiv)) ?? $rawCiv;
                $rawName  = preg_replace('/[:\|]/', 'I', trim($m[2])) ?? trim($m[2]);
                $name     = mb_convert_case(preg_replace('/\s+/', ' ', $rawName), MB_CASE_TITLE, 'UTF-8');
                $holder   = $civility . ' ' . $name;
                break;
            }

            // "M CHRISTIAN GIORDANO" → "M Christian Giordano"
            if (preg_match('/^((?:' . $cv . '))\s+([A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][A-Z:]{1,20})\s+([A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][A-Z:\-]{1,30})$/u', $line, $m)) {
                $rawCiv   = strtoupper(trim($m[1]));
                $civility = match($rawCiv) { 'MME','MME.' => 'Mme', 'MADAME' => 'Mme', 'MR','MR.' => 'M.', default => 'M' };
                $fn = mb_convert_case(preg_replace('/[:\|]/', 'I', $m[2]), MB_CASE_TITLE, 'UTF-8');
                $ln = mb_convert_case(preg_replace('/[:\|]/', 'I', $m[3]), MB_CASE_TITLE, 'UTF-8');
                $holder   = $civility . ' ' . $fn . ' ' . $ln;
                break;
            }
        }
    }

    if ($holder) {
        $account->forceFill(['account_holder' => $holder])->save();
        echo "Compte #{$account->id}: \"{$holder}\"\n";
    } else {
        echo "Compte #{$account->id}: non trouvé (actuel: {$account->account_holder})\n";
    }
}
echo "\nTerminé.\n";
