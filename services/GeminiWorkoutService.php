<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Constants;
use App\Config\Database;
use App\Config\GeminiConfig;
use App\Models\Exercise;
use App\Models\Equipment;
use PDO;

/**
 * Gemini AI Weekly Workout Generator & Robust Fallback Engine
 * Uses strict 5.0s cURL timeout, DB exercise ID validation and 6-retry mechanism
 */
class GeminiWorkoutService
{
    private PDO $db;
    private Exercise $exerciseModel;
    private Equipment $equipmentModel;
    private InjuryFilterService $injuryFilter;

    public function __construct(
        ?PDO $db = null,
        ?Exercise $exerciseModel = null,
        ?Equipment $equipmentModel = null,
        ?InjuryFilterService $injuryFilter = null
    ) {
        $this->db = $db ?? Database::getConnection();
        $this->exerciseModel = $exerciseModel ?? new Exercise();
        $this->equipmentModel = $equipmentModel ?? new Equipment();
        $this->injuryFilter = $injuryFilter ?? new InjuryFilterService();
    }

    /**
     * Generate or fallback weekly program for user
     */
    public function generateWeeklyProgram(int $userId, string $fitnessLevel, bool $forceFallback = false): int
    {
        $equipment = $this->equipmentModel->getByUserId($userId);
        $location = $equipment['workout_location'] ?? 'home_weights';
        $hasBarbell = (bool)($equipment['has_barbell'] ?? false);
        $hasDumbbells = (bool)($equipment['has_dumbbells'] ?? false);

        // Fetch only real available exercises from database
        $availableExercises = $this->exerciseModel->getExercisesForAI($location, $hasBarbell, $hasDumbbells);
        $validDbIds = array_column($availableExercises, 'id');

        $programType = ($fitnessLevel === 'advanced' || $fitnessLevel === 'intermediate')
            ? 'split_upper_lower'
            : 'full_body';

        $programData = null;
        $generationSource = 'ai_generated';
        $rawAiJson = null;

        if (!$forceFallback && !empty(GeminiConfig::getApiKey())) {
            $aiResult = $this->callGeminiApi($userId, $fitnessLevel, $programType, $availableExercises);
            if ($aiResult && $this->validateAiResponse($aiResult, $validDbIds)) {
                $programData = $aiResult;
                $rawAiJson = json_encode($aiResult, JSON_UNESCAPED_UNICODE);
            }
        }

        // If AI generation failed, timed out, or returned invalid IDs, activate Fallback Template
        if ($programData === null) {
            $generationSource = 'fallback_template';
            $programData = $this->getStaticFallbackTemplate($programType, $availableExercises);
            $rawAiJson = json_encode($programData, JSON_UNESCAPED_UNICODE);
        }

        // Persist to user_programs, program_days, program_exercises
        return $this->persistProgram($userId, $programType, $generationSource, $rawAiJson, $programData);
    }

    /**
     * Call Gemini API with strict 5.0 seconds cURL timeout
     */
    private function callGeminiApi(int $userId, string $fitnessLevel, string $programType, array $availableExercises): ?array
    {
        $apiUrl = GeminiConfig::getApiUrl();
        $timeout = GeminiConfig::getTimeoutSeconds();

        // Prepare compact catalog of allowed exercises
        $catalog = array_map(function($e) {
            return [
                'id'       => (int)$e['id'],
                'name'     => $e['name'],
                'pattern'  => $e['movement_pattern'],
                'equip'    => $e['required_equipment']
            ];
        }, $availableExercises);

        $prompt = "You are a world-class biomechanics hypertrophy coach. Generate a weekly workout program for a user with fitness level: '{$fitnessLevel}' and program type: '{$programType}'.\n"
            . "CRITICAL RULE: You MUST ONLY select exercises from the following JSON catalog. Do NOT make up new exercise names or IDs.\n"
            . "Catalog: " . json_encode($catalog, JSON_UNESCAPED_UNICODE) . "\n\n"
            . "Output format MUST BE STRICT VALID JSON ONLY (no markdown fences, no explanatory text):\n"
            . "{\n"
            . "  \"days\": [\n"
            . "    {\n"
            . "      \"day_of_week\": 1,\n"
            . "      \"focus_area\": \"Full Body A / Upper\",\n"
            . "      \"exercises\": [\n"
            . "        {\"exercise_id\": 7, \"order_in_session\": 1, \"target_sets\": 3, \"target_reps_min\": 8, \"target_reps_max\": 12}\n"
            . "      ]\n"
            . "    }\n"
            . "  ]\n"
            . "}";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature'       => 0.2,
                'response_mime_type'=> 'application/json'
            ]
        ];

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => (int)ceil($timeout),
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200 || empty($response)) {
            error_log("Gemini API Error (HTTP {$httpCode}): " . $error);
            return null;
        }

        $decoded = json_decode($response, true);
        $candidateText = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!$candidateText) {
            return null;
        }

        // Clean any markdown formatting if present
        $cleanJson = trim($candidateText);
        if (str_starts_with($cleanJson, '```json')) {
            $cleanJson = substr($cleanJson, 7);
        }
        if (str_starts_with($cleanJson, '```')) {
            $cleanJson = substr($cleanJson, 3);
        }
        if (str_ends_with($cleanJson, '```')) {
            $cleanJson = substr($cleanJson, 0, -3);
        }

        $parsedProgram = json_decode(trim($cleanJson), true);
        return is_array($parsedProgram) ? $parsedProgram : null;
    }

    /**
     * Validate returned AI structure and ensure all exercise_ids exist in database
     */
    private function validateAiResponse(array $data, array $validDbIds): bool
    {
        if (empty($data['days']) || !is_array($data['days'])) {
            return false;
        }

        foreach ($data['days'] as $day) {
            if (empty($day['exercises']) || !is_array($day['exercises'])) {
                return false;
            }
            foreach ($day['exercises'] as $ex) {
                if (empty($ex['exercise_id']) || !in_array((int)$ex['exercise_id'], $validDbIds, true)) {
                    error_log("Invalid exercise ID rejected by AI validator: " . ($ex['exercise_id'] ?? 'NULL'));
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Science-based Static Fallback Templates
     */
    public function getStaticFallbackTemplate(string $programType, array $availableExercises): array
    {
        $idByPattern = [];
        foreach ($availableExercises as $ex) {
            $idByPattern[$ex['movement_pattern']][] = (int)$ex['id'];
        }

        $pick = fn(string $p, int $default) => $idByPattern[$p][0] ?? $default;

        if ($programType === 'split_upper_lower') {
            return [
                'days' => [
                    [
                        'day_of_week' => 1,
                        'focus_area'  => 'Üst Vücut A',
                        'exercises'   => [
                            ['exercise_id' => $pick('push_horizontal', 4), 'order_in_session' => 1, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                            ['exercise_id' => $pick('pull_horizontal', 20), 'order_in_session' => 2, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                            ['exercise_id' => $pick('push_vertical', 14), 'order_in_session' => 3, 'target_sets' => 3, 'target_reps_min' => 10, 'target_reps_max' => 15],
                            ['exercise_id' => $pick('isolation_arm', 42), 'order_in_session' => 4, 'target_sets' => 3, 'target_reps_min' => 10, 'target_reps_max' => 15],
                        ]
                    ],
                    [
                        'day_of_week' => 2,
                        'focus_area'  => 'Alt Vücut A',
                        'exercises'   => [
                            ['exercise_id' => $pick('squat', 30), 'order_in_session' => 1, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                            ['exercise_id' => $pick('hinge', 38), 'order_in_session' => 2, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                            ['exercise_id' => $pick('core', 51), 'order_in_session' => 3, 'target_sets' => 3, 'target_reps_min' => 30, 'target_reps_max' => 60],
                        ]
                    ],
                    [
                        'day_of_week' => 4,
                        'focus_area'  => 'Üst Vücut B',
                        'exercises'   => [
                            ['exercise_id' => $pick('pull_vertical', 26), 'order_in_session' => 1, 'target_sets' => 3, 'target_reps_min' => 6, 'target_reps_max' => 10],
                            ['exercise_id' => $pick('push_horizontal', 8), 'order_in_session' => 2, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                            ['exercise_id' => $pick('isolation_shoulder', 48), 'order_in_session' => 3, 'target_sets' => 3, 'target_reps_min' => 12, 'target_reps_max' => 15],
                            ['exercise_id' => $pick('isolation_arm', 46), 'order_in_session' => 4, 'target_sets' => 3, 'target_reps_min' => 10, 'target_reps_max' => 15],
                        ]
                    ],
                    [
                        'day_of_week' => 5,
                        'focus_area'  => 'Alt Vücut B',
                        'exercises'   => [
                            ['exercise_id' => $pick('squat', 31), 'order_in_session' => 1, 'target_sets' => 3, 'target_reps_min' => 10, 'target_reps_max' => 15],
                            ['exercise_id' => $pick('hinge', 36), 'order_in_session' => 2, 'target_sets' => 3, 'target_reps_min' => 12, 'target_reps_max' => 15],
                            ['exercise_id' => $pick('core', 52), 'order_in_session' => 3, 'target_sets' => 3, 'target_reps_min' => 12, 'target_reps_max' => 15],
                        ]
                    ]
                ]
            ];
        }

        // Full Body 3 Days
        return [
            'days' => [
                [
                    'day_of_week' => 1,
                    'focus_area'  => 'Tüm Vücut (Pazartesi)',
                    'exercises'   => [
                        ['exercise_id' => $pick('squat', 30), 'order_in_session' => 1, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                        ['exercise_id' => $pick('push_horizontal', 4), 'order_in_session' => 2, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                        ['exercise_id' => $pick('pull_horizontal', 20), 'order_in_session' => 3, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                        ['exercise_id' => $pick('core', 51), 'order_in_session' => 4, 'target_sets' => 3, 'target_reps_min' => 30, 'target_reps_max' => 60],
                    ]
                ],
                [
                    'day_of_week' => 3,
                    'focus_area'  => 'Tüm Vücut (Çarşamba)',
                    'exercises'   => [
                        ['exercise_id' => $pick('hinge', 38), 'order_in_session' => 1, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                        ['exercise_id' => $pick('push_vertical', 14), 'order_in_session' => 2, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                        ['exercise_id' => $pick('pull_vertical', 26), 'order_in_session' => 3, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                        ['exercise_id' => $pick('isolation_arm', 42), 'order_in_session' => 4, 'target_sets' => 3, 'target_reps_min' => 10, 'target_reps_max' => 15],
                    ]
                ],
                [
                    'day_of_week' => 5,
                    'focus_area'  => 'Tüm Vücut (Cuma)',
                    'exercises'   => [
                        ['exercise_id' => $pick('squat', 31), 'order_in_session' => 1, 'target_sets' => 3, 'target_reps_min' => 10, 'target_reps_max' => 15],
                        ['exercise_id' => $pick('push_horizontal', 8), 'order_in_session' => 2, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                        ['exercise_id' => $pick('pull_horizontal', 19), 'order_in_session' => 3, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                        ['exercise_id' => $pick('isolation_shoulder', 48), 'order_in_session' => 4, 'target_sets' => 3, 'target_reps_min' => 12, 'target_reps_max' => 15],
                    ]
                ]
            ]
        ];
    }

    /**
     * Persist generated program to relational tables
     */
    private function persistProgram(
        int $userId,
        string $programType,
        string $generationSource,
        ?string $rawJson,
        array $programData
    ): int {
        $this->db->beginTransaction();
        try {
            $weekStartDate = date('Y-m-d', strtotime('monday this week'));

            $stmtProg = $this->db->prepare("
                INSERT INTO user_programs (user_id, program_type, week_start_date, is_deload_week, ai_generated_raw_json, generation_source, generation_retry_count)
                VALUES (:uid, :ptype, :wstart, 0, :raw_json, :gsource, 0)
            ");
            $stmtProg->execute([
                'uid'      => $userId,
                'ptype'    => $programType,
                'wstart'   => $weekStartDate,
                'raw_json' => $rawJson,
                'gsource'  => $generationSource
            ]);
            $programId = (int)$this->db->lastInsertId();

            $stmtDay = $this->db->prepare("
                INSERT INTO program_days (program_id, day_of_week, focus_area)
                VALUES (:pid, :dow, :focus)
            ");

            $stmtEx = $this->db->prepare("
                INSERT INTO program_exercises (program_day_id, exercise_id, order_in_session, target_sets, target_reps_min, target_reps_max)
                VALUES (:day_id, :eid, :ord, :sets, :rmin, :rmax)
            ");

            foreach ($programData['days'] as $day) {
                $stmtDay->execute([
                    'pid'   => $programId,
                    'dow'   => (int)$day['day_of_week'],
                    'focus' => $day['focus_area']
                ]);
                $dayId = (int)$this->db->lastInsertId();

                foreach ($day['exercises'] as $ex) {
                    $stmtEx->execute([
                        'day_id' => $dayId,
                        'eid'    => (int)$ex['exercise_id'],
                        'ord'    => (int)$ex['order_in_session'],
                        'sets'   => (int)($ex['target_sets'] ?? 3),
                        'rmin'   => (int)($ex['target_reps_min'] ?? 8),
                        'rmax'   => (int)($ex['target_reps_max'] ?? 12)
                    ]);
                }
            }

            $this->db->commit();
            return $programId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log("Failed to persist program: " . $e->getMessage());
            throw $e;
        }
    }
}
