<?php
declare(strict_types=1);

/**
 * ============================================================================
 * Akıllı Spor & Dinamik Hipertrofi Platformu (v2.1 — Production)
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
require_once __DIR__ . '/config/gemini.php';
require_once __DIR__ . '/config/constants.php';
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
// Public & Authentication Routes (HTML Web)
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
// Workout & Live Session Routes (HTML Web)
// ----------------------------------------------------------------------------
$router->get('/workout/dashboard', [WorkoutController::class, 'dashboard'], ['auth']);
$router->get('/workout/live', [WorkoutController::class, 'liveSession'], ['auth']);
$router->get('/workout/live/{id}', [WorkoutController::class, 'liveSession'], ['auth']);
$router->get('/workout/summary', [WorkoutController::class, 'summary'], ['auth']);
$router->post('/workout/end', [WorkoutController::class, 'endSession'], ['auth']);
$router->post('/workout/custom_program', [WorkoutController::class, 'createCustomProgram'], ['auth']);

// ----------------------------------------------------------------------------
// Weigh-In & Analytics Routes (HTML Web)
// ----------------------------------------------------------------------------
$router->get('/weigh-in', [WeighInController::class, 'index'], ['auth']);
$router->post('/weigh-in', [WeighInController::class, 'save'], ['auth']);

$router->get('/analytics', [AnalyticsController::class, 'index'], ['auth']);
$router->post('/analytics/sample_data', [AnalyticsController::class, 'sampleData'], ['auth']);
$router->get('/workout/progress', [AnalyticsController::class, 'index'], ['auth']);


// ----------------------------------------------------------------------------
// Profile & Calculators Routes (HTML Web)
// ----------------------------------------------------------------------------
$router->get('/profile', [ProfileController::class, 'show'], ['auth']);
$router->post('/profile', [ProfileController::class, 'update'], ['auth']);
$router->post('/profile/password', [ProfileController::class, 'updatePassword'], ['auth']);

$router->get('/calculators/1rm', [CalculatorController::class, 'index'], ['auth']);
$router->get('/calculators/tdee', [CalculatorController::class, 'tdee'], ['auth']);
$router->get('/calculators/water', [CalculatorController::class, 'water'], ['auth']);
$router->get('/calculators/rpe', [CalculatorController::class, 'rpe'], ['auth']);
$router->get('/tools/hiit', [CalculatorController::class, 'hiit'], ['auth']);
$router->get('/workout/exercises', [WorkoutController::class, 'exercisesLibrary'], ['auth']);

// ============================================================================
// RESTFUL JSON API ENDPOINTS (FAZ 1 - Modern Mobile/SPA Backend)
// ============================================================================

// 1. Auth & Session API
$router->get('/api/auth/csrf', [ApiController::class, 'csrf']);
$router->post('/api/auth/login', [ApiController::class, 'login']);
$router->post('/api/auth/register', [ApiController::class, 'register']);
$router->post('/api/auth/logout', [ApiController::class, 'logout']);
$router->get('/api/auth/logout', [ApiController::class, 'logout']);
$router->get('/api/auth/me', [ApiController::class, 'me']);

// 2. Workout & Engine API
$router->get('/api/workout/dashboard', [ApiController::class, 'workoutDashboard'], ['api_auth']);
$router->get('/api/workout/live', [ApiController::class, 'workoutLive'], ['api_auth']);
$router->get('/api/workout/live/{id}', [ApiController::class, 'workoutLive'], ['api_auth']);
$router->get('/api/workout/exercises', [ApiController::class, 'exercisesLibrary'], ['api_auth']);
$router->post('/api/workout/log-set', [ApiController::class, 'logSet'], ['api_auth']);
$router->post('/api/workout/log_set', [ApiController::class, 'logSet'], ['api_auth']);
$router->post('/api/workout/end', [ApiController::class, 'workoutEnd'], ['api_auth']);
$router->get('/api/workout/ai-coach', [ApiController::class, 'getAiCoach'], ['api_auth']);
$router->post('/api/workout/coach', [ApiController::class, 'dynamicCoach'], ['api_auth']);
$router->post('/api/workout/retry-program', [ApiController::class, 'retryProgram'], ['api_auth']);
$router->post('/api/workout/custom-program', [ApiController::class, 'customProgram'], ['api_auth']);

// 3. Analytics API
$router->get('/api/analytics', [ApiController::class, 'analytics'], ['api_auth']);
$router->get('/api/exercise_progress', [ApiController::class, 'exerciseProgress'], ['api_auth']);

// 4. Profile & Weigh-In API
$router->get('/api/profile', [ApiController::class, 'profile'], ['api_auth']);
$router->post('/api/profile', [ApiController::class, 'updateProfile'], ['api_auth']);
$router->post('/api/profile/password', [ApiController::class, 'updatePassword'], ['api_auth']);
$router->get('/api/weigh-in', [ApiController::class, 'weighIn'], ['api_auth']);
$router->post('/api/weigh-in', [ApiController::class, 'saveWeighIn'], ['api_auth']);

// 5. Calculators API
$router->get('/api/calculators/1rm', [ApiController::class, 'calculator1rm']);
$router->post('/api/calculators/1rm', [ApiController::class, 'calculator1rm']);
$router->get('/api/calculators/tdee', [ApiController::class, 'calculatorTdee']);
$router->post('/api/calculators/tdee', [ApiController::class, 'calculatorTdee']);
$router->get('/api/calculators/water', [ApiController::class, 'calculatorWater']);
$router->post('/api/calculators/water', [ApiController::class, 'calculatorWater']);

// 6. Onboarding API
$router->get('/api/onboarding/assessment', [ApiController::class, 'getAssessment'], ['api_auth']);
$router->post('/api/onboarding/assessment', [ApiController::class, 'saveAssessment'], ['api_auth']);
$router->get('/api/onboarding/equipment', [ApiController::class, 'getEquipment'], ['api_auth']);
$router->post('/api/onboarding/equipment', [ApiController::class, 'saveEquipment'], ['api_auth']);

// Legacy direct compatibility routes
$router->post('/api/log_set', [ApiController::class, 'logSet'], ['api_auth']);
$router->post('/api/log_set.php', [ApiController::class, 'logSet'], ['api_auth']);
$router->post('/api/retry_program', [ApiController::class, 'retryProgram'], ['api_auth']);

// Dispatch Current HTTP Request
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($uri, $method);
