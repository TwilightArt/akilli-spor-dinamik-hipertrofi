<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class CalculatorController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function tdee(): void
    {
        $user = $this->requireAuth();

        $this->render('calculator/tdee', [
            'pageTitle' => 'TDEE & Makro Hesaplayıcı — Akıllı Hipertrofi',
            'user'      => $user
        ]);
    }
}
