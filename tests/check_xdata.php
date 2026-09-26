<?php
$html = file_get_contents(__DIR__ . '/live_user32_output.html');

if (preg_match('/<div x-data="liveWorkout\((\{.*?\})\)"/s', $html, $m)) {
    echo "FOUND liveWorkout config!\n";
    echo substr($m[1], 0, 500) . "\n...\n";
} else {
    echo "NOT FOUND liveWorkout config!\n";
}

// Check if any errors are present
if (strpos($html, 'Fatal error') !== false) {
    echo "HAS FATAL ERROR!\n";
}
