<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$svc = app(\App\Services\StatementImportService::class);
$ref = new \ReflectionClass($svc);

$parseMonth = $ref->getMethod('parseFrenchMonthDate');
$parseMonth->setAccessible(true);

$parsePeriod = $ref->getMethod('extractStatementPeriod');
$parsePeriod->setAccessible(true);

echo "=== Fuzzy month matching ===\n";
$tests = [
    "janv'er", "févr'er", "mars", "avr'l", "ma'", "ju'n",
    "ju'llet", "aoct", "septembre", "octobr'e", "nov", "d'cembre", "janver", "decembr'e",
];
foreach ($tests as $month) {
    $result = $parseMonth->invoke($svc, 15, $month, 2021);
    echo "  '$month' -> " . ($result ?? 'NULL') . "\n";
}

echo "\n=== Period extraction from OCR text ===\n";
$texts = [
    "du 22 avr'l 2021 au 22 ma' 2021",
    "du 22 d'cembre 2020 au 22 janv'er 2021",
    "du 22 janv'er 2023 au 22 févr'er 2023",
    "du 22 décembre 2022 au 22 janv'er 2023",
    "du 22 fevrier 2021 au 22 mars 2021",
];
foreach ($texts as $t) {
    $result = $parsePeriod->invoke($svc, $t);
    echo "  INPUT: $t\n  RESULT: " . json_encode($result) . "\n\n";
}
