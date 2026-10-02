<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Models\User;
use PDO;

/**
 * Relative Force Index (RFI) Service
 * Calisthenic strength baseline calculations, regression chains & calibration
 */
class RfiService
{
    private ?PDO $db;
    private ?User $userModel;

    public function __construct(?PDO $db = null, ?User $userModel = null)
    {
        $this->db = $db;
        $this->userModel = $userModel;
    }

    private function getDb(): PDO
    {
        if ($this->db === null) {
            $this->db = Database::getConnection();
        }
        return $this->db;
    }

    /**
     * Calculate baseline loads for all 6 movement patterns
     *
     * @param float $bodyweightKg
     * @param int $pushups Max unbroken push-up count
     * @param int $chinups Max unbroken chin-up count
     * @param int $squats Max unbroken bodyweight squats count
     * @param int $plankSec Max front plank hold in seconds
     * @return array
     */
    public function calculateBaselineLoads(
        float $bodyweightKg,
        int $pushups,
        int $chinups,
        int $squats,
        int $plankSec = 30
    ): array {
        // Formulas strictly from Master Prompt:
        // Yatay İtiş: Kilo * 0.30 + (Sinav * 0.25)
        $pushHorizontal = round(($bodyweightKg * 0.30) + ($pushups * 0.25), 2);

        // Çekiş: Kilo * 0.35 + (ChinUp * 0.60)
        $pull = round(($bodyweightKg * 0.35) + ($chinups * 0.60), 2);

        // Kol İzolasyon: Kilo * 0.18 + (ChinUp * 0.45)
        $armIsolation = round(($bodyweightKg * 0.18) + ($chinups * 0.45), 2);

        // Bacak Squat: Kilo * 0.50 + (Squat * 0.20)
        $legSquat = round(($bodyweightKg * 0.50) + ($squats * 0.20), 2);

        // Kalça Hinge: Kilo * 0.40 + (ChinUp * 0.30)
        $hipHinge = round(($bodyweightKg * 0.40) + ($chinups * 0.30), 2);

        // Omuz: Kilo * 0.08 + (Sinav * 0.10)
        $shoulder = round(($bodyweightKg * 0.08) + ($pushups * 0.10), 2);

        // Push Vertical: Kilo * 0.20 + (Sinav * 0.15)
        $pushVertical = round(($bodyweightKg * 0.20) + ($pushups * 0.15), 2);

        return [
            'push_horizontal'    => $pushHorizontal,
            'pull_horizontal'    => $pull,
            'pull_vertical'      => $pull,
            'isolation_arm'      => $armIsolation,
            'squat'              => $legSquat,
            'hinge'              => $hipHinge,
            'isolation_shoulder' => $shoulder,
            'push_vertical'      => $pushVertical,
            'core'               => max(20, $plankSec) // Duration based
        ];
    }

    /**
     * Helper to calculate component score based on defined thresholds
     * Thresholds array format: [ t4, t3, t2, t1 ]
     * where value >= t4 gives 100,
     * value >= t3 interpolates between 75 and 100,
     * value >= t2 interpolates between 50 and 75,
     * value >= t1 interpolates between 20 and 50,
     * otherwise interpolates between 0 and 20.
     */
    private function calculateScore(int $value, array $thresholds): float
    {
        [$t4, $t3, $t2, $t1] = $thresholds;

        if ($value >= $t4) {
            return 100.0;
        } elseif ($value >= $t3) {
            return 75.0 + (($value - $t3) / (float)($t4 - $t3)) * 25.0;
        } elseif ($value >= $t2) {
            return 50.0 + (($value - $t2) / (float)($t3 - $t2)) * 25.0;
        } elseif ($value >= $t1) {
            return 20.0 + (($value - $t1) / (float)($t2 - $t1)) * 30.0;
        } else {
            if ($t1 == 0) return 0.0;
            return ($value / (float)$t1) * 20.0;
        }
    }

    /**
     * Determine user fitness tier and regression assignments based on multi-parametric onboarding test
     * Evaluates 4 core functional pillars: Push (Şınav), Pull (Barfiks), Legs (Squat), Core (Mekik/Plank)
     */
    public function evaluateFitnessTier(int $pushups, int $chinups, int $squats, int $corePlankSec = 30): array
    {
        // 1. Push Score (Şınav - İtiş Kuvveti): 0 - 100
        $pushScore = $this->calculateScore($pushups, [30, 20, 10, 3]);

        // 2. Pull Score (Barfiks / Kapı Çekişi): 0 - 100
        $pullScore = $this->calculateScore($chinups, [15, 8, 4, 1]);

        // 3. Leg Score (Vücut Ağırlığı Squat): 0 - 100
        $legScore = $this->calculateScore($squats, [50, 30, 15, 5]);

        // 4. Core Score (Mekik / Ön Plank Süresi saniye): 0 - 100
        $coreScore = $this->calculateScore($corePlankSec, [120, 75, 40, 20]);

        // Weighted composite conditioning score: Push %25, Pull %25, Leg %25, Core %25
        $compositeScore = round(($pushScore * 0.25) + ($pullScore * 0.25) + ($legScore * 0.25) + ($coreScore * 0.25), 1);

        $regressions = [];
        // Push regression chain
        if ($pushups === 0) {
            $regressions['push_horizontal'] = 'Wall Push-Up';
            $regressions['push_vertical'] = 'Pike Push-Up with Knees';
        } elseif ($pushups < 5) {
            $regressions['push_horizontal'] = 'Knee Push-Up';
        }

        // Pull regression chain
        if ($chinups === 0) {
            $regressions['pull_vertical'] = 'Negative Chin-Up (Slow Eccentric)';
            $regressions['pull_horizontal'] = 'Door Towel Row';
        }

        // Squat regression chain
        if ($squats < 5) {
            $regressions['squat'] = 'Chair Assisted Bodyweight Squat';
        }

        // Core regression chain
        if ($corePlankSec < 25) {
            $regressions['core'] = 'Knee Plank (Duration)';
        }

        // Determine level from specific composite score and foundational thresholds
        if ($compositeScore < 22.0 || ($pushups === 0 && $chinups === 0 && $squats < 5)) {
            $level = 'absolute_beginner';
            $title = 'Mutlak Başlangıç / Regresyon Seviyesi';
        } elseif ($compositeScore < 48.0) {
            $level = 'beginner';
            $title = 'Başlangıç Seviyesi (Temel Kondisyon)';
        } elseif ($compositeScore < 76.0) {
            $level = 'intermediate';
            $title = 'Orta Seviye (Hipertrofi Odaklı)';
        } else {
            $level = 'advanced';
            $title = 'İleri Seviye (Yüksek Atletik Kapasite)';
        }

        return [
            'level'           => $level,
            'title'           => $title,
            'composite_score' => $compositeScore,
            'scores'          => [
                'push' => round($pushScore, 1),
                'pull' => round($pullScore, 1),
                'leg'  => round($legScore, 1),
                'core' => round($coreScore, 1)
            ],
            'regressions'     => $regressions
        ];
    }

    /**
     * Calibrate coefficients after the first 2 workout sessions (within ±15%)
     */
    public function calibrateUserPattern(
        int $userId,
        string $pattern,
        float $baseWeight,
        float $actualAverageWeight,
        string $reason
    ): float {
        $ratio = $actualAverageWeight / max(1.0, $baseWeight);
        // Bound calibration between -15% and +15%
        $boundedFactor = min(1.15, max(0.85, $ratio));
        $adjusted = round($baseWeight * $boundedFactor, 2);

        $stmt = $this->db->prepare("
            INSERT INTO user_calibration_log (user_id, movement_pattern, base_coefficient, adjusted_coefficient, reason)
            VALUES (:uid, :pattern, :base, :adj, :reason)
        ");
        $stmt->execute([
            'uid'     => $userId,
            'pattern' => $pattern,
            'base'    => $baseWeight,
            'adj'     => $adjusted,
            'reason'  => $reason
        ]);

        return $adjusted;
    }
}
