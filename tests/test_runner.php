<?php
declare(strict_types=1);

/**
 * ============================================================================
 * Biomechanical & Mathematical Unit Test Suite
 * Validates All Hypertrophy Engine & Plate Solver Rules
 * ============================================================================
 */

putenv("DB_DRIVER=sqlite");

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../models/PasswordReset.php';
require_once __DIR__ . '/../models/Exercise.php';
require_once __DIR__ . '/../services/PlateSolverService.php';
require_once __DIR__ . '/../services/RfiService.php';
require_once __DIR__ . '/../services/InjuryFilterService.php';

use App\Config\Database;
use App\Config\Constants;
use App\Models\Exercise;
use App\Services\PlateSolverService;
use App\Services\RfiService;
use App\Services\InjuryFilterService;
use App\Models\PasswordReset;

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
echo "Dynamic Hypertrophy & Plate Solver Test Runner\n";
echo "================================================================\n\n";

// ----------------------------------------------------------------------------
// TEST 1: PlateSolverService - 12.5 kg with 2.5 kg Dumbbell handle
// ----------------------------------------------------------------------------
echo "1. PlateSolverService Tests:\n";
$solver = new PlateSolverService();
$plates = [
    '5kg'    => 4,
    '2.5kg'  => 4,
    '1.25kg' => 4
];

$res1 = $solver->solve(12.5, false, $plates, 2.5);
// Bar 2.5kg + 10kg plates (5kg on each side: 1x5kg)
assertTest(
    "12.5 kg Dumbbell with 2.5kg handle solves to exact 12.5kg with 1x5kg per side",
    $res1['feasible_weight'] == 12.5 && strpos($res1['recipe'], '1x5kg') !== false,
    "Got: {$res1['feasible_weight']} kg, Recipe: {$res1['recipe']}"
);

// Barbell 20kg + 100kg target
$resBarbell = $solver->solve(100.0, true, ['20kg' => 4, '10kg' => 2, '5kg' => 2], 20.0);
// 100kg - 20kg = 80kg (40kg per side: 2x20kg)
assertTest(
    "100 kg Barbell with 20kg bar solves to 2x20kg per side",
    $resBarbell['feasible_weight'] == 100.0 && strpos($resBarbell['recipe'], '2x20kg') !== false,
    "Got: {$resBarbell['feasible_weight']} kg, Recipe: {$resBarbell['recipe']}"
);

// ----------------------------------------------------------------------------
// TEST 2: Combined Ceiling Rule min(10%, 2.5kg)
// ----------------------------------------------------------------------------
echo "\n2. Combined Ceiling Rule Tests:\n";
// Scenario A: 6 kg Lateral Raise
$weightA = 6.0;
$ceilingA = min($weightA * Constants::MAX_SINGLE_SET_PCT, Constants::MAX_SINGLE_SET_KG);
assertTest(
    "6 kg Lateral Raise ceiling strictly limits increase to 0.60 kg (not 2.5 kg)",
    abs($ceilingA - 0.60) < 0.001,
    "Calculated: {$ceilingA} kg"
);

// Scenario B: 100 kg Squat
$weightB = 100.0;
$ceilingB = min($weightB * Constants::MAX_SINGLE_SET_PCT, Constants::MAX_SINGLE_SET_KG);
assertTest(
    "100 kg Squat ceiling strictly caps single set jump to 2.50 kg (not 10 kg)",
    abs($ceilingB - 2.50) < 0.001,
    "Calculated: {$ceilingB} kg"
);

// ----------------------------------------------------------------------------
// TEST 3: Session Ceiling (+20% of first set weight)
// ----------------------------------------------------------------------------
echo "\n3. Session +20% Ceiling Tests:\n";
$firstSetWeight = 20.0;
$maxSessionAllowed = round($firstSetWeight * (1.0 + Constants::MAX_SESSION_INCREASE_PCT), 2);
assertTest(
    "Initial set 20 kg has maximum session ceiling of exactly 24.0 kg",
    abs($maxSessionAllowed - 24.0) < 0.001,
    "Calculated: {$maxSessionAllowed} kg"
);

// ----------------------------------------------------------------------------
// TEST 4: RFI Service Calisthenic Baseline Formulas
// ----------------------------------------------------------------------------
echo "\n4. RFI Calisthenic Baseline Tests:\n";
$rfi = new RfiService();
// Bodyweight 80kg, Pushup 20, Chinup 10, Squat 30
// Push Horizontal: 80 * 0.30 + (20 * 0.25) = 24 + 5 = 29.0 kg
// Pull: 80 * 0.35 + (10 * 0.60) = 28 + 6 = 34.0 kg
// Squat: 80 * 0.50 + (30 * 0.20) = 40 + 6 = 46.0 kg
$baselines = $rfi->calculateBaselineLoads(80.0, 20, 10, 30, 60);

assertTest(
    "Push Horizontal formula: 80 * 0.30 + (20 * 0.25) = 29.0 kg",
    abs($baselines['push_horizontal'] - 29.0) < 0.001,
    "Calculated: {$baselines['push_horizontal']} kg"
);

assertTest(
    "Pull formula: 80 * 0.35 + (10 * 0.60) = 34.0 kg",
    abs($baselines['pull_vertical'] - 34.0) < 0.001,
    "Calculated: {$baselines['pull_vertical']} kg"
);

assertTest(
    "Squat formula: 80 * 0.50 + (30 * 0.20) = 46.0 kg",
    abs($baselines['squat'] - 46.0) < 0.001,
    "Calculated: {$baselines['squat']} kg"
);

// Absolute Beginner Regression Check (0 pushups, 0 chinups)
$tier0 = $rfi->evaluateFitnessTier(0, 0, 5);
assertTest(
    "0 Pushups and 0 Chinups sets level to absolute_beginner with Wall Push-up regression",
    $tier0['level'] === 'absolute_beginner' && isset($tier0['regressions']['push_horizontal']),
    "Level: {$tier0['level']}"
);

// Intermediate Tier Check (20 pushups, 8 chinups, 30 squats, 60s plank)
$tierIntermediate = $rfi->evaluateFitnessTier(20, 8, 30, 60);
assertTest(
    "20 Pushups, 8 Chinups, 30 Squats, 60s Plank sets level to intermediate",
    $tierIntermediate['level'] === 'intermediate',
    "Level: {$tierIntermediate['level']}"
);

// Advanced Tier Check (35 pushups, 20 chinups, 60 squats, 130s plank)
$tierAdvanced = $rfi->evaluateFitnessTier(35, 20, 60, 130);
assertTest(
    "35 Pushups, 20 Chinups, 60 Squats, 130s Plank sets level to advanced",
    $tierAdvanced['level'] === 'advanced',
    "Level: {$tierAdvanced['level']}"
);

// ----------------------------------------------------------------------------
// TEST 5: Form Breakdown & Consecutive RIR 0 Logic
// ----------------------------------------------------------------------------
echo "\n5. Form Breakdown & RIR Rules Verification:\n";
$w = 50.0;
$emergencyDrop = round($w * (1.0 - Constants::FORM_BREAKDOWN_DROP_PCT), 2);
assertTest(
    "Form breakdown immediately drops 50 kg by 15% to 42.5 kg",
    abs($emergencyDrop - 42.5) < 0.001,
    "Calculated: {$emergencyDrop} kg"
);

$consecutiveRir0Drop = round($w * (1.0 - Constants::RIR0_CONSECUTIVE_DROP_PCT), 2);
assertTest(
    "Second consecutive RIR 0 drops 50 kg by 10% to 45.0 kg",
    abs($consecutiveRir0Drop - 45.0) < 0.001,
    "Calculated: {$consecutiveRir0Drop} kg"
);

// ----------------------------------------------------------------------------
// TEST 6: InjuryFilterService isExerciseHarmful Tests
// ----------------------------------------------------------------------------
echo "\n6. InjuryFilterService isExerciseHarmful Tests:\n";
$db = Database::getConnection();
$db->beginTransaction();
try {
    $filter = new InjuryFilterService($db);

    // Setup dummy data
    $dummyExerciseId = 99999;
    $db->exec("PRAGMA foreign_keys = OFF;");
    $db->exec("INSERT INTO exercises (id, name, movement_pattern, primary_muscle_group, required_equipment) VALUES ({$dummyExerciseId}, 'Dummy Exercise', 'horizontal_push', 'chest', 'bodyweight')");
    $db->exec("INSERT INTO exercise_joint_stress (exercise_id, joint_area) VALUES ({$dummyExerciseId}, 'shoulder')");
    $db->exec("INSERT INTO exercise_joint_stress (exercise_id, joint_area) VALUES ({$dummyExerciseId}, 'elbow')");

    // Test cases
    assertTest(
        "Empty injured joints array returns false",
        $filter->isExerciseHarmful($dummyExerciseId, []) === false
    );

    assertTest(
        "Single injured joint that matches the exercise stress returns true",
        $filter->isExerciseHarmful($dummyExerciseId, ['shoulder']) === true
    );

    assertTest(
        "Single injured joint that does NOT match the exercise stress returns false",
        $filter->isExerciseHarmful($dummyExerciseId, ['knee']) === false
    );

    assertTest(
        "Multiple injured joints where at least one matches returns true",
        $filter->isExerciseHarmful($dummyExerciseId, ['knee', 'elbow']) === true
    );

    assertTest(
        "Multiple injured joints where none match returns false",
        $filter->isExerciseHarmful($dummyExerciseId, ['knee', 'ankle']) === false
    );

} finally {
    $db->rollBack();
}

// ----------------------------------------------------------------------------
// TEST 7: PasswordReset DB & Hash operations
// ----------------------------------------------------------------------------
echo "\n7. PasswordReset Model Tests:\n";
$pdo = Database::getConnection();
$pdo->beginTransaction();
try {
    $resetModel = new PasswordReset();
    $testEmail = "test_reset_" . time() . "@example.com";

    // Test createResetToken
    $rawToken = $resetModel->createResetToken($testEmail);
    assertTest(
        "createResetToken returns a 64-character hex string (32 bytes cryptographically secure)",
        strlen($rawToken) === 64,
        "Got length: " . strlen($rawToken)
    );

    // Verify DB insertion directly
    $stmt = $pdo->prepare("SELECT token_hash, expires_at FROM password_resets WHERE email = :email");
    $stmt->execute(['email' => $testEmail]);
    $row = $stmt->fetch();

    assertTest(
        "createResetToken successfully inserts a hashed token into the database",
        $row !== false && $row['token_hash'] === hash('sha256', $rawToken),
        "DB record found and hash matches"
    );

} finally {
    $pdo->rollBack();
}

// ----------------------------------------------------------------------------
// TEST 8: Exercise Model getRegression Method
// ----------------------------------------------------------------------------
echo "\n8. Exercise::getRegression Tests:\n";
$db = Database::getConnection();
$exerciseModel = new Exercise();

try {
    $db->beginTransaction();

    $db->exec("INSERT INTO `exercises` (`name`, `movement_pattern`, `primary_muscle_group`, `required_equipment`, `difficulty_level`) VALUES ('Regression Pushup', 'push_horizontal', 'chest', 'bodyweight', 'absolute_beginner')");
    $regressionId = (int)$db->lastInsertId();

    $stmt = $db->prepare("INSERT INTO `exercises` (`name`, `movement_pattern`, `primary_muscle_group`, `required_equipment`, `is_regression_of`, `difficulty_level`) VALUES ('Standard Pushup Runner', 'push_horizontal', 'chest', 'bodyweight', :regressionId, 'beginner')");
    $stmt->execute(['regressionId' => $regressionId]);
    $mainId = (int)$db->lastInsertId();

    $db->exec("INSERT INTO `exercises` (`name`, `movement_pattern`, `primary_muscle_group`, `required_equipment`, `difficulty_level`) VALUES ('Squat Runner', 'squat', 'legs', 'bodyweight', 'beginner')");
    $unrelatedId = (int)$db->lastInsertId();

    $regression = $exerciseModel->getRegression($mainId);
    assertTest(
        "Exercise with regression returns correct regression array",
        $regression !== null && (int)$regression['id'] === $regressionId && $regression['name'] === 'Regression Pushup',
        "Expected ID {$regressionId}, got " . ($regression ? print_r($regression, true) : 'null')
    );

    $noRegression = $exerciseModel->getRegression($unrelatedId);
    assertTest(
        "Exercise without regression returns null",
        $noRegression === null,
        "Expected null, got " . print_r($noRegression, true)
    );

    $invalidRegression = $exerciseModel->getRegression(999999);
    assertTest(
        "Invalid exercise ID returns null",
        $invalidRegression === null,
        "Expected null, got " . print_r($invalidRegression, true)
    );

} catch (Exception $e) {
    echo "  [FAIL] Test setup failed: " . $e->getMessage() . "\n";
    $failed++;
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}

echo "\n================================================================\n";
echo "SUMMARY: Passed: {$passed} | Failed: {$failed}\n";
echo "================================================================\n";
