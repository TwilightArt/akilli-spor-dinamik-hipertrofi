<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../models/WorkoutLog.php';

use App\Models\WorkoutLog;
use App\Config\Database;

putenv("DB_DRIVER=sqlite");
$db = Database::getConnection();

$db->exec("DELETE FROM workout_logs");
$db->exec("DELETE FROM workout_sessions");
$db->exec("DELETE FROM users");

$db->exec("INSERT INTO users (id, name, email, password_hash, fitness_level, gender, birth_date, height_cm) VALUES (999, 'Test User', 'test@example.com', 'hash', 'beginner', 'male', '1990-01-01', 180)");
$db->exec("INSERT INTO workout_sessions (id, user_id) VALUES (999, 999)");

echo "Setup and seeding complete.\n";

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

$workoutLogModel = new WorkoutLog();

function insertLog(PDO $db, int $sessionId, int $exerciseId, int $setNumber, string $rirBucket) {
    $stmt = $db->prepare("
        INSERT INTO workout_logs
        (user_id, session_id, exercise_id, set_number, set_type, target_weight, actual_weight, target_reps, actual_reps, rir_bucket)
        VALUES (999, :sid, :eid, :snum, 'reps', 50, 50, 10, 10, :rir)
    ");
    $stmt->execute([
        'sid' => $sessionId,
        'eid' => $exerciseId,
        'snum' => $setNumber,
        'rir' => $rirBucket
    ]);
}

echo "\nRunning Edge Case Tests for getConsecutiveRir0Count:\n";

// Case 1: Zero consecutive RIR 0
$db->exec("DELETE FROM workout_logs");
insertLog($db, 999, 1, 1, '1-2');
insertLog($db, 999, 1, 2, '3+');
$res = $workoutLogModel->getConsecutiveRir0Count(999, 1);
assertTest("Zero consecutive RIR 0 returns 0", $res === 0, "Got $res");

// Case 2: One consecutive RIR 0 at the end
$db->exec("DELETE FROM workout_logs");
insertLog($db, 999, 1, 1, '1-2');
insertLog($db, 999, 1, 2, '0');
$res = $workoutLogModel->getConsecutiveRir0Count(999, 1);
assertTest("One consecutive RIR 0 at the end returns 1", $res === 1, "Got $res");

// Case 3: Multiple consecutive RIR 0 at the end
$db->exec("DELETE FROM workout_logs");
insertLog($db, 999, 1, 1, '1-2');
insertLog($db, 999, 1, 2, '0');
insertLog($db, 999, 1, 3, '0');
insertLog($db, 999, 1, 4, '0');
$res = $workoutLogModel->getConsecutiveRir0Count(999, 1);
assertTest("Multiple consecutive RIR 0 at the end returns 3", $res === 3, "Got $res");

// Case 4: Non-consecutive RIR 0
$db->exec("DELETE FROM workout_logs");
insertLog($db, 999, 1, 1, '0');
insertLog($db, 999, 1, 2, '1-2');
insertLog($db, 999, 1, 3, '0');
insertLog($db, 999, 1, 4, '0');
$res = $workoutLogModel->getConsecutiveRir0Count(999, 1);
assertTest("Non-consecutive RIR 0 returns count of latest consecutive zeroes (2)", $res === 2, "Got $res");

// Case 5: Zero consecutive RIR 0 when earlier sets had 0
$db->exec("DELETE FROM workout_logs");
insertLog($db, 999, 1, 1, '0');
insertLog($db, 999, 1, 2, '0');
insertLog($db, 999, 1, 3, '1-2');
$res = $workoutLogModel->getConsecutiveRir0Count(999, 1);
assertTest("Zero consecutive RIR 0 when earlier sets had 0 returns 0", $res === 0, "Got $res");

echo "\nSummary: Passed: $passed, Failed: $failed\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
