<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Constants;
use App\Models\WorkoutLog;
use App\Models\Equipment;
use App\Models\Exercise;

/**
 * Dynamic Hypertrophy Engine
 * Single Authority RIR + Epley Formula + Combined Ceiling min(10%, +2.5kg) + Session +20% Ceiling
 * Ultra-fast native PHP execution (< 0.05s)
 */
class HypertrophyEngine
{
    private WorkoutLog $workoutLogModel;
    private PlateSolverService $plateSolver;
    private Equipment $equipmentModel;
    private Exercise $exerciseModel;

    public function __construct(
        ?WorkoutLog $workoutLogModel = null,
        ?PlateSolverService $plateSolver = null,
        ?Equipment $equipmentModel = null,
        ?Exercise $exerciseModel = null
    ) {
        $this->workoutLogModel = $workoutLogModel ?? new WorkoutLog();
        $this->plateSolver = $plateSolver ?? new PlateSolverService();
        $this->equipmentModel = $equipmentModel ?? new Equipment();
        $this->exerciseModel = $exerciseModel ?? new Exercise();
    }

    /**
     * Compute next set recommendation
     *
     * @param int $userId
     * @param int $sessionId
     * @param int $exerciseId
     * @param int $setNumber
     * @param float $actualWeight
     * @param int|null $actualReps
     * @param string $rirBucket ('0', '1-2', '3+')
     * @param bool $formBreakdown
     * @param int|null $targetReps
     * @return array
     */
    public function computeNextSet(
        int $userId,
        int $sessionId,
        int $exerciseId,
        int $setNumber,
        float $actualWeight,
        ?int $actualReps,
        string $rirBucket,
        bool $formBreakdown,
        ?int $targetReps = 10
    ): array {
        $reps = $actualReps ?? ($targetReps ?: 10);
        $direction = 'MAINTAIN';
        $restExtension = 0;
        $isLocked = false;
        $reason = '';
        $nextWeight = $actualWeight;

        // 1. DIRECTION DECISION: Single Authority RIR & Form Breakdown
        if ($formBreakdown) {
            // Emergency Safety: Drop weight immediately by 15%, advise tempo control
            $direction = 'DECREASE';
            if ($actualWeight > 0.0) {
                $dropAmount = round($actualWeight * Constants::FORM_BREAKDOWN_DROP_PCT, 2);
                $nextWeight = max(0.0, round($actualWeight - $dropAmount, 2));
                $reason = "⚠️ Form bozulması tespit edildi! Güvenlik gereği ağırlık %15 düşürüldü (-{$dropAmount} kg). Eksantrik fazı (iniş) 3 saniyede kontrollü tamamlayın.";
            } else {
                $nextWeight = 0.0;
                $reason = "⚠️ Form bozulması tespit edildi! Güvenlik gereği bir sonraki sette hedef tekrarı 2-3 tekrar düşürün ve eksantrik (iniş) fazını 3 saniyede kontrollü tamamlayın.";
            }
        } elseif ($rirBucket === '0') {
            // RIR = 0 (Failure)
            if ($actualReps < $targetReps) {
                // Failed to hit target reps: Drop weight
                $direction = 'DECREASE';
                if ($actualWeight > 0.0) {
                    $dropAmount = round($actualWeight * Constants::RIR0_CONSECUTIVE_DROP_PCT, 2);
                    $nextWeight = max(0.0, round($actualWeight - $dropAmount, 2));
                    $reason = "Hedef tekrara ulaşılamadı (Tükeniş). Toparlanma için ağırlık düşürüldü (-{$dropAmount} kg).";
                } else {
                    $nextWeight = 0.0;
                    $reason = "Hedef tekrara ulaşılamadı (Tükeniş). Sonraki sette tekrar sayısını azaltın.";
                }
            } else {
                // Hit target reps but RIR 0 (Max effort): Maintain weight, extend rest time by 30 seconds
                $direction = 'MAINTAIN';
                $restExtension = Constants::RIR0_REST_EXTENSION_SECONDS;
                $nextWeight = $actualWeight;
                $reason = "Tükeniş seti (RIR 0) fakat hedef tekrar tamamlandı. Ağırlık korundu, toparlanma için dinlenme süresi +30 sn uzatıldı.";
            }
        } elseif ($rirBucket === '1-2') {
            // Ideal Hypertrophy Window (RIR 1-2)
            $direction = 'MAINTAIN';
            $nextWeight = $actualWeight;
            $reason = "🎯 İdeal hipertrofi uyarım penceresindesiniz (RIR 1-2). Mevcut ağırlığı koruyarak mekanik gerilimi sürdürün.";
        } elseif ($rirBucket === '3+') {
            // Underloaded: Needs increase
            $direction = 'INCREASE';

            if ($actualWeight <= 0.0) {
                // Calisthenics / Bodyweight: Introduce +2.5 kg external resistance
                $step = 2.5;
                $candidateNextWeight = 2.5;
                $reason = "Vücut ağırlığı hafif geldi (RIR 3+). Dinamik hipertrofi aşırı yüklenmesi (progressive overload) için sonraki sete +2.5 kg harici direnç (dambıl / ağırlık yeleği) eklendi.";
                $nextWeight = $candidateNextWeight;
            } else {
                // Lifter had at least 3 reps in reserve: true failure rep capacity is reps + 3
                $effectiveReps = $reps + 3;
                $epley1RM = $actualWeight * (1.0 + ($effectiveReps / 30.0));

                $targetRepLookup = min(15, max(1, $targetReps ?: $reps));
                $nscaPct = Constants::NSCA_PERCENTAGE_TABLE[$targetRepLookup] ?? 0.75;
                $calculatedTargetWeight = round($epley1RM * $nscaPct, 2);
                $rawIncrease = max(1.25, round($calculatedTargetWeight - $actualWeight, 2));

                // 2. COMBINED CEILING RULE: min(actual_weight * 0.10, 2.5 kg)
                $pctCeiling = round($actualWeight * Constants::MAX_SINGLE_SET_PCT, 2);
                $maxStepAllowed = min($pctCeiling, Constants::MAX_SINGLE_SET_KG);

                // Enforce minimum step if ceiling allows, but never exceed maxStepAllowed
                $step = min($rawIncrease, $maxStepAllowed);
                if ($step < 0.5 && $maxStepAllowed >= 0.5) {
                    $step = min(1.25, $maxStepAllowed);
                }

                $candidateNextWeight = round($actualWeight + $step, 2);

                // 3. SESSION BASELINE & +20% SAFETY CEILING
                $firstSetWeight = $this->workoutLogModel->getFirstSetWeight($sessionId, $exerciseId);
                if ($firstSetWeight === null || $firstSetWeight <= 0.0) {
                    // No working sets yet, so no session ceiling applies
                    $maxSessionWeight = 9999.0;
                } else {
                    $maxSessionWeight = round($firstSetWeight * (1.0 + Constants::MAX_SESSION_INCREASE_PCT), 2);
                }

                $sessionIncreases = $this->workoutLogModel->getSessionIncreasesCount($sessionId, $exerciseId);

                if ($sessionIncreases >= Constants::MAX_SESSION_INCREASES_COUNT) {
                    $isLocked = true;
                    $nextWeight = $actualWeight;
                    $reason = "Bu oturumda maksimum artış sayısına (3 kez) ulaşıldı. Ağırlık kilitlendi, set içi tekrar sayısını artırmaya odaklanın.";
                    $direction = 'MAINTAIN';
                } elseif ($candidateNextWeight > $maxSessionWeight) {
                    $isLocked = true;
                    $nextWeight = $maxSessionWeight;
                    $reason = "Oturum güvenlik tavanına (+%20) ulaşıldı ({$maxSessionWeight} kg). Ağırlık kilitlendi, tekrar sayısını zorlayın.";
                } else {
                    $nextWeight = $candidateNextWeight;
                    $reason = "Yük hafif geldi (RIR 3+). Epley & NSCA formülüyle tek setlik tavan sınırında (+{$step} kg) güvenli artış yapıldı.";
                }
            }
        }

        // 4. PLATE SOLVER INTEGRATION
        $exercise = $this->exerciseModel->findById($exerciseId);
        $userEquipment = $this->equipmentModel->getByUserId($userId);

        $isBarbell = false;
        $barWeight = 0.0;
        $availablePlates = [];

        if ($userEquipment) {
            $availablePlates = $userEquipment['plates_array'] ?? [];
            if ($exercise && $exercise['required_equipment'] === 'barbell') {
                $isBarbell = true;
                $barWeight = (float)($userEquipment['barbell_weight_kg'] ?? 20.0);
            } elseif ($exercise && $exercise['required_equipment'] === 'dumbbell') {
                $isBarbell = false;
                $barWeight = (float)($userEquipment['dumbbell_handle_weight_kg'] ?? 2.5);
            }
        }

        $plateResult = null;
        if (!empty($availablePlates) && $barWeight > 0.0) {
            $plateResult = $this->plateSolver->solve(
                $nextWeight,
                $isBarbell,
                $availablePlates,
                $barWeight
            );

            // If direction was INCREASE but plate solver rounded down to <= actualWeight,
            // check if closest achievable plate load above actualWeight is within safe single-set limit (+2.5 kg)
            if ($direction === 'INCREASE' && $plateResult['feasible_weight'] <= $actualWeight && $nextWeight > $actualWeight) {
                $bumpTarget = $actualWeight + 2.5;
                $bumpResult = $this->plateSolver->solve($bumpTarget, $isBarbell, $availablePlates, $barWeight);
                if ($bumpResult['feasible_weight'] > $actualWeight && ($bumpResult['feasible_weight'] - $actualWeight) <= (Constants::MAX_SINGLE_SET_KG + 0.01)) {
                    $plateResult = $bumpResult;
                    $actualBump = round($plateResult['feasible_weight'] - $actualWeight, 2);
                    $reason = "Yük hafif geldi (RIR 3+). Plaka envanterinizle kurulabilecek en yakın güvenli artış (+{$actualBump} kg) uygulandı.";
                }
            }

            // Snap to physically achievable load, guaranteeing MAINTAIN and INCREASE never decrease weight
            if (($direction === 'MAINTAIN' || $direction === 'INCREASE') && $actualWeight > 0.0 && $plateResult['feasible_weight'] < $actualWeight) {
                $nextWeight = $actualWeight;
            } else {
                $nextWeight = $plateResult['feasible_weight'];
            }
        }

        return [
            'direction'              => $direction,
            'next_weight'            => $nextWeight,
            'rest_extension_seconds' => $restExtension,
            'is_locked'              => $isLocked,
            'reason'                 => $reason,
            'plate_recipe'           => $plateResult['recipe'] ?? "Vücut Ağırlığı / Standart Ekipman",
            'plate_details'          => $plateResult
        ];
    }
}
