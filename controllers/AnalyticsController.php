<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Services\AnalyticsService;

class AnalyticsController extends Controller
{
    private AnalyticsService $analyticsService;

    public function __construct()
    {
        parent::__construct();
        $this->analyticsService = new AnalyticsService();
    }

    public function index(): void
    {
        $currentUser = $this->requireAuth();
        $userId = (int)$currentUser['id'];

        $exerciseList = $this->analyticsService->getUserExercisesList($userId);
        $allPeaks = $this->analyticsService->getAllExercisesPeakWeights($userId);

        // Requested exercise or default to top exercise
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
            'peak_weight'      => 0.0,
            'starting_weight'  => 0.0,
            'progression_kg'   => 0.0,
            'progression_pct'  => 0.0,
            'peak_1rm'         => 0.0,
            'total_volume'     => 0.0,
            'total_sets'       => 0
        ];

        if ($activeExercise) {
            $timeline = $this->analyticsService->getExerciseTimeline($userId, $selectedExerciseId);
            $detailedSets = $this->analyticsService->getExerciseDetailedSets($userId, $selectedExerciseId);

            if (!empty($detailedSets)) {
                $weights = array_column($detailedSets, 'actual_weight');
                $maxW = max($weights);
                $minW = min($weights);
                $peak1rm = max(array_column($detailedSets, 'estimated_1rm'));

                // Calculate total volume
                $totalVol = 0.0;
                foreach ($detailedSets as $s) {
                    $totalVol += ((float)$s['actual_weight'] * (int)($s['actual_reps'] ?? 1));
                }

                // Oldest weight vs newest weight
                $chronological = array_reverse($detailedSets);
                $firstWeight = (float)($chronological[0]['actual_weight'] ?? $minW);
                $lastWeight = (float)($detailedSets[0]['actual_weight'] ?? $maxW);
                $diff = $lastWeight - $firstWeight;
                $pct = ($firstWeight > 0) ? round(($diff / $firstWeight) * 100, 1) : 0.0;

                $metrics = [
                    'peak_weight'     => (float)$maxW,
                    'starting_weight' => $firstWeight,
                    'progression_kg'  => $diff,
                    'progression_pct' => $pct,
                    'peak_1rm'        => (float)$peak1rm,
                    'total_volume'    => $totalVol,
                    'total_sets'      => count($detailedSets)
                ];
            }
        }

        $this->render('analytics/index', [
            'pageTitle'        => 'Ağırlık Gelişimi & Güç Analitiği — Akıllı Hipertrofi',
            'user'             => $currentUser,
            'exerciseList'     => $exerciseList,
            'activeExercise'   => $activeExercise,
            'timeline'         => $timeline,
            'detailedSets'     => $detailedSets,
            'allPeaks'         => $allPeaks,
            'metrics'          => $metrics
        ]);
    }

    public function seedSampleData(): void
    {
        $currentUser = $this->requireAuth();
        Security::verifyRequestCsrf();
        $userId = (int)$currentUser['id'];

        try {
            $this->analyticsService->seedRealisticOverloadHistory($userId);
            $this->setFlash('success', '🎯 4 haftalık aşamalı aşırı yüklenme (progressive overload) antrenman verisi başarıyla hesabınıza tanımlandı! Grafikleriniz aşağıda güncellendi.');
        } catch (\Throwable $e) {
            $this->setFlash('danger', 'Demo veri eklenirken hata oluştu: ' . $e->getMessage());
        }

        $this->redirect('/analytics');
    }

    public function apiProgress(): void
    {
        $currentUser = $this->requireAuth();
        $userId = (int)$currentUser['id'];
        $exerciseId = isset($_GET['exercise_id']) ? (int)$_GET['exercise_id'] : 0;

        if ($exerciseId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Geçersiz egzersiz ID']);
            exit;
        }

        $timeline = $this->analyticsService->getExerciseTimeline($userId, $exerciseId);
        $detailed = $this->analyticsService->getExerciseDetailedSets($userId, $exerciseId);

        header('Content-Type: application/json');
        echo json_encode([
            'success'  => true,
            'timeline' => $timeline,
            'detailed' => $detailed
        ]);
        exit;
    }
}
