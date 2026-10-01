<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Constants;
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
     * Determine user fitness tier and regression assignments based on multi-parametric onboarding test
     * Evaluates 4 core functional pillars: Push (Şınav), Pull (Barfiks), Legs (Squat), Core (Mekik/Plank)
     */
    public function evaluateFitnessTier(int $pushups, int $chinups, int $squats, int $corePlankSec = 30): array
    {
        $pushScore = $this->calculateScoreFromThresholds($pushups, [
            [30, 100.0],
            [20, 75.0],
            [10, 50.0],
            [3, 20.0],
            [0, 0.0]
        ]);

        $pullScore = $this->calculateScoreFromThresholds($chinups, [
            [15, 100.0],
            [8, 75.0],
            [4, 50.0],
            [1, 20.0],
            [0, 0.0]
        ]);

        $legScore = $this->calculateScoreFromThresholds($squats, [
            [50, 100.0],
            [30, 75.0],
            [15, 50.0],
            [5, 20.0],
            [0, 0.0]
        ]);

        $coreScore = $this->calculateScoreFromThresholds($corePlankSec, [
            [120, 100.0],
            [75, 75.0],
            [40, 50.0],
            [20, 20.0],
            [0, 0.0]
        ]);

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

    /**
     * Helper method to interpolate scores based on an array of thresholds.
     * Thresholds should be sorted in descending order of input value.
     * Each threshold is an array [inputValue, scoreValue].
     */
    private function calculateScoreFromThresholds(int $value, array $thresholds): float
    {
        $prevInput = null;
        $prevScore = null;

        foreach ($thresholds as $index => $point) {
            $currentInput = $point[0];
            $currentScore = $point[1];

            if ($value >= $currentInput) {
                if ($index === 0) {
                    return $currentScore;
                }

                $inputDiff = $prevInput - $currentInput;
                $scoreDiff = $prevScore - $currentScore;

                return $currentScore + (($value - $currentInput) / $inputDiff) * $scoreDiff;
            }

            $prevInput = $currentInput;
            $prevScore = $currentScore;
        }

        return 0.0;
    }
}