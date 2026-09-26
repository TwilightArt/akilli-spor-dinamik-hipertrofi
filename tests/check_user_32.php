<?php
require_once __DIR__ . '/../config/database.php';
$db = \App\Config\Database::getConnection();

$u32 = $db->query("SELECT * FROM users WHERE id = 32")->fetch();
echo "USER 32:\n";
print_r($u32);

$eq32 = $db->query("SELECT * FROM user_equipment WHERE user_id = 32")->fetch();
echo "EQUIPMENT 32:\n";
print_r($eq32);

$inj32 = $db->query("SELECT * FROM injury_reports WHERE user_id = 32")->fetchAll();
echo "INJURIES 32:\n";
print_r($inj32);

$exs32 = $db->query("SELECT pe.*, e.name as exercise_name, e.movement_pattern, e.required_equipment,
       e.primary_muscle_group, e.primary_joint_stress
FROM program_exercises pe
JOIN exercises e ON pe.exercise_id = e.id
WHERE pe.program_day_id = 150
ORDER BY pe.order_in_session ASC")->fetchAll();
echo "PROGRAM EXERCISES 150 (count: " . count($exs32) . "):\n";
print_r($exs32);

// Now test WorkoutController::liveSession for user 32!
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../models/Equipment.php';
require_once __DIR__ . '/../models/Exercise.php';
require_once __DIR__ . '/../models/WorkoutSession.php';
require_once __DIR__ . '/../models/WorkoutLog.php';
require_once __DIR__ . '/../services/PlateSolverService.php';
require_once __DIR__ . '/../services/InjuryFilterService.php';
require_once __DIR__ . '/../services/HypertrophyEngine.php';
require_once __DIR__ . '/../controllers/WorkoutController.php';

$filter = new \App\Services\InjuryFilterService();
$equipment = $eq32;
$location = $equipment['workout_location'] ?? 'home_weights';
$allowedEquipment = ['bodyweight'];
if ($location === 'commercial_gym') {
    $allowedEquipment = ['bodyweight', 'barbell', 'dumbbell', 'machine', 'cable'];
} else {
    if (!empty($equipment['has_barbell'])) $allowedEquipment[] = 'barbell';
    if (!empty($equipment['has_dumbbells'])) $allowedEquipment[] = 'dumbbell';
}
echo "LOCATION: {$location}, ALLOWED EQ: " . implode(',', $allowedEquipment) . "\n";
$filtered = $filter->filterProgramExercises(32, $exs32, $allowedEquipment);
echo "FILTERED EXERCISES COUNT: " . count($filtered) . "\n";
print_r($filtered);
