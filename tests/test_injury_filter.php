<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../services/InjuryFilterService.php';

$filter = new \App\Services\InjuryFilterService();
$db = \App\Config\Database::getConnection();

$stmtEx = $db->prepare("
    SELECT pe.*, e.name as exercise_name, e.movement_pattern, e.required_equipment,
           e.primary_muscle_group, e.primary_joint_stress
    FROM program_exercises pe
    JOIN exercises e ON pe.exercise_id = e.id
    WHERE pe.program_day_id = 150
    ORDER BY pe.order_in_session ASC
");
$stmtEx->execute();
$dayExercises = $stmtEx->fetchAll();

echo "BEFORE FILTER: " . count($dayExercises) . " exercises\n";
$filtered = $filter->filterProgramExercises(1, $dayExercises, ['bodyweight', 'barbell', 'dumbbell']);
echo "AFTER FILTER: " . count($filtered) . " exercises\n";
print_r($filtered);
