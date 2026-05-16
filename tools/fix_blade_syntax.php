<?php
$file = __DIR__ . '/../resources/views/cases/show.blade.php';
$content = file_get_contents($file);

// Fix missing semicolons on return lines inside map closures (lines 4622 and 4626)
$fixed = preg_replace(
    "/return (number_format\(\\\$x,1,'\.',''[^;\\n]+)\n(\s+\}\)->implode)/",
    "return $1;\n$2",
    $content
);

if ($fixed === null) {
    echo "Regex error: " . preg_last_error_msg() . PHP_EOL;
    exit(1);
}

$count = substr_count($fixed, "return number_format") - substr_count($content, "return number_format");
file_put_contents($file, $fixed);

// Count lines changed
$before = preg_match_all("/return number_format\([^;\\n]+\n/", $content, $m1);
$after  = preg_match_all("/return number_format\([^;\\n]+\n/", $fixed, $m2);
echo "Missing semicolons before: $before, after: $after" . PHP_EOL;
echo ($fixed !== $content ? "File updated." : "No change made.") . PHP_EOL;
