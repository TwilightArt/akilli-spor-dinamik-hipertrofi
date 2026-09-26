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
$_SESSION = []; // unauthenticated guest

$csrfToken = \App\Core\Security::generateCsrfToken();
$_POST = [
    'csrf_token' => $csrfToken,
    'name' => 'Ahmet Yılmaz',
    'email' => 'ahmet_' . time() . '@test.com',
    'password' => 'secret12345',
    'gender' => 'male',
    'birth_date' => '1996-03-20',
    'height_cm' => '182.5',
    'current_weight_kg' => '81.0'
];

$_SERVER['REQUEST_METHOD'] = 'POST';

$ctrl = new App\Controllers\AuthController();
try {
    $ctrl->register();
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
echo "Flash message: " . json_encode($_SESSION['flash'] ?? null, JSON_UNESCAPED_UNICODE) . "\n";
echo "Session user: " . json_encode($_SESSION['user'] ?? null, JSON_UNESCAPED_UNICODE) . "\n";
