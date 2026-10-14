<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Models\DailyWeighIn;
use App\Models\BodyMeasurement;

class WeighInController extends Controller
{
    private DailyWeighIn $weighInModel;
    private BodyMeasurement $measurementModel;

    public function __construct()
    {
        parent::__construct();
        $this->weighInModel = new DailyWeighIn();
        $this->measurementModel = new BodyMeasurement();
    }

    public function index(): void
    {
        $user = $this->requireAuth();
        $userId = (int)$user['id'];

        $history = $this->weighInModel->getRecentHistory($userId, 30);
        $movingAvg7 = $this->weighInModel->getMovingAverage($userId, 7);
        $weeklyRate = $this->weighInModel->getWeeklyRateOfChange($userId);

        $measurements = $this->measurementModel->getRecentHistory($userId, 20);
        $deltas = $this->measurementModel->getDeltas($userId);

        // Prepare Chart.js arrays
        $labels = [];
        $rawWeights = [];
        $movingAverages = [];

        foreach ($history as $idx => $entry) {
            $labels[] = date('d M', strtotime($entry['logged_date']));
            $rawWeights[] = (float)$entry['weight_kg'];

            // Compute running 7-day average up to this date
            $subset = array_slice($rawWeights, max(0, $idx - 6), min(7, $idx + 1));
            $movingAverages[] = round(array_sum($subset) / count($subset), 2);
        }

        $this->render('weigh_in/index', [
            'pageTitle'         => 'Tartı & Vücut Ölçüleri Takibi — Akıllı Hipertrofi',
            'user'              => $user,
            'history'           => array_reverse($history),
            'movingAvg7'        => $movingAvg7,
            'weeklyRate'        => $weeklyRate,
            'measurements'      => $measurements,
            'deltas'            => $deltas,
            'chartLabels'       => json_encode($labels),
            'chartRawWeights'   => json_encode($rawWeights),
            'chartMovingAvg'    => json_encode($movingAverages)
        ]);
    }

    public function save(): void
    {
        $user = $this->requireAuth();
        Security::verifyRequestCsrf();

        $userId = (int)$user['id'];
        $weightKg = (float)($_POST['weight_kg'] ?? 0.0);
        $date = trim($_POST['logged_date'] ?? date('Y-m-d'));
        $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;

        if ($weightKg < 30.0 || $weightKg > 300.0) {
            $this->setFlash('danger', 'Lütfen geçerli bir kilo değeri giriniz (30 - 300 kg).');
            $this->redirect('/weigh-in');
        }

        $this->weighInModel->logWeight($userId, $weightKg, $date, $notes);
        $this->setFlash('success', "Tartı kaydınız ({$weightKg} kg) başarıyla kaydedildi.");
        $this->redirect('/weigh-in');
    }

    public function saveMeasurements(): void
    {
        $user = $this->requireAuth();
        Security::verifyRequestCsrf();

        $userId = (int)$user['id'];
        $this->measurementModel->logMeasurement($userId, $_POST);

        $this->setFlash('success', 'Vücut ölçüleriniz başarıyla kaydedildi.');
        $this->redirect('/weigh-in');
    }
}
