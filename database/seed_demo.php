<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Equipment.php';
require_once __DIR__ . '/../models/Exercise.php';
require_once __DIR__ . '/../services/InjuryFilterService.php';
require_once __DIR__ . '/../services/GeminiWorkoutService.php';

use App\Models\User;
use App\Models\Equipment;
use App\Services\GeminiWorkoutService;

$userModel = new User();
$email = 'demo@hipertrofi.com';
$password = 'demo123456';

$existing = $userModel->findByEmail($email);
if (!$existing) {
    $uid = $userModel->register('Demo Sporcu', $email, $password, 'male', '1996-06-20', 180.0, 75.0, 'intermediate');
    $eq = new Equipment();
    $eq->saveOrUpdate($uid, true, true, 20.0, 2.5, [
        '20kg'   => 2,
        '10kg'   => 4,
        '5kg'    => 4,
        '2.5kg'  => 4,
        '1.25kg' => 4,
        '0.5kg'  => 2
    ], 'home_weights');
    $gem = new GeminiWorkoutService();
    $gem->generateWeeklyProgram($uid, 'intermediate', true);
    echo "SUCCESS: Demo user created! Email: {$email} | Password: {$password}\n";
} else {
    echo "Demo user already exists: {$email}\n";
}

