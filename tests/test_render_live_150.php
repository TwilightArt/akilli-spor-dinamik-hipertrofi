<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Equipment.php';
require_once __DIR__ . '/../models/Exercise.php';
require_once __DIR__ . '/../models/WorkoutSession.php';
require_once __DIR__ . '/../models/WorkoutLog.php';
require_once __DIR__ . '/../services/PlateSolverService.php';
require_once __DIR__ . '/../services/InjuryFilterService.php';
require_once __DIR__ . '/../services/HypertrophyEngine.php';
require_once __DIR__ . '/../controllers/WorkoutController.php';

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['csrf_token'] = 'test-token';

$controller = new \App\Controllers\WorkoutController();
ob_start();
$controller->liveSession('150');
$html = ob_get_clean();

echo "HTML LENGTH: " . strlen($html) . "\n";

// Search for JSON or errors
if (preg_match('/liveWorkout\((\{.*?\})\)/s', $html, $matches)) {
    echo "Found liveWorkout config JSON!\n";
    $jsonConfig = $matches[1];
    // Check if JSON is valid
    // Extract exercises:
    if (preg_match('/exercises:\s*(\[.*?\])\s*\}/s', $jsonConfig, $mEx)) {
        echo "Found exercises JSON! Length: " . strlen($mEx[1]) . "\n";
        $decoded = json_decode($mEx[1], true);
        if ($decoded === null) {
            echo "JSON DECODE ERROR: " . json_last_error_msg() . "\n";
            echo "FIRST 200 CHARS OF EXERCISES JSON:\n" . substr($mEx[1], 0, 200) . "\n";
        } else {
            echo "Exercises JSON decoded successfully! Count: " . count($decoded) . "\n";
        }
    }
} else {
    echo "Could not find liveWorkout config!\n";
}

// Check for any PHP errors in HTML
if (strpos($html, 'Fatal error') !== false || strpos($html, 'Parse error') !== false) {
    echo "FOUND PHP ERROR IN HTML!\n";
}
