<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class CalculatorController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();

        $this->render('calculators/1rm', [
            'pageTitle' => '1RM (One Rep Max) Hesaplayıcı — Akıllı Hipertrofi',
            'user'      => $user
        ]);
    }

    public function tdee(): void
    {
        $user = $this->requireAuth();

        $this->render('calculators/tdee', [
            'pageTitle' => 'TDEE & Makro Hesaplayıcı — Akıllı Hipertrofi',
            'user'      => $user
        ]);
    }

    public function water(): void
    {
        $user = $this->requireAuth();

        $this->render('calculators/water', [
            'pageTitle' => 'Günlük Su İhtiyacı Hesaplayıcı — Akıllı Hipertrofi',
            'user'      => $user
        ]);
    }

    public function rpe(): void
    {
        $user = $this->requireAuth();

        $this->render('calculators/rpe', [
            'pageTitle' => 'RPE & RIR Hedef Ağırlık Simülatörü — Akıllı Hipertrofi',
            'user'      => $user
        ]);
    }

    public function hiit(): void
    {
        $user = $this->requireAuth();

        $this->render('calculators/hiit', [
            'pageTitle' => 'HIIT & Tabata Akıllı Kronometre — Akıllı Hipertrofi',
            'user'      => $user
        ]);
    }
}
