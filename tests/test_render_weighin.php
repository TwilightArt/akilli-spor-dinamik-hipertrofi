<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/DailyWeighIn.php';
require_once __DIR__ . '/../controllers/WeighInController.php';

\App\Core\Security::startSession();
$_SESSION['user'] = [
    'id' => 1,
    'name' => 'Samet Altınöz',
    'email' => 'sametltnz@gmail.com',
    'role' => 'athlete',
    'fitness_level' => 'intermediate'
];

ob_start();
$ctrl = new App\Controllers\WeighInController();
$ctrl->index();
$html = ob_get_clean();

echo "Rendered Weigh-in HTML length: " . strlen($html) . "\n";
echo "Contains Tartı ve Kilo Trendi: " . (strpos($html, 'Tartı') !== false ? 'YES' : 'NO') . "\n";
