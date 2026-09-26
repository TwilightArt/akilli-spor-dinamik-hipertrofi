<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Security.php';

\App\Core\Security::startSession();
$_SESSION['user'] = [
    'id' => 1,
    'name' => 'Samet Altınöz',
    'email' => 'sametltnz@gmail.com',
    'role' => 'athlete',
    'fitness_level' => 'intermediate'
];
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Equipment.php';
require_once __DIR__ . '/../models/Exercise.php';
require_once __DIR__ . '/../models/WorkoutSession.php';
require_once __DIR__ . '/../models/WorkoutLog.php';
require_once __DIR__ . '/../models/DailyWeighIn.php';
require_once __DIR__ . '/../services/InjuryFilterService.php';
require_once __DIR__ . '/../services/GeminiWorkoutService.php';
require_once __DIR__ . '/../services/PlateSolverService.php';
require_once __DIR__ . '/../services/HypertrophyEngine.php';
require_once __DIR__ . '/../controllers/WorkoutController.php';

ob_start();
$ctrl = new App\Controllers\WorkoutController();
$ctrl->liveSession();
$html = ob_get_clean();

echo "Rendered HTML length: " . strlen($html) . "\n";
echo "Contains Wall Push-Up: " . (strpos($html, 'Wall Push-Up') !== false ? 'YES' : 'NO') . "\n";
echo "Contains focus_area Üst Vücut A: " . (strpos($html, 'Üst Vücut A') !== false ? 'YES' : 'NO') . "\n";
if (strpos($html, 'Wall Push-Up') === false) {
    echo "First 1000 chars of output:\n" . substr($html, 0, 1000) . "\n";
}
