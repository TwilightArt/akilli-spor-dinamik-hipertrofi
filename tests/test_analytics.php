<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/sqlite_bootstrap.php';
require_once __DIR__ . '/../services/AnalyticsService.php';

putenv("DB_DRIVER=sqlite");

use App\Config\Database;
use App\Database\SqliteBootstrap;
use App\Services\AnalyticsService;

$passed = 0;
$failed = 0;

function assertTest(string $name, bool $condition, string $details = '') {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$name}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$name} - Details: {$details}\n";
        $failed++;
    }
}

echo "================================================================\n";
echo "Analytics Service Unit Tests\n";
echo "================================================================\n\n";

try {
    $db = Database::getConnection();
    SqliteBootstrap::init($db);

    // Setup basic mock data
    $userId = 999;
    $db->exec("INSERT OR IGNORE INTO users (id, name, email, password_hash, gender, birth_date, height_cm) VALUES ({$userId}, 'Test User', 'test{$userId}@example.com', 'hash', 'male', '1990-01-01', 180)");
    $db->exec("DELETE FROM workout_sessions WHERE id = 1");
    $db->exec("DELETE FROM workout_logs WHERE session_id = 1");

    // Exercises are already seeded by SqliteBootstrap::init(), so we don't need to insert them again.
    // Exercise 8 is Barbell Flat Bench Press.
    $exerciseId = 8;

    // Create a workout session
    $db->exec("INSERT INTO workout_sessions (id, user_id, started_at, ended_at) VALUES (1, {$userId}, '2024-01-01 10:00:00', '2024-01-01 11:00:00')");

    // Add some logs
    // Bench Press Set 1 (50kg x 10)
    $db->exec("INSERT INTO workout_logs (user_id, session_id, exercise_id, set_number, set_type, target_weight, actual_weight, target_reps, actual_reps, rir_bucket, form_breakdown, created_at) VALUES ({$userId}, 1, {$exerciseId}, 1, 'reps', 50, 50, 10, 10, '1-2', 0, '2024-01-01 10:10:00')");
    // Bench Press Set 2 (52.5kg x 8)
    $db->exec("INSERT INTO workout_logs (user_id, session_id, exercise_id, set_number, set_type, target_weight, actual_weight, target_reps, actual_reps, rir_bucket, form_breakdown, created_at) VALUES ({$userId}, 1, {$exerciseId}, 2, 'reps', 52.5, 52.5, 10, 8, '0', 0, '2024-01-01 10:15:00')");

    $analytics = new AnalyticsService($db);

    echo "1. Testing getUserExercisesList...\n";
    $exercisesList = $analytics->getUserExercisesList($userId);
    $benchData = null;
    foreach ($exercisesList as $ex) {
        if ($ex['id'] == $exerciseId) {
            $benchData = $ex;
            break;
        }
    }

    assertTest("getUserExercisesList returns logged exercises", !empty($benchData), "Bench press data not found");
    assertTest("total_sets_logged is correct", (int)($benchData['total_sets_logged'] ?? 0) === 2, "Expected 2 sets");
    assertTest("max_weight_ever is correct", (float)($benchData['max_weight_ever'] ?? 0) === 52.5, "Expected 52.5");

    echo "\n2. Testing getExerciseTimeline...\n";
    $timeline = $analytics->getExerciseTimeline($userId, $exerciseId);
    assertTest("getExerciseTimeline returns timeline for session", count($timeline) === 1, "Expected 1 timeline entry");
    assertTest("timeline peak_weight is correct", (float)($timeline[0]['peak_weight'] ?? 0) === 52.5, "Expected peak 52.5");
    assertTest("timeline set_count is correct", (int)($timeline[0]['set_count'] ?? 0) === 2, "Expected 2 sets in timeline");

    echo "\n3. Testing getExerciseDetailedSets...\n";
    $detailedSets = $analytics->getExerciseDetailedSets($userId, $exerciseId);
    assertTest("getExerciseDetailedSets returns all sets", count($detailedSets) === 2, "Expected 2 detailed sets");
    $prSetFound = false;
    foreach ($detailedSets as $set) {
        if ($set['actual_weight'] == 52.5 && $set['is_pr'] === true) {
            $prSetFound = true;
        }
    }
    assertTest("is_pr flag is correctly assigned to highest weight", $prSetFound, "PR flag missing on 52.5kg set");

    echo "\n4. Testing getAllExercisesPeakWeights...\n";
    $peaks = $analytics->getAllExercisesPeakWeights($userId);
    assertTest("getAllExercisesPeakWeights returns peaks for all trained exercises", count($peaks) === 1, "Expected 1 peak entry");
    assertTest("peak_weight is correct", (float)($peaks[0]['peak_weight'] ?? 0) === 52.5, "Expected peak 52.5");

} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n================================================================\n";
echo "SUMMARY: Passed: {$passed} | Failed: {$failed}\n";
echo "================================================================\n";

if ($failed > 0) {
    exit(1);
}
