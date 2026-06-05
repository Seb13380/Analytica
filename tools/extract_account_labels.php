<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$knownLabels = [
    'RELEVE DE COMPTE CHEQUES'                         => 'Relevé de compte chèques',
    'RELEVE DE COMPTE CHEQUE'                          => 'Relevé de compte chèques',
    'RELEVE COMPTE CHEQUES'                            => 'Relevé de compte chèques',
    'RELEVE LIVRET DEV DURABLE ET SOLIDAIRE'           => 'Relevé Livret Développement Durable et Solidaire',
    'RELEVE LIVRET DEVELOPPEMENT DURABLE ET SOLIDAIRE' => 'Relevé Livret Développement Durable et Solidaire',
    'RELEVE LIVRET DEV. DURABLE ET SOLIDAIRE'          => 'Relevé Livret Développement Durable et Solidaire',
    'RELEVE LIVRET EPARGNE'                            => 'Relevé Livret Épargne',
    'RELEVE LIVRET A'                                  => 'Relevé Livret A',
    'RELEVE COMPTE COURANT'                            => 'Relevé de compte courant',
    'RELEVE DE COMPTE COURANT'                         => 'Relevé de compte courant',
];

foreach (\App\Models\BankAccount::orderBy('id')->get() as $account) {
    $stmt = \App\Models\Statement::where('bank_account_id', $account->id)->first();
    if (!$stmt) { echo "Compte #{$account->id}: pas de relevé\n"; continue; }

    $upper = mb_strtoupper($stmt->extracted_text ?? '');
    $lines = array_slice(explode("\n", $upper), 0, 80);

    $label = null;
    foreach ($lines as $line) {
        $line = trim($line);
        if (!str_contains($line, 'RELEV') || strlen($line) < 10 || strlen($line) > 120) continue;
        if (preg_match('/RELEV[EÉ]\s+(?:DE\s+)?(?:COMPTE\s+)?.{5,60}?(?=\s+P\.?\s*\d|\s*$)/u', $line, $m)) {
            $raw = trim(preg_replace('/\s+P\.?\s*\d+.*$/u', '', $m[0]));
            $raw = trim(preg_replace('/\s+/u', ' ', $raw));
            if (strlen($raw) < 10) continue;
            $clean = preg_replace('/[ÈÉÊ]/u', 'E', mb_strtoupper($raw));
            foreach ($knownLabels as $key => $value) {
                if (str_contains($clean, $key)) { $label = $value; break 2; }
            }
            if (!$label && strlen($raw) >= 10) {
                $label = mb_convert_case(mb_strtolower($raw), MB_CASE_TITLE, 'UTF-8');
                break;
            }
        }
    }

    if ($label) {
        $account->forceFill(['account_label' => $label])->save();
        echo "Compte #{$account->id}: \"{$label}\"\n";
    } else {
        echo "Compte #{$account->id}: label non trouvé\n";
    }
}
echo "\nTerminé.\n";
