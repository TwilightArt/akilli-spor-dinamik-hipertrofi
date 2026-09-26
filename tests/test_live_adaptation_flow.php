<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Equipment.php';
require_once __DIR__ . '/../models/WorkoutSession.php';
require_once __DIR__ . '/../models/WorkoutLog.php';
require_once __DIR__ . '/../models/Exercise.php';
require_once __DIR__ . '/../services/PlateSolverService.php';
require_once __DIR__ . '/../services/HypertrophyEngine.php';

$db = \App\Config\Database::getConnection();
$userModel = new \App\Models\User();
$user = $userModel->findByEmail('sametltnz@gmail.com');
$userId = (int)$user['id'];

// Get an active session
$sessionModel = new \App\Models\WorkoutSession();
$activeSession = $sessionModel->getActiveSession($userId);
if (!$activeSession) {
    $sid = $sessionModel->startSession($userId, 1);
    $activeSession = $sessionModel->findById($sid);
}
$sessionId = (int)$activeSession['id'];

// Exercise: Barbell Flat Bench Press
$ex = $db->query("SELECT * FROM exercises WHERE required_equipment = 'barbell' LIMIT 1")->fetch();
$exerciseId = (int)$ex['id'];

echo "=== CANLI SET ADAPTASYON SIMÜLASYONU ===\n";
echo "Kullanıcı: " . $user['name'] . " (ID: {$userId})\n";
echo "Egzersiz: " . $ex['name'] . " (ID: {$exerciseId})\n\n";

$engine = new \App\Services\HypertrophyEngine();

// Clean up old logs for this test session to be clean
$db->exec("DELETE FROM workout_logs WHERE session_id = {$sessionId} AND exercise_id = {$exerciseId}");

// --- SET 1: Kullanıcı 50 kg ile 10 tekrar yaptı, RIR 3+ (Kolay geldi) bildirdi ---
echo "--- 1. SET GİRİŞİ ---\n";
echo "Girilen Değerler: 50.0 kg, 10 tekrar, RIR: 3+ (Kolay)\n";
$logModel = new \App\Models\WorkoutLog();
$logModel->logSet([
    'user_id' => $userId,
    'session_id' => $sessionId,
    'exercise_id' => $exerciseId,
    'set_number' => 1,
    'set_type' => 'reps',
    'actual_weight' => 50.0,
    'target_reps' => 10,
    'actual_reps' => 10,
    'rir_bucket' => '3+',
    'form_breakdown' => 0
]);

$r1 = $engine->computeNextSet($userId, $sessionId, $exerciseId, 1, 50.0, 10, '3+', false, 10);
echo "Sonuç Yönü: " . $r1['direction'] . "\n";
echo "Önerilen Sonraki Ağırlık: " . $r1['next_weight'] . " kg (Değişim: +" . ($r1['next_weight'] - 50.0) . " kg)\n";
echo "Plaka Tarifi: " . $r1['plate_recipe'] . "\n";
echo "Açıklama: " . $r1['reason'] . "\n\n";

assert($r1['direction'] === 'INCREASE', "Yön INCREASE olmalı");
assert($r1['next_weight'] == 52.5, "Sonraki ağırlık 52.5 kg olmalı");

// --- SET 2: Kullanıcı 52.5 kg ile 10 tekrar yaptı, RIR 1-2 (İdeal) bildirdi ---
echo "--- 2. SET GİRİŞİ ---\n";
echo "Girilen Değerler: 52.5 kg, 10 tekrar, RIR: 1-2 (İdeal)\n";
$logModel->logSet([
    'user_id' => $userId,
    'session_id' => $sessionId,
    'exercise_id' => $exerciseId,
    'set_number' => 2,
    'set_type' => 'reps',
    'actual_weight' => 52.5,
    'target_reps' => 10,
    'actual_reps' => 10,
    'rir_bucket' => '1-2',
    'form_breakdown' => 0
]);

$r2 = $engine->computeNextSet($userId, $sessionId, $exerciseId, 2, 52.5, 10, '1-2', false, 10);
echo "Sonuç Yönü: " . $r2['direction'] . "\n";
echo "Önerilen Sonraki Ağırlık: " . $r2['next_weight'] . " kg\n";
echo "Plaka Tarifi: " . $r2['plate_recipe'] . "\n";
echo "Açıklama: " . $r2['reason'] . "\n\n";

assert($r2['direction'] === 'MAINTAIN', "Yön MAINTAIN olmalı");
assert($r2['next_weight'] == 52.5, "Sonraki ağırlık 52.5 kg korunmalı");

// --- SET 3: Kullanıcı 52.5 kg ile yaptı ama Form Bozuldu ---
echo "--- 3. SET GİRİŞİ ---\n";
echo "Girilen Değerler: 52.5 kg, 8 tekrar, Form Bozulması: EVET\n";
$logModel->logSet([
    'user_id' => $userId,
    'session_id' => $sessionId,
    'exercise_id' => $exerciseId,
    'set_number' => 3,
    'set_type' => 'reps',
    'actual_weight' => 52.5,
    'target_reps' => 10,
    'actual_reps' => 8,
    'rir_bucket' => '0',
    'form_breakdown' => 1
]);

$r3 = $engine->computeNextSet($userId, $sessionId, $exerciseId, 3, 52.5, 8, '0', true, 10);
echo "Sonuç Yönü: " . $r3['direction'] . "\n";
echo "Önerilen Sonraki Ağırlık: " . $r3['next_weight'] . " kg\n";
echo "Plaka Tarifi: " . $r3['plate_recipe'] . "\n";
echo "Açıklama: " . $r3['reason'] . "\n\n";

assert($r3['direction'] === 'DECREASE', "Yön DECREASE olmalı");
assert($r3['next_weight'] < 52.5, "Sonraki ağırlık düşürülmeli");

echo "✅ TÜM CANLI ADAPTASYON TESTLERİ BAŞARIYLA GEÇTİ!\n";
