<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Config\Database;
use App\Models\User;
use App\Models\WorkoutSession;
use App\Models\WorkoutLog;
use App\Models\Equipment;
use App\Models\DailyWeighIn;
use App\Services\HypertrophyEngine;
use App\Services\GeminiWorkoutService;
use App\Services\AnalyticsService;
use App\Services\PlateSolverService;
use App\Services\InjuryFilterService;
use App\Services\RfiService;
use PDO;

class ApiController extends Controller
{
    private PDO $db;
    private User $userModel;
    private WorkoutSession $sessionModel;
    private WorkoutLog $logModel;
    private Equipment $equipmentModel;
    private DailyWeighIn $weighInModel;
    private HypertrophyEngine $hypertrophyEngine;
    private GeminiWorkoutService $geminiWorkoutService;
    private AnalyticsService $analyticsService;
    private PlateSolverService $plateSolver;
    private InjuryFilterService $injuryFilter;
    private RfiService $rfiService;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getConnection();
        $this->userModel = new User();
        $this->sessionModel = new WorkoutSession();
        $this->logModel = new WorkoutLog();
        $this->equipmentModel = new Equipment();
        $this->weighInModel = new DailyWeighIn();
        $this->hypertrophyEngine = new HypertrophyEngine();
        $this->geminiWorkoutService = new GeminiWorkoutService();
        $this->analyticsService = new AnalyticsService();
        $this->plateSolver = new PlateSolverService();
        $this->injuryFilter = new InjuryFilterService();
        $this->rfiService = new RfiService($this->db);
    }

    // =========================================================================
    // 1. AUTH & SESSION ENDPOINTS
    // =========================================================================

    /**
     * GET /api/auth/csrf
     * Returns a valid CSRF token for frontend forms & fetch calls
     */
    public function csrf(): void
    {
        $this->jsonResponse([
            'success'    => true,
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    /**
     * POST /api/auth/login
     */
    public function login(): void
    {
        $data = $this->getRequestData();
        $ip = Security::getClientIp();
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $password = (string)($data['password'] ?? '');

        // 1. IP & Brute-Force Check
        if (!Security::checkLoginRateLimit($ip, $email)) {
            Security::recordLoginAttempt($ip, $email, false);
            $this->jsonResponse([
                'success' => false,
                'error'   => 'Çok fazla başarısız deneme yapıldı. Güvenliğiniz için IP adresiniz 15 dakika boyunca kilitlenmiştir.'
            ], 429);
        }

        if (empty($email) || empty($password)) {
            $this->jsonResponse([
                'success' => false,
                'error'   => 'Lütfen e-posta ve parola alanlarını doldurun.'
            ], 400);
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user || !$this->userModel->verifyPassword($password, $user['password_hash'])) {
            Security::recordLoginAttempt($ip, $email, false);
            $this->jsonResponse([
                'success' => false,
                'error'   => 'E-posta adresi veya parola hatalı.'
            ], 401);
        }

        // Record successful login
        Security::recordLoginAttempt($ip, $email, true);
        Security::regenerateSession();

        unset($user['password_hash']);
        $_SESSION['user'] = $user;

        $equipment = $this->equipmentModel->getByUserId((int)$user['id']);

        $this->jsonResponse([
            'success'        => true,
            'user'           => $user,
            'csrf_token'     => Security::generateCsrfToken(),
            'has_onboarding' => !empty($equipment),
            'message'        => "Hoş geldiniz, {$user['name']}!"
        ]);
    }

    /**
     * POST /api/auth/register
     */
    public function register(): void
    {
        $data = $this->getRequestData();

        $ip = Security::getClientIp();
        if (!Security::checkLoginRateLimit($ip)) {
            $this->jsonResponse([
                'success' => false,
                'error'   => 'Çok fazla istek gönderildi. Lütfen daha sonra tekrar deneyin.'
            ], 429);
        }

        $name = trim((string)($data['name'] ?? ''));
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $password = (string)($data['password'] ?? '');
        $gender = (string)($data['gender'] ?? 'male');
        $birthDate = (string)($data['birth_date'] ?? '');
        $heightCm = (float)($data['height_cm'] ?? 175.0);
        $currentWeightKg = (float)($data['current_weight_kg'] ?? 75.0);
        $fitnessLevel = (string)($data['fitness_level'] ?? 'beginner');

        if (empty($name) || empty($email) || strlen($password) < 8 || empty($birthDate)) {
            $this->jsonResponse([
                'success' => false,
                'error'   => 'Lütfen tüm alanları eksiksiz doldurun. Parola en az 8 karakter olmalıdır.'
            ], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse([
                'success' => false,
                'error'   => 'Lütfen geçerli bir e-posta adresi girin.'
            ], 400);
        }

        if (!in_array($gender, ['male', 'female'], true)) {
            $this->jsonResponse([
                'success' => false,
                'error'   => 'Lütfen geçerli bir cinsiyet seçin.'
            ], 400);
        }

        $bDateObj = \DateTime::createFromFormat('Y-m-d', $birthDate);
        if (!$bDateObj || $bDateObj->format('Y-m-d') !== $birthDate) {
            $this->jsonResponse([
                'success' => false,
                'error'   => 'Lütfen geçerli bir doğum tarihi (Yıl-Ay-Gün) girin.'
            ], 400);
        }
        $age = (int)(new \DateTime())->diff($bDateObj)->y;
        if ($age < 13 || $age > 100) {
            $this->jsonResponse([
                'success' => false,
                'error'   => 'Platformumuzu kullanabilmek için yaşınız 13 ile 100 arasında olmalıdır.'
            ], 400);
        }

        if ($heightCm < 100.0 || $heightCm > 250.0) {
            $this->jsonResponse([
                'success' => false,
                'error'   => 'Boy 100 cm ile 250 cm arasında olmalıdır.'
            ], 400);
        }

        if ($currentWeightKg < 30.0 || $currentWeightKg > 300.0) {
            $this->jsonResponse([
                'success' => false,
                'error'   => 'Kilo 30 kg ile 300 kg arasında olmalıdır.'
            ], 400);
        }

        // Check if email already registered
        $existing = $this->userModel->findByEmail($email);
        if ($existing) {
            $this->jsonResponse([
                'success' => false,
                'error'   => 'Bu e-posta adresi zaten kullanımda.'
            ], 409);
        }

        try {
            $userId = $this->userModel->register(
                $name,
                $email,
                $password,
                $gender,
                $birthDate,
                $heightCm,
                $currentWeightKg,
                $fitnessLevel
            );
        } catch (\Throwable $e) {
            error_log("API Register error: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'error'   => 'Kayıt sırasında bir sistem hatası oluştu. Lütfen bilgilerinizi kontrol edip tekrar deneyin.'
            ], 500);
            return;
        }

        // Log starting weight
        $this->weighInModel->logWeight((int)$userId, $currentWeightKg, date('Y-m-d'), 'Başlangıç Tartı Kaydı');

        // Create default equipment entry
        $this->equipmentModel->saveOrUpdate(
            (int)$userId,
            true,
            true,
            20.0,
            2.5,
            ['20kg' => 2, '10kg' => 4, '5kg' => 4, '2.5kg' => 4, '1.25kg' => 4],
            'commercial_gym'
        );

        // Auto authenticate newly registered user
        Security::regenerateSession();
        $user = $this->userModel->findById((int)$userId);
        unset($user['password_hash']);
        $_SESSION['user'] = $user;

        $this->jsonResponse([
            'success'        => true,
            'user'           => $user,
            'csrf_token'     => Security::generateCsrfToken(),
            'has_onboarding' => true,
            'message'        => 'Kayıt başarıyla tamamlandı.'
        ], 201);
    }

    /**
     * POST /api/auth/logout (and GET /api/auth/logout)
     */
    public function logout(): void
    {
        Security::startSession();
        $_SESSION = [];
        if (ini_get("session.use_cookies") && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        $this->jsonResponse([
            'success' => true,
            'message' => 'Başarıyla çıkış yapıldı.'
        ]);
    }

    /**
     * GET /api/auth/me
     */
    public function me(): void
    {
        $sessionUser = $this->requireApiAuth();
        $userId = (int)$sessionUser['id'];

        $user = $this->userModel->findById($userId);
        if (!$user) {
            $this->jsonResponse(['success' => false, 'authenticated' => false, 'error' => 'Kullanıcı bulunamadı.'], 401);
        }

        unset($user['password_hash']);
        $_SESSION['user'] = $user;

        $equipment = $this->equipmentModel->getByUserId($userId);

        $this->jsonResponse([
            'success'        => true,
            'authenticated'  => true,
            'user'           => $user,
            'csrf_token'     => Security::generateCsrfToken(),
            'has_onboarding' => !empty($equipment)
        ]);
    }

    // =========================================================================
    // 2. WORKOUT & MOTOR ENDPOINTS
    // =========================================================================

    /**
     * GET /api/workout/dashboard
     */
    public function workoutDashboard(): void
    {
        $user = $this->requireApiAuth();
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
            $this->geminiWorkoutService->generateWeeklyProgram($userId, $user['fitness_level'] ?? 'beginner', true);
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

        // Active session check
        $activeSession = $this->sessionModel->getActiveSession($userId);

        // Fetch user weight & biometrics
        $latestWeight = (float)($user['current_weight_kg'] ?? 75.0);
        $stmtW = $this->db->prepare("SELECT weight_kg FROM daily_weigh_ins WHERE user_id = :uid ORDER BY logged_date DESC LIMIT 1");
        $stmtW->execute(['uid' => $userId]);
        $wRow = $stmtW->fetch();
        if ($wRow) {
            $latestWeight = (float)$wRow['weight_kg'];
        }

        // BMI
        $heightM = (float)($user['height_cm'] ?? 175.0) / 100.0;
        $bmi = ($heightM > 0) ? round($latestWeight / ($heightM * $heightM), 1) : 22.5;

        // RFI Baselines
        $baselines = $this->rfiService->calculateBaselineLoads($latestWeight, 12, 5, 20, 45);

        // Active injuries
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

        $this->jsonResponse([
            'success'         => true,
            'program'         => $program,
            'program_days'    => $programDays,
            'active_session'  => $activeSession,
            'recent_workouts' => $recentWorkouts,
            'latest_weight'   => $latestWeight,
            'bmi'             => $bmi,
            'baselines'       => $baselines,
            'active_injuries' => $activeInjuries
        ]);
    }

    /**
     * GET /api/workout/live/{day_id} and GET /api/workout/live
     */
    public function workoutLive(?string $id = null): void
    {
        $user = $this->requireApiAuth();
        $userId = (int)$user['id'];
        $rawDayId = $id ?? ($_GET['day'] ?? null);
        $numericDayId = $rawDayId !== null ? (int)$rawDayId : null;

        // 1. Ensure user has a weekly program
        $stmtProg = $this->db->prepare("SELECT id FROM user_programs WHERE user_id = :uid ORDER BY id DESC LIMIT 1");
        $stmtProg->execute(['uid' => $userId]);
        $latestProg = $stmtProg->fetch();
        if (!$latestProg) {
            $this->geminiWorkoutService->generateWeeklyProgram($userId, $user['fitness_level'] ?? 'beginner', true);
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
            $validDayIds = array_map(fn($d) => (int)$d['id'], $allProgramDays);
            if (!in_array($numericDayId, $validDayIds, true)) {
                $this->jsonResponse([
                    'success' => false,
                    'error'   => 'İstenen antrenman günü programınızda bulunamadı (IDOR koruması).'
                ], 404);
                return;
            }

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
            if ($activeSession && !empty($activeSession['program_day_id'])) {
                $programDayId = (int)$activeSession['program_day_id'];
            } else {
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

        // Apply injury filter
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

        $this->jsonResponse([
            'success'          => true,
            'session'          => $activeSession,
            'day_info'         => $dayInfo,
            'exercises'        => $dayExercises,
            'all_program_days' => $allProgramDays,
            'current_day_id'   => $programDayId,
            'equipment'        => $equipment
        ]);
    }

    /**
     * POST /api/workout/log-set (and /api/log_set)
     */
    public function logSet(): void
    {
        $user = $this->requireApiAuth();
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

        $input = $this->getRequestData();

        // 3. Payload & Validation
        $sessionId = (int)($input['session_id'] ?? 0);
        $exerciseId = (int)($input['exercise_id'] ?? 0);
        $setNumber = (int)($input['set_number'] ?? 1);
        $setType = (string)($input['set_type'] ?? 'reps');
        $targetWeight = isset($input['target_weight']) ? (float)$input['target_weight'] : null;
        $actualWeight = (float)($input['actual_weight'] ?? 0.0);
        $targetReps = isset($input['target_reps']) ? (int)$input['target_reps'] : 10;
        $actualReps = isset($input['actual_reps']) && $input['actual_reps'] !== '' ? (int)$input['actual_reps'] : null;
        $durationSeconds = isset($input['duration_seconds']) && $input['duration_seconds'] !== '' ? (int)$input['duration_seconds'] : null;
        $rirBucket = (string)($input['rir_bucket'] ?? '1-2');
        $formBreakdown = !empty($input['form_breakdown']) && ($input['form_breakdown'] === '1' || $input['form_breakdown'] === true || $input['form_breakdown'] === 'true');

        if ($exerciseId <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Geçersiz egzersiz seçimi.'], 400);
            return;
        }

        if ($actualWeight < 0.0) {
            $this->jsonResponse(['success' => false, 'error' => 'Kaldırılan ağırlık negatif olamaz.'], 400);
            return;
        }

        $allowedSetTypes = ['reps', 'duration', 'warmup'];
        if (!in_array($setType, $allowedSetTypes, true)) {
            $setType = 'reps';
        }

        if (!in_array($rirBucket, ['0', '1-2', '3+'], true)) {
            $rirBucket = '1-2';
        }

        // 4. IDOR Guard & Session State Check
        Security::enforceOwnership('workout_sessions', $sessionId, $userId);

        $session = $this->sessionModel->findById($sessionId);
        if (!$session) {
            $this->jsonResponse(['success' => false, 'error' => 'Antrenman seansı bulunamadı.'], 404);
            return;
        }
        if (!empty($session['ended_at'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Bu antrenman seansı tamamlanmış. Bitmiş seansa yeni set eklenemez.'], 400);
            return;
        }

        // Duplicate set check (Double-tap prevention with session lock)
        $lockKey = "log_lock_{$sessionId}_{$exerciseId}_{$setNumber}";
        if (!empty($_SESSION[$lockKey]) && (time() - $_SESSION[$lockKey] < 5)) {
            $this->jsonResponse([
                'success' => false,
                'error'   => "İşleminiz devam ediyor (Çoklu tıklama engellendi)."
            ], 409);
            return;
        }
        $_SESSION[$lockKey] = time();

        $stmtDup = $this->db->prepare("
            SELECT id FROM workout_logs 
            WHERE session_id = :sid AND exercise_id = :eid AND set_number = :set_num 
            LIMIT 1
        ");
        $stmtDup->execute(['sid' => $sessionId, 'eid' => $exerciseId, 'set_num' => $setNumber]);
        if ($stmtDup->fetch()) {
            $this->jsonResponse([
                'success' => false,
                'error'   => "Bu egzersiz için {$setNumber}. set zaten kaydedilmiş (Mükerrer set kaydı engellendi)."
            ], 409);
            return;
        }

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

        // 6. Execute Native Dynamic Hypertrophy Engine
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
     * POST /api/workout/end
     */
    public function workoutEnd(): void
    {
        $user = $this->requireApiAuth();
        Security::verifyRequestCsrf();
        $userId = (int)$user['id'];

        $input = $this->getRequestData();
        $sessionId = (int)($input['session_id'] ?? 0);

        if ($sessionId <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Geçersiz antrenman oturumu.'], 400);
        }

        Security::enforceOwnership('workout_sessions', $sessionId, $userId);

        $this->sessionModel->endSession($sessionId, $userId);
        $summary = $this->sessionModel->getSessionSummary($sessionId, $userId);

        $totalSets = $summary['total_sets_completed'] ?? 0;
        $volume = round((float)($summary['total_volume_kg'] ?? 0), 1);

        $this->jsonResponse([
            'success'    => true,
            'session_id' => $sessionId,
            'summary'    => $summary,
            'message'    => "Harika iş! Antrenman başarıyla tamamlandı. Toplam {$totalSets} set ve {$volume} kg hacim kaydettiniz!"
        ]);
    }

    /**
     * GET /api/workout/ai-coach
     */
    public function getAiCoach(): void
    {
        $user = $this->requireApiAuth();
        $sessionId = (int)($_GET['session_id'] ?? 0);

        if ($sessionId <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Geçersiz antrenman oturumu.'], 400);
        }

        Security::enforceOwnership('workout_sessions', $sessionId, (int)$user['id']);

        try {
            $coachMsg = $this->geminiWorkoutService->generateAiCoachSummary((int)$user['id'], $sessionId);
            $this->jsonResponse(['success' => true, 'message' => $coachMsg]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => \App\Core\Security::isLocalEnv() ? $e->getMessage() : 'Sunucu tarafında beklenmeyen bir hata oluştu.'], 500);
        }
    }

    /**
     * POST /api/workout/coach
     * Multi-turn conversational endpoint for live session coaching
     */
    public function dynamicCoach(): void
    {
        $user = $this->requireApiAuth();
        $input = $this->getRequestData();

        $history = $input['history'] ?? [];
        $message = $input['message'] ?? '';
        $sessionId = (int)($input['session_id'] ?? 0);
        $currentExercise = $input['current_exercise'] ?? null;

        if (empty($message)) {
            $this->jsonResponse(['success' => false, 'error' => 'Mesaj boş olamaz.'], 400);
            return;
        }

        try {
            $response = $this->geminiWorkoutService->dynamicCoachChat((int)$user['id'], $sessionId, $currentExercise, $history, $message);
            $this->jsonResponse(['success' => true, 'data' => $response]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => \App\Core\Security::isLocalEnv() ? $e->getMessage() : 'Sunucu tarafında beklenmeyen bir hata oluştu.'], 500);
        }
    }

    /**
     * POST /api/workout/retry-program
     */
    public function retryProgram(): void
    {
        $user = $this->requireApiAuth();
        Security::verifyRequestCsrf();
        $userId = (int)$user['id'];
        $fitnessLevel = $user['fitness_level'] ?? 'beginner';

        try {
            $this->geminiWorkoutService->generateWeeklyProgram($userId, $fitnessLevel, true);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Antrenman programınız Gemini AI tarafından yeniden optimize edildi.'
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse([
                'success' => false,
                'error'   => \App\Core\Security::isLocalEnv() ? 'Program yenilenirken bir hata oluştu: ' . $e->getMessage() : 'Sunucu tarafında beklenmeyen bir hata oluştu.'
            ], 500);
        }
    }

    /**
     * POST /api/workout/custom-program
     */
    public function customProgram(): void
    {
        $user = $this->requireApiAuth();
        Security::verifyRequestCsrf();
        $userId = (int)$user['id'];
        $input = $this->getRequestData();

        $programType = trim((string)($input['program_type'] ?? 'split_upper_lower'));
        if (!in_array($programType, ['full_body', 'split_upper_lower', 'split_ppl'], true)) {
            $programType = 'full_body';
        }

        $workoutLocation = trim((string)($input['workout_location'] ?? 'home_weights'));

        // Update equipment location
        $equipment = $this->equipmentModel->getByUserId($userId);
        if ($equipment) {
            $this->equipmentModel->update((int)$equipment['id'], ['workout_location' => $workoutLocation]);
        }

        // Handle injury reports
        $injuries = $input['injuries'] ?? [];
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
        $fitnessLevel = $user['fitness_level'] ?? 'intermediate';
        $this->geminiWorkoutService->generateWeeklyProgram($userId, $fitnessLevel, true);

        // Terminate any old hanging session
        $now = date('Y-m-d H:i:s');
        $this->db->exec("UPDATE workout_sessions SET ended_at = '{$now}' WHERE user_id = {$userId} AND ended_at IS NULL");

        $this->jsonResponse([
            'success' => true,
            'message' => 'Yeni antrenman programınız başarıyla oluşturuldu ve haftalık takviminize atandı!'
        ]);
    }

    // =========================================================================
    // 3. ANALYTICS & STATS ENDPOINTS
    // =========================================================================

    /**
     * GET /api/analytics
     */
    public function analytics(): void
    {
        $user = $this->requireApiAuth();
        $userId = (int)$user['id'];

        $exerciseList = $this->analyticsService->getUserExercisesList($userId);
        $allPeaks = $this->analyticsService->getAllExercisesPeakWeights($userId);
        $heatmap = $this->analyticsService->getWeeklyMuscleHeatmap($userId);

        $selectedExerciseId = isset($_GET['exercise_id']) ? (int)$_GET['exercise_id'] : 0;
        
        $activeExercise = null;
        if ($selectedExerciseId > 0) {
            foreach ($exerciseList as $ex) {
                if ((int)$ex['id'] === $selectedExerciseId) {
                    $activeExercise = $ex;
                    break;
                }
            }
        }

        if (!$activeExercise && !empty($exerciseList)) {
            $activeExercise = $exerciseList[0];
            $selectedExerciseId = (int)$activeExercise['id'];
        }

        $timeline = [];
        $detailedSets = [];
        $metrics = [
            'peak_weight'     => 0.0,
            'starting_weight' => 0.0,
            'progression_kg'  => 0.0,
            'progression_pct' => 0.0,
            'peak_1rm'        => 0.0,
            'total_volume'    => 0.0,
            'total_sets'      => 0
        ];

        if ($activeExercise) {
            $timeline = $this->analyticsService->getExerciseTimeline($userId, $selectedExerciseId);
            $detailedSets = $this->analyticsService->getExerciseDetailedSets($userId, $selectedExerciseId);

            if (!empty($detailedSets)) {
                $weights = array_column($detailedSets, 'actual_weight');
                $maxW = max($weights);
                $minW = min($weights);
                $peak1rm = max(array_column($detailedSets, 'estimated_1rm'));

                $totalVol = 0.0;
                foreach ($detailedSets as $s) {
                    $totalVol += ((float)$s['actual_weight'] * (int)($s['actual_reps'] ?? 1));
                }

                $chronological = array_reverse($detailedSets);
                $firstWeight = (float)($chronological[0]['actual_weight'] ?? $minW);
                $lastWeight = (float)($detailedSets[0]['actual_weight'] ?? $maxW);
                $diff = $lastWeight - $firstWeight;
                $pct = $firstWeight > 0 ? round(($diff / $firstWeight) * 100, 1) : 0.0;

                $metrics = [
                    'peak_weight'     => (float)$maxW,
                    'starting_weight' => (float)$firstWeight,
                    'progression_kg'  => (float)$diff,
                    'progression_pct' => (float)$pct,
                    'peak_1rm'        => (float)$peak1rm,
                    'total_volume'    => (float)$totalVol,
                    'total_sets'      => count($detailedSets)
                ];
            }
        }

        $this->jsonResponse([
            'success'          => true,
            'heatmap'          => $heatmap,
            'exercise_list'    => $exerciseList,
            'all_peaks'        => $allPeaks,
            'active_exercise'  => $activeExercise,
            'timeline'         => $timeline,
            'detailed_sets'    => $detailedSets,
            'metrics'          => $metrics
        ]);
    }

    /**
     * GET /api/exercise_progress
     */
    public function exerciseProgress(): void
    {
        $user = $this->requireApiAuth();
        $userId = (int)$user['id'];
        $exerciseId = (int)($_GET['exercise_id'] ?? 0);

        if ($exerciseId <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Geçersiz egzersiz.'], 400);
        }

        $timeline = $this->analyticsService->getExerciseTimeline($userId, $exerciseId);
        $detailed = $this->analyticsService->getExerciseDetailedSets($userId, $exerciseId);

        $this->jsonResponse([
            'success'  => true,
            'timeline' => $timeline,
            'sets'     => $detailed
        ]);
    }

    // =========================================================================
    // 4. PROFILE & WEIGH-IN ENDPOINTS
    // =========================================================================

    /**
     * GET /api/profile
     */
    public function profile(): void
    {
        $userSession = $this->requireApiAuth();
        $userId = (int)$userSession['id'];

        $user = $this->userModel->findById($userId);
        if (!$user) {
            $this->jsonResponse(['success' => false, 'error' => 'Kullanıcı bulunamadı.'], 404);
        }
        unset($user['password_hash']);

        // Active injuries
        $activeInjuries = $this->injuryFilter->getActiveInjuredJoints($userId);

        // BMI
        $heightCm = (float)($user['height_cm'] ?? 175.0);
        $currentWeight = (float)($user['current_weight_kg'] ?? 75.0);

        $bmi = 0.0;
        $bmiCategory = 'Bilinmiyor';
        if ($heightCm > 50) {
            $heightM = $heightCm / 100.0;
            $bmi = round($currentWeight / ($heightM * $heightM), 1);
            if ($bmi < 18.5) {
                $bmiCategory = 'Düşük Kilo';
            } elseif ($bmi < 25.0) {
                $bmiCategory = 'Normal / İdeal';
            } elseif ($bmi < 30.0) {
                $bmiCategory = 'Fazla Kilolu';
            } else {
                $bmiCategory = 'Obezite';
            }
        }

        $minHealthyWeight = round(18.5 * (($heightCm / 100.0) ** 2), 1);
        $maxHealthyWeight = round(24.9 * (($heightCm / 100.0) ** 2), 1);

        // Calisthenic tier info
        $pushups = (int)($user['pushups_count'] ?? 0);
        $chinups = (int)($user['chinups_count'] ?? 0);
        $squats = (int)($user['squats_count'] ?? 0);
        $plankSec = (int)($user['plank_seconds'] ?? 30);
        $tierInfo = $this->rfiService->evaluateFitnessTier($pushups, $chinups, $squats, $plankSec);

        $equipment = $this->equipmentModel->getByUserId($userId);

        $this->jsonResponse([
            'success'            => true,
            'user'               => $user,
            'tier_info'          => $tierInfo,
            'active_injuries'    => $activeInjuries,
            'bmi'                => $bmi,
            'bmi_category'       => $bmiCategory,
            'min_healthy_weight' => $minHealthyWeight,
            'max_healthy_weight' => $maxHealthyWeight,
            'equipment'          => $equipment
        ]);
    }

    /**
     * POST /api/profile
     */
    public function updateProfile(): void
    {
        $currentUser = $this->requireApiAuth();
        Security::verifyRequestCsrf();
        $userId = (int)$currentUser['id'];
        $input = $this->getRequestData();

        $name = trim((string)($input['name'] ?? ''));
        $gender = (string)($input['gender'] ?? 'male');
        $birthDate = (string)($input['birth_date'] ?? '');
        $heightCm = max(100.0, min(250.0, (float)($input['height_cm'] ?? 175.0)));
        $currentWeightKg = max(30.0, min(300.0, (float)($input['current_weight_kg'] ?? 75.0)));
        
        $pushups = max(0, min(200, (int)($input['pushups_count'] ?? 0)));
        $chinups = max(0, min(100, (int)($input['chinups_count'] ?? 0)));
        $squats = max(0, min(300, (int)($input['squats_count'] ?? 0)));
        $plankSec = max(0, min(600, (int)($input['plank_seconds'] ?? 30)));

        $tierResult = $this->rfiService->evaluateFitnessTier($pushups, $chinups, $squats, $plankSec);

        $evalMode = (string)($input['fitness_eval_mode'] ?? 'auto');
        $submittedLevel = (string)($input['fitness_level'] ?? '');
        $allowedLevels = ['absolute_beginner', 'beginner', 'intermediate', 'advanced'];

        if ($evalMode === 'manual' && in_array($submittedLevel, $allowedLevels, true)) {
            $finalFitnessLevel = $submittedLevel;
        } else {
            $finalFitnessLevel = $tierResult['level'];
        }

        if (empty($name)) {
            $this->jsonResponse(['success' => false, 'error' => 'İsim alanı boş bırakılamaz.'], 400);
        }

        $oldLevel = $currentUser['fitness_level'] ?? '';

        // 1. Update User Record
        $this->userModel->update($userId, [
            'name'              => $name,
            'gender'            => $gender,
            'birth_date'        => $birthDate,
            'height_cm'         => $heightCm,
            'current_weight_kg' => $currentWeightKg,
            'fitness_level'     => $finalFitnessLevel,
            'pushups_count'     => $pushups,
            'chinups_count'     => $chinups,
            'squats_count'      => $squats,
            'plank_seconds'     => $plankSec,
            'calisthenic_score' => $tierResult['composite_score']
        ]);

        // 2. Log weight only if changed and no weigh-in recorded today
        $oldWeight = (float)($currentUser['current_weight_kg'] ?? 0.0);
        if (abs($oldWeight - $currentWeightKg) > 0.01) {
            $latestWeighIn = $this->weighInModel->getLatestWeighIn($userId);
            if (!$latestWeighIn || ($latestWeighIn['logged_date'] ?? '') !== date('Y-m-d')) {
                $this->weighInModel->logWeight($userId, $currentWeightKg, date('Y-m-d'), 'Profil Ayarları Güncellemesi');
            }
        }

        // 3. Update Joint Complaints (clear only profile-reported active ones, preserve history)
        $submittedInjuries = $input['injuries'] ?? [];
        if (!is_array($submittedInjuries)) {
            $submittedInjuries = [];
        }

        $allowedJoints = ['shoulder', 'knee', 'lower_back', 'elbow', 'wrist'];
        $cleanJoints = array_intersect($submittedInjuries, $allowedJoints);

        $this->db->prepare("
            DELETE FROM injury_reports 
            WHERE user_id = :uid 
              AND notes = 'Kullanıcı profilinden bildirildi'
        ")->execute(['uid' => $userId]);

        $insertStmt = $this->db->prepare("
            INSERT INTO injury_reports (user_id, joint_area, severity, reported_date, notes) 
            VALUES (:uid, :joint, 'moderate', :rdate, 'Kullanıcı profilinden bildirildi')
        ");
        $today = date('Y-m-d');
        foreach ($cleanJoints as $joint) {
            $insertStmt->execute([
                'uid'   => $userId,
                'joint' => $joint,
                'rdate' => $today
            ]);
        }

        // 4. Regenerate weekly program if requested
        $shouldRegenerate = !empty($input['regenerate_program']) || ($oldLevel !== $finalFitnessLevel);
        if ($shouldRegenerate) {
            try {
                $this->geminiWorkoutService->generateWeeklyProgram($userId, $finalFitnessLevel, true);
            } catch (\Throwable $e) {
                error_log("Program regen error: " . $e->getMessage());
            }
        }

        $updatedUser = $this->userModel->findById($userId);
        unset($updatedUser['password_hash']);
        $_SESSION['user'] = $updatedUser;

        $this->jsonResponse([
            'success'   => true,
            'user'      => $updatedUser,
            'tier_info' => $tierResult,
            'message'   => "Biyometrik verileriniz ve tercihleriniz başarıyla kaydedildi!"
        ]);
    }

    /**
     * POST /api/profile/password
     */
    public function updatePassword(): void
    {
        $currentUser = $this->requireApiAuth();
        Security::verifyRequestCsrf();
        $userId = (int)$currentUser['id'];
        $input = $this->getRequestData();

        $currentPassword = (string)($input['current_password'] ?? '');
        $newPassword = (string)($input['new_password'] ?? '');
        $confirmPassword = (string)($input['confirm_password'] ?? '');

        if (strlen($newPassword) < 8) {
            $this->jsonResponse(['success' => false, 'error' => 'Yeni parola en az 8 karakter olmalıdır.'], 400);
        }

        if ($newPassword !== $confirmPassword) {
            $this->jsonResponse(['success' => false, 'error' => 'Yeni parolalar birbiriyle eşleşmiyor.'], 400);
        }

        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $userWithHash = $stmt->fetch();
        if (!$userWithHash || !password_verify($currentPassword, $userWithHash['password_hash'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Mevcut parolanızı hatalı girdiniz.'], 400);
        }

        $this->userModel->updatePassword($userId, $newPassword);

        $this->jsonResponse([
            'success' => true,
            'message' => 'Parolanız başarıyla güncellendi.'
        ]);
    }

    /**
     * GET /api/weigh-in
     */
    public function weighIn(): void
    {
        $user = $this->requireApiAuth();
        $userId = (int)$user['id'];

        $history = $this->weighInModel->getRecentHistory($userId, 30);
        $movingAvg7 = $this->weighInModel->getMovingAverage($userId, 7);
        $weeklyRate = $this->weighInModel->getWeeklyRateOfChange($userId);

        $labels = [];
        $rawWeights = [];
        $movingAverages = [];

        foreach ($history as $idx => $entry) {
            $labels[] = date('d M', strtotime($entry['logged_date']));
            $rawWeights[] = (float)$entry['weight_kg'];

            $subset = array_slice($rawWeights, max(0, $idx - 6), min(7, $idx + 1));
            $movingAverages[] = round(array_sum($subset) / count($subset), 2);
        }

        $this->jsonResponse([
            'success'     => true,
            'history'     => array_reverse($history),
            'moving_avg7' => $movingAvg7,
            'weekly_rate' => $weeklyRate,
            'chart'       => [
                'labels'          => $labels,
                'raw_weights'     => $rawWeights,
                'moving_averages' => $movingAverages
            ]
        ]);
    }

    /**
     * POST /api/weigh-in
     */
    public function saveWeighIn(): void
    {
        $user = $this->requireApiAuth();
        Security::verifyRequestCsrf();
        $userId = (int)$user['id'];
        $input = $this->getRequestData();

        $weightKg = (float)($input['weight_kg'] ?? 0.0);
        $date = trim((string)($input['logged_date'] ?? date('Y-m-d')));
        $notes = !empty($input['notes']) ? trim((string)$input['notes']) : null;

        if ($weightKg < 30.0 || $weightKg > 300.0) {
            $this->jsonResponse(['success' => false, 'error' => 'Lütfen geçerli bir kilo değeri giriniz (30 - 300 kg).'], 400);
        }

        $this->weighInModel->logWeight($userId, $weightKg, $date, $notes);
        
        // Sync current weight on user record as well
        $this->userModel->update($userId, ['current_weight_kg' => $weightKg]);
        if (!empty($_SESSION['user'])) {
            $_SESSION['user']['current_weight_kg'] = $weightKg;
        }

        $this->jsonResponse([
            'success' => true,
            'message' => "Tartı kaydınız ({$weightKg} kg) başarıyla kaydedildi.",
            'weight'  => $weightKg
        ]);
    }

    // =========================================================================
    // 5. CALCULATORS ENDPOINTS
    // =========================================================================

    /**
     * GET/POST /api/calculators/1rm
     */
    public function calculator1rm(): void
    {
        $input = $this->getRequestData();
        $weight = (float)($input['weight'] ?? 100.0);
        $reps = max(1, min(30, (int)($input['reps'] ?? 5)));

        // Formulas:
        // Epley: weight * (1 + reps / 30)
        // Brzycki: weight * (36 / (37 - reps))
        $epley = ($reps === 1) ? $weight : round($weight * (1.0 + ($reps / 30.0)), 1);
        $brzycki = ($reps >= 37) ? $weight : round($weight * (36.0 / (37.0 - $reps)), 1);
        $estimated1rm = round(($epley + $brzycki) / 2.0, 1);

        // Percentage table
        $table = [];
        $percentages = [100, 95, 90, 85, 80, 75, 70, 65, 60, 55, 50];
        foreach ($percentages as $pct) {
            $table[] = [
                'percentage' => $pct,
                'weight'     => round($estimated1rm * ($pct / 100.0), 1),
                'reps'       => match ($pct) {
                    100 => 1,
                    95  => 2,
                    90  => 4,
                    85  => 6,
                    80  => 8,
                    75  => 10,
                    70  => 12,
                    65  => 15,
                    60  => 20,
                    default => 25
                }
            ];
        }

        $this->jsonResponse([
            'success'          => true,
            'weight'           => $weight,
            'reps'             => $reps,
            'epley_1rm'        => $epley,
            'brzycki_1rm'      => $brzycki,
            'estimated_1rm'    => $estimated1rm,
            'percentage_table' => $table
        ]);
    }

    /**
     * GET/POST /api/calculators/tdee
     */
    public function calculatorTdee(): void
    {
        $input = $this->getRequestData();
        $gender = (string)($input['gender'] ?? 'male');
        $weightKg = (float)($input['weight_kg'] ?? 75.0);
        $heightCm = (float)($input['height_cm'] ?? 175.0);
        $age = max(14, min(100, (int)($input['age'] ?? 25)));
        $activity = (string)($input['activity_level'] ?? 'moderate');
        $goal = (string)($input['goal'] ?? 'maintain');

        // BMR (Mifflin-St Jeor)
        // Men: 10 * weight(kg) + 6.25 * height(cm) - 5 * age(y) + 5
        // Women: 10 * weight(kg) + 6.25 * height(cm) - 5 * age(y) - 161
        if ($gender === 'female') {
            $bmr = round((10.0 * $weightKg) + (6.25 * $heightCm) - (5.0 * $age) - 161.0);
        } else {
            $bmr = round((10.0 * $weightKg) + (6.25 * $heightCm) - (5.0 * $age) + 5.0);
        }

        $multipliers = [
            'sedentary'  => 1.2,
            'light'      => 1.375,
            'moderate'   => 1.55,
            'heavy'      => 1.725,
            'extreme'    => 1.9
        ];
        $multiplier = $multipliers[$activity] ?? 1.55;
        $tdee = round($bmr * $multiplier);

        // Goal adjustments
        $targetCalories = match ($goal) {
            'cut_aggressive' => round($tdee - 500),
            'cut'            => round($tdee - 300),
            'bulk'           => round($tdee + 300),
            'bulk_aggressive'=> round($tdee + 500),
            default          => $tdee
        };

        // Macronutrients
        $proteinGrams = round($weightKg * 2.0);
        $fatGrams = round($weightKg * 1.0);
        $proteinCalories = $proteinGrams * 4;
        $fatCalories = $fatGrams * 9;
        $remainingCalories = max(0, $targetCalories - ($proteinCalories + $fatCalories));
        $carbGrams = round($remainingCalories / 4);

        $this->jsonResponse([
            'success'         => true,
            'bmr'             => $bmr,
            'tdee'            => $tdee,
            'target_calories' => $targetCalories,
            'goal'            => $goal,
            'activity_level'  => $activity,
            'macros'          => [
                'protein_grams' => $proteinGrams,
                'fat_grams'     => $fatGrams,
                'carb_grams'    => $carbGrams
            ]
        ]);
    }

    /**
     * GET /api/onboarding/assessment
     */
    public function getAssessment(): void
    {
        $user = $this->requireApiAuth();
        $pushups = (int)($user['pushups_count'] ?? 0);
        $chinups = (int)($user['chinups_count'] ?? 0);
        $squats = (int)($user['squats_count'] ?? 0);
        $plankSec = (int)($user['plank_seconds'] ?? 30);

        $tierResult = $this->rfiService->evaluateFitnessTier($pushups, $chinups, $squats, $plankSec);

        $this->jsonResponse([
            'success'       => true,
            'pushups'       => $pushups,
            'chinups'       => $chinups,
            'squats'        => $squats,
            'plank_seconds' => $plankSec,
            'tier'          => $tierResult
        ]);
    }

    /**
     * POST /api/onboarding/assessment
     */
    public function saveAssessment(): void
    {
        $user = $this->requireApiAuth();
        Security::verifyRequestCsrf();

        $userId = (int)$user['id'];
        $input = $this->getRequestData();

        $pushups = max(0, (int)($input['pushups'] ?? 0));
        $chinups = max(0, (int)($input['chinups'] ?? 0));
        $squats = max(0, (int)($input['squats'] ?? 0));
        $plankSec = max(0, (int)($input['plank_seconds'] ?? 30));

        $tierResult = $this->rfiService->evaluateFitnessTier($pushups, $chinups, $squats, $plankSec);
        $this->userModel->update($userId, [
            'fitness_level'     => $tierResult['level'],
            'pushups_count'     => $pushups,
            'chinups_count'     => $chinups,
            'squats_count'      => $squats,
            'plank_seconds'     => $plankSec,
            'calisthenic_score' => $tierResult['composite_score']
        ]);

        $_SESSION['user']['fitness_level'] = $tierResult['level'];
        $_SESSION['user']['pushups_count'] = $pushups;
        $_SESSION['user']['chinups_count'] = $chinups;
        $_SESSION['user']['squats_count'] = $squats;
        $_SESSION['user']['plank_seconds'] = $plankSec;
        $_SESSION['user']['calisthenic_score'] = $tierResult['composite_score'];

        $estWeight = (float)($user['current_weight_kg'] ?? 75.0);
        $baselines = $this->rfiService->calculateBaselineLoads($estWeight, $pushups, $chinups, $squats, $plankSec);
        $_SESSION['onboarding_baselines'] = $baselines;
        $_SESSION['onboarding_regressions'] = $tierResult['regressions'];

        try {
            $this->geminiWorkoutService->generateWeeklyProgram($userId, $tierResult['level'], true);
        } catch (\Throwable $e) {
            error_log("Program regen error: " . $e->getMessage());
        }

        $this->jsonResponse([
            'success'   => true,
            'tier'      => $tierResult,
            'baselines' => $baselines,
            'message'   => "🎯 Kuvvet testiniz kaydedildi! Skorunuz: {$tierResult['composite_score']}/100 — Seviyeniz: {$tierResult['title']}."
        ]);
    }

    /**
     * GET /api/onboarding/equipment
     */
    public function getEquipment(): void
    {
        $user = $this->requireApiAuth();
        $userId = (int)$user['id'];
        $eq = $this->equipmentModel->getByUserId($userId);

        $defaultPlates = [
            '20kg'   => 2,
            '10kg'   => 4,
            '5kg'    => 4,
            '2.5kg'  => 4,
            '1.25kg' => 4,
            '0.5kg'  => 2
        ];

        $platesArr = (!empty($eq['plates_array']) && is_array($eq['plates_array'])) ? $eq['plates_array'] : $defaultPlates;

        $this->jsonResponse([
            'success'   => true,
            'equipment' => [
                'workout_location'          => $eq['workout_location'] ?? 'home_weights',
                'has_barbell'               => !empty($eq['has_barbell']),
                'has_dumbbells'             => !empty($eq['has_dumbbells']),
                'barbell_weight_kg'         => (float)($eq['barbell_weight_kg'] ?? 20.0),
                'dumbbell_handle_weight_kg' => (float)($eq['dumbbell_handle_weight_kg'] ?? 2.5),
                'plates'                    => $platesArr
            ]
        ]);
    }

    /**
     * POST /api/onboarding/equipment
     */
    public function saveEquipment(): void
    {
        $user = $this->requireApiAuth();
        Security::verifyRequestCsrf();

        $userId = (int)$user['id'];
        $input = $this->getRequestData();

        $location = (string)($input['workout_location'] ?? 'home_weights');
        $hasBarbell = !empty($input['has_barbell']);
        $hasDumbbells = !empty($input['has_dumbbells']);
        $barbellWeight = (float)($input['barbell_weight_kg'] ?? 20.0);
        $dumbbellHandleWeight = (float)($input['dumbbell_handle_weight_kg'] ?? 2.5);

        $plates = [];
        $plateKeys = ['20kg', '15kg', '10kg', '5kg', '2.5kg', '1.25kg', '0.5kg'];
        if (isset($input['plates']) && is_array($input['plates'])) {
            foreach ($plateKeys as $pk) {
                $cnt = max(0, (int)($input['plates'][$pk] ?? 0));
                if ($cnt > 0) $plates[$pk] = $cnt;
            }
        } else {
            foreach ($plateKeys as $pk) {
                $cnt = max(0, (int)($input["plate_{$pk}"] ?? 0));
                if ($cnt > 0) $plates[$pk] = $cnt;
            }
        }

        $this->equipmentModel->saveOrUpdate(
            $userId,
            $hasBarbell,
            $hasDumbbells,
            $barbellWeight,
            $dumbbellHandleWeight,
            $plates,
            $location
        );

        $fitnessLevel = $user['fitness_level'] ?? 'beginner';
        try {
            $this->geminiWorkoutService->generateWeeklyProgram($userId, $fitnessLevel);
        } catch (\Throwable $e) {
            error_log("Program gen error: " . $e->getMessage());
        }

        $this->jsonResponse([
            'success' => true,
            'message' => 'Ekipman envanteriniz kaydedildi ve antrenman programınız başarıyla hazırlandı!'
        ]);
    }
}
