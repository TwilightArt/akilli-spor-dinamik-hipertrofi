<?php
$html = file_get_contents(__DIR__ . '/live_user32_output.html');

preg_match('/<div x-data="liveWorkout\((\{.*?\})\)" class=/s', $html, $m);
if (!empty($m[1])) {
    echo "Testing if JavaScript JSON is valid...\n";
    // Let's test evaluating the config
    $content = $m[1];
    // Find exercises: [...]
    preg_match('/exercises:\s*(\[.*?\])\s*\}\)/s', $html, $mex);
    if (!empty($mex[1])) {
        echo "Found exercises array string!\n";
        $exStr = $mex[1];
        $dec = json_decode($exStr);
        if ($dec === null) {
            echo "FAILED TO DECODE EXERCISES JSON! Error: " . json_last_error_msg() . "\n";
            // Check byte sequence around error
            echo "Hex around ğ: " . bin2hex(substr($exStr, 0, 400)) . "\n";
        } else {
            echo "Successfully decoded exercises! Count: " . count($dec) . "\n";
        }
    }
}
