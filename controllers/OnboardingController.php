<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Models\User;
use App\Models\Equipment;
use App\Services\RfiService;
use App\Services\GeminiWorkoutService;

class OnboardingController extends Controller
{
    private User $userModel;
    private Equipment $equipmentModel;
    private RfiService $rfiService;
    private GeminiWorkoutService $geminiWorkoutService;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->equipmentModel = new Equipment();
        $this->rfiService = new RfiService();
        $this->geminiWorkoutService = new GeminiWorkoutService();
    }

    public function showAssessment(): void
    {
        $user = $this->requireAuth();
        $this->render('onboarding/assessment', [
            'pageTitle' => 'Kuvvet Testi & Onboarding — Akıllı Hipertrofi',
            'user'      => $user
        ]);
    }

    public function saveAssessment(): void
    {
        $user = $this->requireAuth();
        Security::verifyRequestCsrf();

        $userId = (int)$user['id'];
        $pushups = max(0, (int)($_POST['pushups'] ?? 0));
        $chinups = max(0, (int)($_POST['chinups'] ?? 0));
        $squats = max(0, (int)($_POST['squats'] ?? 0));
        $plankSec = max(0, (int)($_POST['plank_seconds'] ?? 30));

        // Evaluate tier & regressions with 4-pillar multi-parametric algorithm
        $tierResult = $this->rfiService->evaluateFitnessTier($pushups, $chinups, $squats, $plankSec);
        $this->userModel->update($userId, [
            'fitness_level'     => $tierResult['level'],
            'pushups_count'     => $pushups,
            'chinups_count'     => $chinups,
            'squats_count'      => $squats,
            'plank_seconds'     => $plankSec,
            'calisthenic_score' => $tierResult['composite_score']
        ]);

        // Update session
        $_SESSION['user']['fitness_level'] = $tierResult['level'];
        $_SESSION['user']['pushups_count'] = $pushups;
        $_SESSION['user']['chinups_count'] = $chinups;
        $_SESSION['user']['squats_count'] = $squats;
        $_SESSION['user']['plank_seconds'] = $plankSec;
        $_SESSION['user']['calisthenic_score'] = $tierResult['composite_score'];

        // Save baseline calculation in session
        $heightCm = (float)($user['height_cm'] ?? 175.0);
        $estWeight = (float)($user['current_weight_kg'] ?? 75.0);
        $baselines = $this->rfiService->calculateBaselineLoads($estWeight, $pushups, $chinups, $squats, $plankSec);
        $_SESSION['onboarding_baselines'] = $baselines;
        $_SESSION['onboarding_regressions'] = $tierResult['regressions'];

        // Automatically regenerate weekly program tailored to this newly evaluated level
        $this->geminiWorkoutService->generateWeeklyProgram($userId, $tierResult['level'], true);

        $this->setFlash('success', "🎯 Kalistenik kuvvet testiniz tamamlandı! Skorunuz: {$tierResult['composite_score']}/100 — Seviyeniz: {$tierResult['title']}. Programınız güncellendi.");
        $this->redirect('/onboarding/equipment');
    }

    public function showEquipment(): void
    {
        $user = $this->requireAuth();
        $existingEquipment = $this->equipmentModel->getByUserId((int)$user['id']);

        $this->render('onboarding/equipment', [
            'pageTitle' => 'Ekipman & Plaka Envanteri — Akıllı Hipertrofi',
            'user'      => $user,
            'equipment' => $existingEquipment
        ]);
    }

    public function saveEquipment(): void
    {
        $user = $this->requireAuth();
        Security::verifyRequestCsrf();

        $userId = (int)$user['id'];
        $location = (string)($_POST['workout_location'] ?? 'home_weights');
        $hasBarbell = !empty($_POST['has_barbell']);
        $hasDumbbells = !empty($_POST['has_dumbbells']);
        $barbellWeight = (float)($_POST['barbell_weight_kg'] ?? 20.0);
        $dumbbellHandleWeight = (float)($_POST['dumbbell_handle_weight_kg'] ?? 2.5);

        // Parse plate counts
        $plates = [];
        $plateKeys = ['20kg', '15kg', '10kg', '5kg', '2.5kg', '1.25kg', '0.5kg'];
        foreach ($plateKeys as $pk) {
            $cnt = max(0, (int)($_POST["plate_{$pk}"] ?? 0));
            if ($cnt > 0) {
                $plates[$pk] = $cnt;
            }
        }

        // Save equipment
        $this->equipmentModel->saveOrUpdate(
            $userId,
            $hasBarbell,
            $hasDumbbells,
            $barbellWeight,
            $dumbbellHandleWeight,
            $plates,
            $location
        );

        // Generate weekly program skeleton
        $fitnessLevel = $user['fitness_level'] ?? 'beginner';
        try {
            $this->geminiWorkoutService->generateWeeklyProgram($userId, $fitnessLevel);
            $this->setFlash('success', 'Tebrikler! Ekipman envanteriniz kaydedildi ve ilk haftalık antrenman programınız başarıyla oluşturuldu.');
        } catch (\Throwable $e) {
            error_log("Workout generation notice: " . $e->getMessage());
            $this->setFlash('warning', 'Ekipmanlarınız kaydedildi. Programınız statik şablon üzerinden hazırlandı.');
        }

        $this->redirect('/workout/dashboard');
    }
}
