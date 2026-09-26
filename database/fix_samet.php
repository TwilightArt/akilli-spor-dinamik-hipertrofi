<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Equipment.php';
require_once __DIR__ . '/../models/Exercise.php';
require_once __DIR__ . '/../models/WorkoutSession.php';
require_once __DIR__ . '/../models/DailyWeighIn.php';
require_once __DIR__ . '/../services/InjuryFilterService.php';
require_once __DIR__ . '/../services/GeminiWorkoutService.php';

$db = App\Config\Database::getConnection();

// Find Samet
$stmt = $db->query("SELECT * FROM users WHERE email LIKE '%samet%' LIMIT 1");
$samet = $stmt->fetch();

if ($samet) {
    $uid = (int)$samet['id'];
    echo "Configuring Samet Altınöz (ID: {$uid})...\n";

    // 1. Ensure Equipment
    $eqModel = new App\Models\Equipment();
    $eq = $eqModel->getByUserId($uid);
    if (!$eq) {
        $eqModel->saveOrUpdate($uid, true, true, 20.0, 2.5, [
            '20kg'   => 2,
            '10kg'   => 4,
            '5kg'    => 4,
            '2.5kg'  => 4,
            '1.25kg' => 4,
            '0.5kg'  => 2
        ], 'home_weights');
        echo "Equipment saved.\n";
    }

    // 2. Ensure Weigh-in
    $wModel = new App\Models\DailyWeighIn();
    $wModel->logWeight($uid, 78.0, date('Y-m-d'), 'Başlangıç Kilosu');
    echo "Weigh-in saved.\n";

    // 3. Ensure Program
    $progStmt = $db->prepare("SELECT id FROM user_programs WHERE user_id = :uid LIMIT 1");
    $progStmt->execute(['uid' => $uid]);
    $progId = $progStmt->fetchColumn();

    if (!$progId) {
        $gemini = new App\Services\GeminiWorkoutService();
        $progId = $gemini->generateWeeklyProgram($uid, 'intermediate', true);
        echo "Generated program ID: {$progId}\n";
    }

    // 4. Fix Workout Session (bind to Day 1)
    $dayStmt = $db->prepare("SELECT id FROM program_days WHERE program_id = :pid ORDER BY day_of_week ASC LIMIT 1");
    $dayStmt->execute(['pid' => $progId]);
    $firstDayId = (int)$dayStmt->fetchColumn();

    $db->exec("UPDATE workout_sessions SET program_day_id = {$firstDayId} WHERE user_id = {$uid} AND ended_at IS NULL");
    echo "Bound active session to Day ID: {$firstDayId}\n";
}
