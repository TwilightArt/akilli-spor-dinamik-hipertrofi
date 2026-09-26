<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/RfiService.php';

$rfi = new App\Services\RfiService();
$res = $rfi->evaluateFitnessTier(15, 5, 25, 60);
echo "Tier result for 15 push, 5 chin, 25 squat, 60s plank:\n";
print_r($res);

$userModel = new App\Models\User();
$user32 = $userModel->findById(32);
echo "\nUser 32 before update:\n";
echo "Level: {$user32['fitness_level']}, Score: " . ($user32['calisthenic_score'] ?? 'null') . "\n";
