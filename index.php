<?php
declare(strict_types=1);

/**
 * ============================================================================
 * Akıllı Spor & Dinamik Hipertrofi Platformu (v2.1 — Full Production)
 * Front Controller & Routing Entry Point
 * PHP 8.2+ (Strict Types, OOP, PDO)
 * ============================================================================
 */

// Support PHP built-in development server (php -S) static files
if (php_sapi_name() === 'cli-server') {
    $filePath = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($filePath)) {
        return false;
    }
}

// Error logging: display errors in local development, log in production
$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', ['127.0.0.1', '::1', 'localhost'], true) || (php_sapi_name() === 'cli-server');
error_reporting(E_ALL);
ini_set('display_errors', $isLocal ? '1' : '0');
ini_set('log_errors', '1');

// Simple PSR-4 style autoloader
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // Lowercase first directory if needed (e.g. App\Core -> core/)
    if (!file_exists($file)) {
        $parts = explode('\\', $relativeClass);
        if (count($parts) > 1) {
            $parts[0] = strtolower($parts[0]);
            $file = $baseDir . implode('/', $parts) . '.php';
        }
    }

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Router;
require_once __DIR__ . '/config/database.php';
use App\Core\Security;
use App\Controllers\AuthController;
use App\Controllers\OnboardingController;
use App\Controllers\WorkoutController;
use App\Controllers\WeighInController;
use App\Controllers\ProfileController;
use App\Controllers\AnalyticsController;
use App\Controllers\ApiController;
use App\Controllers\CalculatorController;

// Start secure session
Security::startSession();

$router = new Router();

// ----------------------------------------------------------------------------
// Public & Authentication Routes
// ----------------------------------------------------------------------------
$router->get('/', function() {
    if (!empty($_SESSION['user'])) {
        header('Location: /workout/dashboard');
    } else {
        header('Location: /login');
    }
    exit;
});

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);

$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);

$router->get('/logout', [AuthController::class, 'logout']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword']);

$router->get('/reset-password', [AuthController::class, 'showResetPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);

// ----------------------------------------------------------------------------
// Onboarding Routes
// ----------------------------------------------------------------------------
$router->get('/onboarding/assessment', [OnboardingController::class, 'showAssessment'], ['auth']);
$router->post('/onboarding/assessment', [OnboardingController::class, 'saveAssessment'], ['auth']);

$router->get('/onboarding/equipment', [OnboardingController::class, 'showEquipment'], ['auth']);
$router->post('/onboarding/equipment', [OnboardingController::class, 'saveEquipment'], ['auth']);

// ----------------------------------------------------------------------------
// Workout & Live Session Routes
// ----------------------------------------------------------------------------
$router->get('/workout/dashboard', [WorkoutController::class, 'dashboard'], ['auth']);
$router->get('/workout/live', [WorkoutController::class, 'liveSession'], ['auth']);
$router->get('/workout/live/{id}', [WorkoutController::class, 'liveSession'], ['auth']);
$router->post('/workout/end', [WorkoutController::class, 'endSession'], ['auth']);
$router->post('/workout/custom_program', [WorkoutController::class, 'createCustomProgram'], ['auth']);

// ----------------------------------------------------------------------------
// Calculators Routes
// ----------------------------------------------------------------------------
$router->get('/calculators/1rm', [CalculatorController::class, 'index'], ['auth']);

// ----------------------------------------------------------------------------
// Weigh-In & Trend Routes
// ----------------------------------------------------------------------------
$router->get('/weigh-in', [WeighInController::class, 'index'], ['auth']);
$router->post('/weigh-in', [WeighInController::class, 'save'], ['auth']);

// ----------------------------------------------------------------------------
// Progressive Overload & Weight Analytics Routes
// ----------------------------------------------------------------------------
$router->get('/analytics', [AnalyticsController::class, 'index'], ['auth']);
$router->get('/workout/progress', [AnalyticsController::class, 'index'], ['auth']);
$router->post('/analytics/sample_data', [AnalyticsController::class, 'seedSampleData'], ['auth']);
$router->get('/api/exercise_progress', [AnalyticsController::class, 'apiProgress'], ['auth']);

// ----------------------------------------------------------------------------
// Profile & Biometric Settings Routes
// ----------------------------------------------------------------------------
$router->get('/profile', [ProfileController::class, 'show'], ['auth']);
$router->post('/profile', [ProfileController::class, 'update'], ['auth']);
$router->post('/profile/password', [ProfileController::class, 'updatePassword'], ['auth']);

// ----------------------------------------------------------------------------
// API AJAX Endpoints
// ----------------------------------------------------------------------------
$router->post('/api/log_set', [ApiController::class, 'logSet'], ['auth']);
$router->post('/api/log_set.php', [ApiController::class, 'logSet'], ['auth']); // Legacy direct file route
$router->post('/api/retry_program', [ApiController::class, 'retryProgram'], ['auth']);

// Dispatch Current HTTP Request
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($uri, $method);
