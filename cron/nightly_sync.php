<?php
declare(strict_types=1);

/**
 * ============================================================================
 * Akıllı Spor & Dinamik Hipertrofi Platformu (v2.1)
 * cPanel Nightly Synchronization Cron Job
 * Command: 0 0 * * * /usr/local/bin/php /home/{username}/public_html/cron/nightly_sync.php >/dev/null 2>&1
 * ============================================================================
 */

// CLI check (prevent external access entirely)
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Erişim engellendi: Bu betik yalnızca sistem zamanlayıcısı (CLI) üzerinden çalıştırılabilir.\n");
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/gemini.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../models/DailyWeighIn.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Equipment.php';
require_once __DIR__ . '/../models/Exercise.php';
require_once __DIR__ . '/../services/InjuryFilterService.php';
require_once __DIR__ . '/../services/GeminiWorkoutService.php';

use App\Config\Database;
use App\Config\Constants;
use App\Models\DailyWeighIn;
use App\Services\GeminiWorkoutService;

$db = Database::getConnection();
$weighInModel = new DailyWeighIn();
$geminiService = new GeminiWorkoutService();

$logTimestamp = date('Y-m-d H:i:s');
echo "[{$logTimestamp}] Nightly sync starting...\n";

// 1. Fetch all users
$stmtUsers = $db->query("SELECT id, name, birth_date, fitness_level, created_at FROM users");
$users = $stmtUsers->fetchAll();

foreach ($users as $user) {
    $userId = (int)$user['id'];
    $birthDate = $user['birth_date'];
    $age = date_diff(date_create($birthDate), date_create('today'))->y;

    echo "İşleniyor: Kullanıcı #{$userId} ({$user['name']}), Yaş: {$age}...\n";

    // 2. Fetch Latest Program
    $stmtProg = $db->prepare("
        SELECT * FROM user_programs 
        WHERE user_id = :uid 
        ORDER BY id DESC LIMIT 1
    ");
    $stmtProg->execute(['uid' => $userId]);
    $latestProgram = $stmtProg->fetch();

    if (!$latestProgram) {
        continue;
    }

    $programId = (int)$latestProgram['id'];

    // 3. Deload Cycle Audit
    // Every 5-6 weeks, or every 4 weeks if user age >= 40
    $weeksCycleLimit = ($age >= Constants::SENIOR_AGE_THRESHOLD)
        ? Constants::SENIOR_DELOAD_CYCLE_WEEKS
        : Constants::STANDARD_DELOAD_CYCLE_WEEKS;

    $stmtWeekCount = $db->prepare("
        SELECT COUNT(*) as program_weeks 
        FROM user_programs 
        WHERE user_id = :uid AND created_at >= (
            SELECT COALESCE(MAX(created_at), '1970-01-01') 
            FROM user_programs 
            WHERE user_id = :uid2 AND is_deload_week = 1
        )
    ");
    $stmtWeekCount->execute(['uid' => $userId, 'uid2' => $userId]);
    $consecutiveNormalWeeks = (int)$stmtWeekCount->fetchColumn();

    $isDeloadWeek = false;
    if ($consecutiveNormalWeeks >= $weeksCycleLimit) {
        $isDeloadWeek = true;
        echo "  -> Deload döngüsü tetiklendi ({$consecutiveNormalWeeks}. hafta). Deload programı tanımlanıyor.\n";

        // Mark deload in user_programs
        $stmtDeload = $db->prepare("UPDATE user_programs SET is_deload_week = 1 WHERE id = :pid");
        $stmtDeload->execute(['pid' => $programId]);

        // Halve sets for all program exercises (minimum 2 sets)
        $stmtAdjustSets = $db->prepare("
            UPDATE program_exercises 
            SET target_sets = GREATEST(:min_sets, FLOOR(target_sets * :reduction_factor))
            WHERE program_day_id IN (SELECT id FROM program_days WHERE program_id = :pid)
        ");
        $stmtAdjustSets->execute([
            'min_sets'         => Constants::MIN_TARGET_SETS,
            'reduction_factor' => Constants::DELOAD_SET_REDUCTION_FACTOR,
            'pid'              => $programId
        ]);
        // Deload weight & intensity reduction (-10%) via RFI calibration log
        $patterns = ['push_horizontal', 'pull', 'squat', 'hinge'];
        $stmtCalib = $db->prepare("
            INSERT INTO user_calibration_log (user_id, movement_pattern, base_coefficient, adjusted_coefficient, reason)
            VALUES (:uid, :pattern, 1.0, 0.90, 'Deload döngüsü: %10 ağırlık azaltımı ve merkezi sinir sistemi toparlanması')
        ");
        foreach ($patterns as $pattern) {
            $stmtCalib->execute(['uid' => $userId, 'pattern' => $pattern]);
        }
    }

    // 4. Weigh-In Moving Average & Weekly Volume Adaptation
    // Check if adaptation already ran this week (using user_calibration_log as a lock)
    $stmtCheckLock = $db->prepare("
        SELECT id FROM user_calibration_log 
        WHERE user_id = :uid AND reason LIKE 'Haftalık Adaptasyon%' 
        AND created_at >= date('now', 'weekday 0', '-7 days')
    ");
    $stmtCheckLock->execute(['uid' => $userId]);
    $alreadyAdapted = (bool)$stmtCheckLock->fetch();

    $isWeeklyAdaptationDay = ((int)date('N') === 7);
    $weeklyRate = $isWeeklyAdaptationDay ? $weighInModel->getWeeklyRateOfChange($userId) : null;

    if ($weeklyRate !== null && !$isDeloadWeek && !$alreadyAdapted) {
        if ($weeklyRate < -Constants::WEIGHT_LOSS_RATE_THRESHOLD) {
            echo "  -> Hızlı kilo kaybı tespit edildi (" . round($weeklyRate * 100, 2) . "%). Hacim %25 dengeleniyor.\n";
            $stmtCutSets = $db->prepare("
                UPDATE program_exercises 
                SET target_sets = GREATEST(:min_sets, ROUND(target_sets * 0.75))
                WHERE program_day_id IN (SELECT id FROM program_days WHERE program_id = :pid)
            ");
            $stmtCutSets->execute([
                'min_sets' => Constants::MIN_TARGET_SETS,
                'pid'      => $programId
            ]);
            
            $db->prepare("INSERT INTO user_calibration_log (user_id, movement_pattern, base_coefficient, adjusted_coefficient, reason) VALUES (:uid, 'system', 1.0, 0.75, 'Haftalık Adaptasyon: Hızlı kilo kaybı (-25% hacim)')")->execute(['uid' => $userId]);
        } elseif ($weeklyRate >= 0.0) {
            $stmtIncVol = $db->prepare("
                UPDATE program_exercises 
                SET target_reps_max = LEAST(25, target_reps_max + 1)
                WHERE program_day_id IN (SELECT id FROM program_days WHERE program_id = :pid)
            ");
            $stmtIncVol->execute(['pid' => $programId]);
            echo "  -> Kilo stabil veya artışta (" . round($weeklyRate * 100, 2) . "%). Hacim optimizasyonu (+%5) uygulandı.\n";
            
            $db->prepare("INSERT INTO user_calibration_log (user_id, movement_pattern, base_coefficient, adjusted_coefficient, reason) VALUES (:uid, 'system', 1.0, 1.05, 'Haftalık Adaptasyon: Kilo stabil (+1 tekrar)')")->execute(['uid' => $userId]);
        }
    }

    // 5. Automatic Retry for Failed AI Skeleton Programs (up to 6 retries)
    if ($latestProgram['generation_source'] === 'fallback_template') {
        $retryCount = (int)$latestProgram['generation_retry_count'];
        if ($retryCount < Constants::MAX_AI_RETRIES) {
            echo "  -> Başarısız AI programı yeniden deneniyor (Deneme {$retryCount} / " . Constants::MAX_AI_RETRIES . ")...\n";
            try {
                $newProgId = $geminiService->generateWeeklyProgram($userId, $user['fitness_level'] ?? 'beginner');
                echo "  -> Gemini AI başarıyla yeni program üretti (#{$newProgId}).\n";
            } catch (\Throwable $e) {
                $stmtInc = $db->prepare("
                    UPDATE user_programs 
                    SET generation_retry_count = generation_retry_count + 1 
                    WHERE id = :pid
                ");
                $stmtInc->execute(['pid' => $programId]);
                echo "  -> AI çağrısı tekrar başarısız oldu: " . $e->getMessage() . "\n";
            }
        } else {
            echo "  -> Maksimum deneme sınırına (6) ulaşıldı. Otomatik deneme durduruldu, UI'da buton hazır.\n";
        }
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Nightly sync tamamlandı.\n";
