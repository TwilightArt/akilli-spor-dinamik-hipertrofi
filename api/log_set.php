<?php
declare(strict_types=1);

/**
 * Direct file fallback for /api/log_set.php
 * Forwards execution to App\Controllers\ApiController@logSet
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/gemini.php';
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/WorkoutLog.php';
require_once __DIR__ . '/../models/Equipment.php';
require_once __DIR__ . '/../models/Exercise.php';
require_once __DIR__ . '/../services/PlateSolverService.php';
require_once __DIR__ . '/../services/HypertrophyEngine.php';
require_once __DIR__ . '/../services/InjuryFilterService.php';
require_once __DIR__ . '/../services/GeminiWorkoutService.php';
require_once __DIR__ . '/../controllers/ApiController.php';

use App\Controllers\ApiController;

$controller = new ApiController();
$controller->logSet();
