<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Models\WorkoutLog;
use App\Services\HypertrophyEngine;
use App\Services\GeminiWorkoutService;

class ApiController extends Controller
{
    private WorkoutLog $logModel;
    private HypertrophyEngine $hypertrophyEngine;
    private GeminiWorkoutService $geminiWorkoutService;

    public function __construct()
    {
        parent::__construct();
        $this->logModel = new WorkoutLog();
        $this->hypertrophyEngine = new HypertrophyEngine();
        $this->geminiWorkoutService = new GeminiWorkoutService();
    }

    /**
     * Set logging AJAX endpoint
     * Rate limited to max 3 requests per minute
     */
    public function logSet(): void
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            $this->jsonResponse(['success' => false, 'error' => 'Oturum süreniz dolmuş.'], 401);
        }

        $userId = (int)$user['id'];

        // 1. Rate Limiting: Max 3 set logs per minute
        if (!Security::checkSetLogRateLimit($userId)) {
            $this->jsonResponse([
                'success' => false,
                'error'   => 'Lütfen setler arasında acele etmeyin (Dakikada en fazla 3 set kaydedilebilir).'
            ], 429);
        }

        // 2. CSRF Verification
        Security::verifyRequestCsrf();

        // 3. Payload & Validation
        $sessionId = (int)($_POST['session_id'] ?? 0);
        $exerciseId = (int)($_POST['exercise_id'] ?? 0);
        $setNumber = (int)($_POST['set_number'] ?? 1);
        $setType = (string)($_POST['set_type'] ?? 'reps');
        $targetWeight = isset($_POST['target_weight']) ? (float)$_POST['target_weight'] : null;
        $actualWeight = (float)($_POST['actual_weight'] ?? 0.0);
        $targetReps = isset($_POST['target_reps']) ? (int)$_POST['target_reps'] : 10;
        $actualReps = isset($_POST['actual_reps']) && $_POST['actual_reps'] !== '' ? (int)$_POST['actual_reps'] : null;
        $durationSeconds = isset($_POST['duration_seconds']) && $_POST['duration_seconds'] !== '' ? (int)$_POST['duration_seconds'] : null;
        $rirBucket = (string)($_POST['rir_bucket'] ?? '1-2');
        $formBreakdown = !empty($_POST['form_breakdown']) && ($_POST['form_breakdown'] === '1' || $_POST['form_breakdown'] === 'true');

        if (!in_array($rirBucket, ['0', '1-2', '3+'], true)) {
            $rirBucket = '1-2';
        }

        // 4. IDOR Guard: Verify ownership of workout session
        Security::enforceOwnership('workout_sessions', $sessionId, $userId);

        // 5. Persist Log to database
        $logId = $this->logModel->logSet([
            'user_id'          => $userId,
            'session_id'       => $sessionId,
            'exercise_id'      => $exerciseId,
            'set_number'       => $setNumber,
            'set_type'         => $setType,
            'target_weight'    => $targetWeight,
            'actual_weight'    => $actualWeight,
            'target_reps'      => $targetReps,
            'actual_reps'      => $actualReps,
            'duration_seconds' => $durationSeconds,
            'rpe'              => ($rirBucket === '0' ? 10 : ($rirBucket === '1-2' ? 8 : 6)),
            'rir_bucket'       => $rirBucket,
            'form_breakdown'   => $formBreakdown ? 1 : 0
        ]);

        // 6. Execute Native Dynamic Hypertrophy Engine (< 0.05 seconds)
        $recommendation = $this->hypertrophyEngine->computeNextSet(
            $userId,
            $sessionId,
            $exerciseId,
            $setNumber,
            $actualWeight,
            $actualReps,
            $rirBucket,
            $formBreakdown,
            $targetReps
        );

        $this->jsonResponse([
            'success'                => true,
            'log_id'                 => $logId,
            'set_number'             => $setNumber,
            'direction'              => $recommendation['direction'],
            'next_weight'            => $recommendation['next_weight'],
            'rest_extension_seconds' => $recommendation['rest_extension_seconds'],
            'is_locked'              => $recommendation['is_locked'],
            'reason'                 => $recommendation['reason'],
            'plate_recipe'           => $recommendation['plate_recipe'],
            'plate_details'          => $recommendation['plate_details']
        ]);
    }

    /**
     * Manually retry AI program generation
     */
    public function retryProgram(): void
    {
        $user = $this->requireAuth();
        Security::verifyRequestCsrf();
        $userId = (int)$user['id'];
        $fitnessLevel = $user['fitness_level'] ?? 'beginner';

        try {
            $this->geminiWorkoutService->generateWeeklyProgram($userId, $fitnessLevel);
            $this->setFlash('success', 'Antrenman programınız Gemini AI tarafından yeniden optimize edildi.');
        } catch (\Throwable $e) {
            $this->setFlash('danger', 'Program yenilenirken bir hata oluştu: ' . $e->getMessage());
        }

        $this->redirect('/workout/dashboard');
    }
}
