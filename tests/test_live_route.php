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

$router = new \App\Core\Router();
$router->get('/workout/live/{id}', [\App\Controllers\WorkoutController::class, 'liveSession'], ['auth']);

ob_start();
try {
    $router->dispatch('/workout/live/11', 'GET');
    $output = ob_get_clean();
    echo "SUCCESS: Dispatched /workout/live/11, output length: " . strlen($output) . "\n";
    echo "Contains focus_area: " . (strpos($output, 'Üst Vücut A') !== false ? 'YES' : 'NO') . "\n";
} catch (\Throwable $e) {
    ob_end_clean();
    echo "FAILED with exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
