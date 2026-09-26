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

ob_start();
$ctrl = new App\Controllers\ProfileController();
$ctrl->show();
$html = ob_get_clean();

echo "Rendered Profile HTML length: " . strlen($html) . "\n";
echo "Contains Boy Uzunluğu: " . (strpos($html, 'Boy Uzunluğu') !== false ? 'YES' : 'NO') . "\n";
echo "Contains Eklem Hassasiyetleri: " . (strpos($html, 'Eklem Hassasiyetleri') !== false ? 'YES' : 'NO') . "\n";
echo "Contains Parola Değiştir: " . (strpos($html, 'Parola Değiştir') !== false ? 'YES' : 'NO') . "\n";
