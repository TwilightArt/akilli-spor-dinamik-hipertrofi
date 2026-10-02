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
}
