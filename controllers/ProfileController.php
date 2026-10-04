<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Models\User;
use App\Models\DailyWeighIn;
use App\Services\InjuryFilterService;
use App\Services\RfiService;
use App\Services\GeminiWorkoutService;
use App\Config\Database;
use PDO;

class ProfileController extends Controller
{
    private User $userModel;
    private InjuryFilterService $injuryFilter;
    private RfiService $rfiService;
    private GeminiWorkoutService $workoutService;
    private PDO $db;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->injuryFilter = new InjuryFilterService();
        $this->rfiService = new RfiService();
        $this->workoutService = new GeminiWorkoutService();
        $this->db = Database::getConnection();
    }

    public function show(): void
    {
        $currentUser = $this->requireAuth();
        $userId = (int)$currentUser['id'];

        // Refresh user record from database
        $user = $this->userModel->findById($userId);
        if (!$user) {
            $this->redirect('/logout');
        }

        // Active injured joints
        $activeInjuries = $this->injuryFilter->getActiveInjuredJoints($userId);

        // Calculate BMI and Healthy Range
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

        // Multi-parametric conditioning tier calculation
        $pushups = (int)($user['pushups_count'] ?? 0);
        $chinups = (int)($user['chinups_count'] ?? 0);
        $squats = (int)($user['squats_count'] ?? 0);
        $plankSec = (int)($user['plank_seconds'] ?? 30);
        $tierInfo = $this->rfiService->evaluateFitnessTier($pushups, $chinups, $squats, $plankSec);

        $this->render('profile/index', [
            'pageTitle'         => 'Profilim & Biyometrik Ayarlar — Akıllı Hipertrofi',
            'user'              => $user,
            'tierInfo'          => $tierInfo,
            'activeInjuries'    => $activeInjuries,
            'bmi'               => $bmi,
            'bmiCategory'       => $bmiCategory,
            'minHealthyWeight'  => $minHealthyWeight,
            'maxHealthyWeight'  => $maxHealthyWeight
        ]);
    }

    public function update(): void
    {
        $currentUser = $this->requireAuth();
        Security::verifyRequestCsrf();
        $userId = (int)$currentUser['id'];

        $name = trim($_POST['name'] ?? '');
        $gender = (string)($_POST['gender'] ?? 'male');
        
        if (!in_array($gender, ['male', 'female'], true)) {
            $this->setFlash('danger', 'Lütfen geçerli bir cinsiyet seçin.');
            $this->redirect('/profile');
        }

        $birthDate = (string)($_POST['birth_date'] ?? '');
        $heightCm = max(100.0, min(250.0, (float)($_POST['height_cm'] ?? 175.0)));
        $currentWeightKg = max(30.0, min(300.0, (float)($_POST['current_weight_kg'] ?? 75.0)));
        
        // Multi-parametric conditioning inputs
        $pushups = max(0, min(200, (int)($_POST['pushups_count'] ?? 0)));
        $chinups = max(0, min(100, (int)($_POST['chinups_count'] ?? 0)));
        $squats = max(0, min(300, (int)($_POST['squats_count'] ?? 0)));
        $plankSec = max(0, min(600, (int)($_POST['plank_seconds'] ?? 30)));

        // Run multi-parametric evaluation algorithm (Push %25, Pull %25, Leg %25, Core %25)
        $tierResult = $this->rfiService->evaluateFitnessTier($pushups, $chinups, $squats, $plankSec);

        // Check if user set manual override or relies on auto algorithm
        $evalMode = (string)($_POST['fitness_eval_mode'] ?? 'auto');
        $submittedLevel = (string)($_POST['fitness_level'] ?? '');
        $allowedLevels = ['absolute_beginner', 'beginner', 'intermediate', 'advanced'];

        if ($evalMode === 'manual' && in_array($submittedLevel, $allowedLevels, true)) {
            $finalFitnessLevel = $submittedLevel;
        } else {
            $finalFitnessLevel = $tierResult['level'];
        }

        if (empty($name)) {
            $this->setFlash('danger', 'İsim alanı boş bırakılamaz.');
            $this->redirect('/profile');
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

        // Günlük tartı ve sakatlık raporları artık kendi özel sayfalarından / modüllerinden yönetilecek.
        // Profil kaydı sırasında bunların ezilmesi (overwrite) engellendi.

        // 4. Regenerate weekly program if requested or if fitness level changed
        $shouldRegenerate = !empty($_POST['regenerate_program']) || ($oldLevel !== $finalFitnessLevel);
        $programNote = "";
        if ($shouldRegenerate) {
            try {
                $this->workoutService->generateWeeklyProgram($userId, $finalFitnessLevel, true);
                $programNote = " Haftalık antrenman programınız yeni seviyenize ve kilonuza göre baştan uyarlandı!";
            } catch (\Throwable $e) {
                error_log("Program regen error: " . $e->getMessage());
            }
        }

        // Refresh Session
        $updatedUser = $this->userModel->findById($userId);
        unset($updatedUser['password_hash']);
        $_SESSION['user'] = $updatedUser;

        $this->setFlash('success', "Biyometrik verileriniz, 4 parametreli kalistenik testiniz (Skor: {$tierResult['composite_score']}/100 — {$tierResult['title']}) ve eklem tercihleriniz kaydedildi!{$programNote}");
        $this->redirect('/profile');
    }

    public function updatePassword(): void
    {
        $currentUser = $this->requireAuth();
        Security::verifyRequestCsrf();
        $userId = (int)$currentUser['id'];

        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if (strlen($newPassword) < 8) {
            $this->setFlash('danger', 'Yeni parola en az 8 karakter olmalıdır.');
            $this->redirect('/profile');
        }

        if ($newPassword !== $confirmPassword) {
            $this->setFlash('danger', 'Yeni parolalar birbiriyle eşleşmiyor.');
            $this->redirect('/profile');
        }

        // Verify current password
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $userWithHash = $stmt->fetch();
        if (!$userWithHash || !password_verify($currentPassword, $userWithHash['password_hash'])) {
            $this->setFlash('danger', 'Mevcut parolanızı hatalı girdiniz.');
            $this->redirect('/profile');
        }

        // Update to new password via User model (Argon2id)
        $this->userModel->updatePassword($userId, $newPassword);

        $this->setFlash('success', 'Parolanız başarıyla güncellendi.');
        $this->redirect('/profile');
    }
}
