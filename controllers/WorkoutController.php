<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Models\WorkoutSession;
use App\Models\WorkoutLog;
use App\Models\Equipment;
use App\Services\PlateSolverService;
use App\Services\InjuryFilterService;
use App\Services\HypertrophyEngine;
use App\Config\Database;
use PDO;

class WorkoutController extends Controller
{
    private WorkoutSession $sessionModel;
    private WorkoutLog $logModel;
    private Equipment $equipmentModel;
    private PlateSolverService $plateSolver;
    private InjuryFilterService $injuryFilter;
    private HypertrophyEngine $hypertrophyEngine;
    private PDO $db;

    public function __construct()
    {
        parent::__construct();
        $this->sessionModel = new WorkoutSession();
        $this->logModel = new WorkoutLog();
        $this->equipmentModel = new Equipment();
        $this->plateSolver = new PlateSolverService();
        $this->injuryFilter = new InjuryFilterService();
        $this->hypertrophyEngine = new HypertrophyEngine();
        $this->db = Database::getConnection();
    }

    public function dashboard(): void
    {
        $user = $this->requireAuth();
        $userId = (int)$user['id'];

        // Get latest user program or auto-create if missing
        $stmtProg = $this->db->prepare("
            SELECT * FROM user_programs 
            WHERE user_id = :uid 
            ORDER BY id DESC LIMIT 1
        ");
        $stmtProg->execute(['uid' => $userId]);
        $program = $stmtProg->fetch();

        if (!$program) {
            $geminiService = new \App\Services\GeminiWorkoutService();
            $geminiService->generateWeeklyProgram($userId, $user['fitness_level'] ?? 'beginner', true);

            $stmtProg->execute(['uid' => $userId]);
            $program = $stmtProg->fetch();
        }

        $programDays = [];
        if ($program) {
            $stmtDays = $this->db->prepare("
                SELECT pd.* 
                FROM program_days pd 
                WHERE pd.program_id = :pid 
                ORDER BY pd.day_of_week ASC
            ");
            $stmtDays->execute(['pid' => $program['id']]);
            $rawDays = $stmtDays->fetchAll();

            foreach ($rawDays as $d) {
                // Fetch exercises for each day
                $stmtEx = $this->db->prepare("
                    SELECT pe.*, e.name as exercise_name, e.movement_pattern, e.primary_muscle_group, e.required_equipment
                    FROM program_exercises pe
                    JOIN exercises e ON pe.exercise_id = e.id
                    WHERE pe.program_day_id = :did
                    ORDER BY pe.order_in_session ASC
                ");
                $stmtEx->execute(['did' => $d['id']]);
                $d['exercises'] = $stmtEx->fetchAll();
                $d['exercise_count'] = count($d['exercises']);
                $programDays[] = $d;
            }
        }

        // Check active session
        $activeSession = $this->sessionModel->getActiveSession($userId);

        // Fetch user weight & biometrics
        $latestWeight = (float)($user['current_weight_kg'] ?? 75.0);
        $stmtW = $this->db->prepare("SELECT weight_kg FROM daily_weigh_ins WHERE user_id = :uid ORDER BY logged_date DESC LIMIT 1");
        $stmtW->execute(['uid' => $userId]);
        $wRow = $stmtW->fetch();
        if ($wRow) {
            $latestWeight = (float)$wRow['weight_kg'];
        }

        // Calculate BMI
        $heightM = (float)($user['height_cm'] ?? 175.0) / 100.0;
        $bmi = ($heightM > 0) ? round($latestWeight / ($heightM * $heightM), 1) : 22.5;

        // Calculate RFI Baselines
        $rfiService = new \App\Services\RfiService();
        $baselines = $rfiService->calculateBaselineLoads($latestWeight, 12, 5, 20, 45);

        // User active injuries
        $activeInjuries = $this->injuryFilter->getActiveInjuredJoints($userId);

        // Recent completed sessions
        $stmtPast = $this->db->prepare("
            SELECT ws.*, pd.focus_area,
                   COUNT(wl.id) as total_sets,
                   SUM(wl.actual_weight * COALESCE(wl.actual_reps, 1)) as total_volume
            FROM workout_sessions ws
            LEFT JOIN program_days pd ON ws.program_day_id = pd.id
            LEFT JOIN workout_logs wl ON ws.id = wl.session_id
            WHERE ws.user_id = :uid AND ws.ended_at IS NOT NULL
            GROUP BY ws.id
            ORDER BY ws.id DESC
            LIMIT 5
        ");
        $stmtPast->execute(['uid' => $userId]);
        $recentWorkouts = $stmtPast->fetchAll();

        $this->render('workout/dashboard', [
            'pageTitle'      => 'Sporcu & Müşteri Paneli — Akıllı Hipertrofi',
            'user'           => $user,
            'program'        => $program,
            'programDays'    => $programDays,
            'activeSession'  => $activeSession,
            'recentWorkouts' => $recentWorkouts,
            'latestWeight'   => $latestWeight,
            'bmi'            => $bmi,
            'baselines'      => $baselines,
            'activeInjuries' => $activeInjuries
        ]);
    }

    public function liveSession(?string $id = null): void
    {
        $user = $this->requireAuth();
        $userId = (int)$user['id'];
        $rawDayId = $id ?? ($_GET['day'] ?? null);
        $numericDayId = $rawDayId !== null ? (int)$rawDayId : null;

        // 1. Ensure user has a weekly program
        $stmtProg = $this->db->prepare("SELECT id FROM user_programs WHERE user_id = :uid ORDER BY id DESC LIMIT 1");
        $stmtProg->execute(['uid' => $userId]);
        $latestProg = $stmtProg->fetch();
        if (!$latestProg) {
            $geminiService = new \App\Services\GeminiWorkoutService();
            $geminiService->generateWeeklyProgram($userId, $user['fitness_level'] ?? 'beginner', true);
            $stmtProg->execute(['uid' => $userId]);
            $latestProg = $stmtProg->fetch();
        }

        // 2. Fetch all program days for switching tabs
        $stmtAllDays = $this->db->prepare("
            SELECT pd.*, 
                   (SELECT COUNT(*) FROM program_exercises WHERE program_day_id = pd.id) as ex_count
            FROM program_days pd
            WHERE pd.program_id = :pid
            ORDER BY pd.day_of_week ASC
        ");
        $stmtAllDays->execute(['pid' => $latestProg['id']]);
        $allProgramDays = $stmtAllDays->fetchAll();

        // 3. Resolve active day ID
        $programDayId = $numericDayId;
        $activeSession = $this->sessionModel->getActiveSession($userId);

        if ($numericDayId !== null) {
            // IDOR Protection: Verify that the requested day belongs to user's active program
            $validDayIds = array_map(fn($d) => (int)$d['id'], $allProgramDays);
            if (!in_array($numericDayId, $validDayIds, true)) {
                $this->setFlash('danger', 'İstenen antrenman günü programınızda bulunamadı.');
                $this->redirect('/workout/dashboard');
                return;
            }

            // User explicitly requested a specific day: update session or start new
            if ($activeSession) {
                if ((int)$activeSession['program_day_id'] !== $numericDayId) {
                    $this->sessionModel->update((int)$activeSession['id'], ['program_day_id' => $numericDayId]);
                    $activeSession['program_day_id'] = $numericDayId;
                }
            } else {
                $sessionId = $this->sessionModel->startSession($userId, $numericDayId);
                $activeSession = $this->sessionModel->findById($sessionId);
            }
            $programDayId = $numericDayId;
        } else {
            // Auto resolve day
            if ($activeSession && !empty($activeSession['program_day_id'])) {
                $programDayId = (int)$activeSession['program_day_id'];
            } else {
                // Match today's day of week or pick first day
                $matchedDay = null;
                if (!empty($allProgramDays)) {
                    $dowToday = (int)date('N');
                    foreach ($allProgramDays as $pd) {
                        if ((int)$pd['day_of_week'] === $dowToday) {
                            $matchedDay = $pd;
                            break;
                        }
                    }
                    if (!$matchedDay) {
                        $matchedDay = $allProgramDays[0];
                    }
                }

                $programDayId = $matchedDay ? (int)$matchedDay['id'] : null;

                if ($activeSession && $programDayId) {
                    $this->sessionModel->update((int)$activeSession['id'], ['program_day_id' => $programDayId]);
                    $activeSession['program_day_id'] = $programDayId;
                } elseif (!$activeSession && $programDayId) {
                    $sessionId = $this->sessionModel->startSession($userId, $programDayId);
                    $activeSession = $this->sessionModel->findById($sessionId);
                }
            }
        }

        $sessionId = $activeSession ? (int)$activeSession['id'] : 0;

        // 4. Fetch Day Exercises
        $dayExercises = [];
        $dayInfo = null;

        if ($programDayId) {
            $stmtDay = $this->db->prepare("SELECT * FROM program_days WHERE id = :id LIMIT 1");
            $stmtDay->execute(['id' => $programDayId]);
            $dayInfo = $stmtDay->fetch();

            $stmtEx = $this->db->prepare("
                SELECT pe.*, e.name as exercise_name, e.movement_pattern, e.required_equipment,
                       e.primary_muscle_group, e.primary_joint_stress
                FROM program_exercises pe
                JOIN exercises e ON pe.exercise_id = e.id
                WHERE pe.program_day_id = :did
                ORDER BY pe.order_in_session ASC
            ");
            $stmtEx->execute(['did' => $programDayId]);
            $dayExercises = $stmtEx->fetchAll();
        }

        // Apply injury filter if user has active joint complaints
        $equipment = $this->equipmentModel->getByUserId($userId);
        $location = $equipment['workout_location'] ?? 'home_weights';
        $allowedEquipment = ['bodyweight'];
        if ($location === 'commercial_gym') {
            $allowedEquipment = ['bodyweight', 'barbell', 'dumbbell', 'machine', 'cable'];
        } else {
            if (!empty($equipment['has_barbell'])) $allowedEquipment[] = 'barbell';
            if (!empty($equipment['has_dumbbells'])) $allowedEquipment[] = 'dumbbell';
        }
        $dayExercises = $this->injuryFilter->filterProgramExercises($userId, $dayExercises, $allowedEquipment);

        // User equipment for plate calculations
        $plates = $equipment['plates_array'] ?? [];
        $barbellKg = (float)($equipment['barbell_weight_kg'] ?? 20.0);
        $dumbbellKg = (float)($equipment['dumbbell_handle_weight_kg'] ?? 2.5);

        // Fetch completed logs for this session
        $logs = $this->logModel->getLogsForSession($sessionId);
        $logsByExercise = [];
        foreach ($logs as $l) {
            $logsByExercise[$l['exercise_id']][] = $l;
        }

        // Compute starting plate recommendation and adapted weights for each exercise
        foreach ($dayExercises as &$ex) {
            $eid = (int)$ex['exercise_id'];
            $exLogs = $logsByExercise[$eid] ?? [];
            $completedCount = count($exLogs);
            $ex['completed_sets'] = $completedCount;
            $ex['logs'] = array_values($exLogs);

            $isBarbell = ($ex['required_equipment'] === 'barbell');
            $isDumbbell = ($ex['required_equipment'] === 'dumbbell');
            $barWeight = $isBarbell ? $barbellKg : ($isDumbbell ? $dumbbellKg : 0.0);

            $lastEngineResult = null;
            if (!empty($exLogs)) {
                $lastLog = end($exLogs);
                $engineRec = $this->hypertrophyEngine->computeNextSet(
                    $userId,
                    $sessionId,
                    $eid,
                    (int)$lastLog['set_number'],
                    (float)$lastLog['actual_weight'],
                    $lastLog['actual_reps'] !== null ? (int)$lastLog['actual_reps'] : null,
                    (string)$lastLog['rir_bucket'],
                    !empty($lastLog['form_breakdown']),
                    (int)($lastLog['target_reps'] ?? 10)
                );
                $initialWeight = (float)$engineRec['next_weight'];
                $plateRecipe = $engineRec['plate_recipe'];
                $lastEngineResult = $engineRec;
            } else {
                $initialWeight = $barWeight > 0.0 ? $barWeight : 0.0;
                $plateRecipe = "Vücut Ağırlığı / Standart Ekipman";
                if ($barWeight > 0.0 && !empty($plates)) {
                    $solved = $this->plateSolver->solve($initialWeight, $isBarbell, $plates, $barWeight);
                    $plateRecipe = $solved['recipe'];
                    $initialWeight = $solved['feasible_weight'];
                }
            }

            $ex['current_weight'] = $initialWeight;
            $ex['plate_recipe'] = $plateRecipe;
            $ex['next_set_number'] = $completedCount + 1;
            $ex['last_engine_result'] = $lastEngineResult;
        }
        unset($ex);

        $this->render('workout/live_session', [
            'pageTitle'      => 'Canlı Antrenman Oturumu — Akıllı Hipertrofi',
            'user'           => $user,
            'session'        => $activeSession,
            'dayInfo'        => $dayInfo,
            'exercises'      => $dayExercises,
            'equipment'      => $equipment,
            'logsByExercise' => $logsByExercise,
            'allProgramDays' => $allProgramDays,
            'currentDayId'   => $programDayId
        ]);
    }

    public function endSession(): void
    {
        $user = $this->requireAuth();
        Security::verifyRequestCsrf();

        $userId = (int)$user['id'];
        $sessionId = (int)($_POST['session_id'] ?? 0);

        Security::enforceOwnership('workout_sessions', $sessionId, $userId);

        $this->sessionModel->endSession($sessionId, $userId);
        $summary = $this->sessionModel->getSessionSummary($sessionId, $userId);

        $totalSets = $summary['total_sets_completed'] ?? 0;
        $volume = round((float)($summary['total_volume_kg'] ?? 0), 1);

        $this->setFlash('success', "Harika iş! Antrenman başarıyla tamamlandı. Toplam {$totalSets} set ve {$volume} kg hacim kaydettiniz!");
        $this->redirect('/workout/summary?session_id=' . $sessionId);
    }

    public function summary(): void
    {
        $user = $this->requireAuth();
        $sessionId = (int)($_GET['session_id'] ?? 0);
        if ($sessionId <= 0) {
            $this->redirect('/workout/dashboard');
        }
        
        Security::enforceOwnership('workout_sessions', $sessionId, (int)$user['id']);
        $summary = $this->sessionModel->getSessionSummary($sessionId, (int)$user['id']);
        
        $this->render('workout/summary', [
            'pageTitle' => 'Antrenman Özeti',
            'user' => $user,
            'sessionId' => $sessionId,
            'summary' => $summary
        ]);
    }

    public function createCustomProgram(): void
    {
        $user = $this->requireAuth();
        Security::verifyRequestCsrf();
        $userId = (int)$user['id'];

        $programType = trim($_POST['program_type'] ?? 'split_upper_lower');
        if (!in_array($programType, ['full_body', 'split_upper_lower', 'split_ppl'], true)) {
            $programType = 'full_body';
        }

        $workoutLocation = trim($_POST['workout_location'] ?? 'home_weights');

        // Update equipment location
        $equipment = $this->equipmentModel->getByUserId($userId);
        if ($equipment) {
            $this->equipmentModel->update((int)$equipment['id'], ['workout_location' => $workoutLocation]);
        }

        // Handle injury reports if reported
        $injuries = $_POST['injuries'] ?? [];
        if (is_array($injuries) && !empty($injuries)) {
            $today = date('Y-m-d');
            $stmtInj = $this->db->prepare("
                INSERT INTO injury_reports (user_id, joint_area, severity, reported_date, notes)
                VALUES (:uid, :joint, 'mild', :rdate, 'Kullanıcı program sihirbazında bildirdi')
            ");
            $allowedJoints = ['shoulder', 'elbow', 'lower_back', 'knee', 'wrist', 'neck', 'other'];
            foreach ($injuries as $j) {
                if (in_array($j, $allowedJoints, true)) {
                    $stmtInj->execute([
                        'uid'   => $userId,
                        'joint' => $j,
                        'rdate' => $today
                    ]);
                }
            }
        }

        // Generate tailored program
        $gemini = new \App\Services\GeminiWorkoutService();
        $fitnessLevel = $user['fitness_level'] ?? 'intermediate';
        $gemini->generateWeeklyProgram($userId, $fitnessLevel, true);

        // Terminate any old hanging session so new day starts fresh
        $now = date('Y-m-d H:i:s');
        $this->db->exec("UPDATE workout_sessions SET ended_at = '{$now}' WHERE user_id = {$userId} AND ended_at IS NULL");

        $this->setFlash('success', '🎯 Yeni antrenman programınız başarıyla oluşturuldu ve haftalık takviminize atandı!');
        $this->redirect('/workout/dashboard');
    }
}