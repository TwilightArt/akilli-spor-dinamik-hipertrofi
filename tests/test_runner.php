<?php
declare(strict_types=1);

/**
 * ============================================================================
 * Biomechanical & Mathematical Unit Test Suite
 * Validates All Hypertrophy Engine & Plate Solver Rules
 * ============================================================================
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../services/PlateSolverService.php';
require_once __DIR__ . '/../services/RfiService.php';

use App\Config\Constants;
use App\Services\PlateSolverService;
use App\Services\RfiService;

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

echo "\n================================================================\n";
echo "SUMMARY: Passed: {$passed} | Failed: {$failed}\n";
echo "================================================================\n";
