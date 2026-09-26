<?php
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (!file_exists($file)) {
        $parts = explode('\\', $relativeClass);
        if (count($parts) > 1) {
            $parts[0] = strtolower($parts[0]);
            $file = $baseDir . implode('/', $parts) . '.php';
        }
    }
    if (file_exists($file)) require_once $file;
});

\App\Core\Security::startSession();
$_SESSION['user'] = [
    'id' => 1,
    'name' => 'Samet Altınöz',
    'email' => 'sametltnz@gmail.com',
    'role' => 'athlete',
    'fitness_level' => 'intermediate'
];

$csrf = \App\Core\Security::generateCsrfToken();
$_POST = [
    'csrf_token'        => $csrf,
    'name'              => 'Samet Altınöz',
    'gender'            => 'male',
    'birth_date'        => '1995-05-15',
    'height_cm'         => '185.0',
    'current_weight_kg' => '80.0',
    'fitness_level'     => 'intermediate',
    'injuries'          => ['shoulder', 'wrist']
];
$_SERVER['REQUEST_METHOD'] = 'POST';

// We catch redirect exit
class MockProfileController extends \App\Controllers\ProfileController {
    protected function redirect(string $path): void {
        echo "Redirected to: {$path}\n";
    }
}

$ctrl = new MockProfileController();
$ctrl->update();

// Check updated user in DB
$user = (new \App\Models\User())->findById(1);
echo "Updated height in DB: {$user['height_cm']} cm\n";
echo "Updated weight in DB: {$user['current_weight_kg']} kg\n";

// Check injuries in DB
$inj = (new \App\Services\InjuryFilterService())->getActiveInjuredJoints(1);
echo "Active injuries in DB: " . implode(', ', $inj) . "\n";
