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

$token = \App\Core\Security::generateCsrfToken();
$_POST = [
    'csrf_token' => $token,
    'session_id' => '2',
    'exercise_id' => '1',
    'set_number' => '1',
    'set_type' => 'reps',
    'actual_weight' => '0',
    'target_reps' => '12',
    'actual_reps' => '12',
    'rir_bucket' => '1-2',
    'form_breakdown' => '0'
];

// Mock headers for Security::verifyRequestCsrf
$_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
$ctrl = new App\Controllers\ApiController();
$ctrl->logSet();
$json = ob_get_clean();

echo "API logSet response: " . $json . "\n";
