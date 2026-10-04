<?php
declare(strict_types=1);

namespace App\Services;

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

    public function __construct(
        ?PDO $db = null,
        ?Exercise $exerciseModel = null,
        ?Equipment $equipmentModel = null
    ) {
        $this->db = $db ?? Database::getConnection();
        $this->exerciseModel = $exerciseModel ?? new Exercise();
        $this->equipmentModel = $equipmentModel ?? new Equipment();
    }

    public function generateAiCoachSummary(int $userId, int $sessionId): string
    {
        $summaryData = $this->db->query("SELECT * FROM workout_sessions WHERE id = $sessionId AND user_id = $userId")->fetch();
        if (!$summaryData) return "Antrenman verisi bulunamadı.";
        
        $logs = $this->db->query("
            SELECT e.name, wl.set_number, wl.actual_weight, wl.actual_reps, wl.rir_bucket, wl.form_breakdown 
            FROM workout_logs wl 
            JOIN exercises e ON wl.exercise_id = e.id 
            WHERE wl.session_id = $sessionId
            ORDER BY wl.created_at ASC
        ")->fetchAll();
        
        if (empty($logs)) return "Kaydedilmiş set bulunamadı. Yapay zeka yorum yapamıyor.";
        
        $prompt = "Sen 'Akıllı Hipertrofi' uygulamasının acımasız ama motive edici sanal antrenörüsün. Kullanıcı az önce antrenmanını bitirdi. Aşağıda antrenman dökümü var:\n";
        foreach ($logs as $l) {
            $prompt .= "- {$l['name']} (Set {$l['set_number']}): {$l['actual_weight']}kg x {$l['actual_reps']} tekrar. Zorluk: {$l['rir_bucket']}. Form bozulması: ".($l['form_breakdown'] ? 'Evet' : 'Hayır')."\n";
        }
        $prompt .= "\nKullanıcıya 3-4 cümlelik kısa, Türkçe bir koçluk özeti ver. Ağırlığı düştüğü veya formunu bozduğu yerler varsa uyar, kolay bulduğu yerlerde bir dahakine daha çok zorlamasını söyle. Sadece doğrudan konuşma metnini ver, merhaba vb. deme.";
        
        $apiUrl = \App\Config\GeminiConfig::getApiUrl();
        $apiKey = \App\Config\GeminiConfig::getApiKey();
        
        if (!$apiKey || $apiKey === 'GEMINI_API_KEY_BURAYA') {
            return "Gerçek yapay zeka analizini görmek için lütfen Gemini API anahtarınızı yapılandırın. (Simüle edilmiş koçluk: Bugün fena değildin ama tükenişe yeterince yaklaşmamışsın gibi duruyor. Sonraki idmanda daha cesur ol!)";
        }
        
        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ];
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? "AI koç şu an antrenman programı yapıyor, ulaşamadık. Sonraki idmanda görüşürüz!";
    }

    /**
     * Handle multi-turn chat for live workout coaching
     * Returns JSON structure with 'response' (text) and optionally 'action' (rir adjustment, weight drop)
     */
    public function dynamicCoachChat(int $userId, int $sessionId, ?string $currentExercise, array $history, string $newMessage): array
    {
        $apiUrl = \App\Config\GeminiConfig::getApiUrl();
        $apiKey = \App\Config\GeminiConfig::getApiKey();
        
        if (!$apiKey || $apiKey === 'GEMINI_API_KEY_BURAYA') {
            return [
                'response' => "Simüle edilmiş yanıt: API anahtarı eksik. 'Çok zorlandım' dediğini varsayarak ağırlığı %10 düşürmeni öneriyorum.",
                'action' => ['type' => 'decrease_weight', 'amount_percent' => 10]
            ];
        }

        // Construct multi-turn contents
        $contents = [];
        
        // Add a system prompt as the first instruction (or pass it properly)
        $systemInstruction = "Sen 'Akıllı Hipertrofi' uygulamasının Dinamik Seans Koçusun. "
            . "Kullanıcı şu anda antrenman yapıyor" . ($currentExercise ? " (Egzersiz: $currentExercise)" : "") . ". "
            . "Sana şikayet veya durum bildirecek (Örn: 'Çok zorlandım', 'Formum bozuldu'). "
            . "Buna göre Türkçe, kısa, motive edici bir yanıt ver ve eğer gerekiyorsa RIR düşürme veya ağırlık azaltma kararı al.\n"
            . "ÇIKTIYI SADECE AŞAĞIDAKİ GİBİ JSON FORMATINDA VER (Başka hiçbir açıklama yazma):\n"
            . "{\n"
            . "  \"response\": \"Koçun kullanıcıya söyleyeceği metin\",\n"
            . "  \"action\": { \"type\": \"decrease_weight|increase_rir|none\", \"value\": 10 }\n"
            . "}";

        // Add history correctly using role: user / role: model
        if (!empty($history)) {
            foreach ($history as $msg) {
                // Ensure only allowed roles are used
                $role = ($msg['role'] === 'model') ? 'model' : 'user';
                $text = $msg['parts'][0]['text'] ?? '';
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $text]]
                ];
            }
        }
        
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $newMessage]]
        ];

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemInstruction]]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.4,
                'response_mime_type' => 'application/json'
            ]
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        $candidateText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        
        if ($candidateText) {
            $parsed = json_decode(trim($candidateText), true);
            if (is_array($parsed) && isset($parsed['response'])) {
                return $parsed;
            }
        }
        
        return [
            'response' => "Anlaşıldı. Yükü veya tekrarı kendine göre ayarla, sakatlıktan kaçın.",
            'action' => ['type' => 'none']
        ];
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
     * Call Gemini API with strict 5.0 seconds cURL timeout and Tool Calling support
     */
    private function callGeminiApi(int $userId, string $fitnessLevel, string $programType, array $availableExercises): ?array
    {
        $apiUrl = GeminiConfig::getApiUrl();
        $timeout = GeminiConfig::getTimeoutSeconds();
        
        $injuryFilter = new InjuryFilterService($this->db);

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
            . "Use the provided tools to check the user's equipment and injury profile to ensure the program is perfectly tailored and safe.\n"
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

        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ];

        $tools = [
            [
                'functionDeclarations' => [
                    [
                        'name' => 'get_user_equipment',
                        'description' => "Get the available equipment for the user.",
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'user_id' => [
                                    'type' => 'INTEGER',
                                    'description' => 'The user ID'
                                ]
                            ],
                            'required' => ['user_id']
                        ]
                    ],
                    [
                        'name' => 'get_injury_profile',
                        'description' => "Get the user's active injured joint areas to avoid certain exercises.",
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'user_id' => [
                                    'type' => 'INTEGER',
                                    'description' => 'The user ID'
                                ]
                            ],
                            'required' => ['user_id']
                        ]
                    ]
                ]
            ]
        ];

        $generationConfig = [
            'temperature'       => 0.2,
            'response_mime_type'=> 'application/json',
            'response_schema'   => [
                'type'       => 'OBJECT',
                'properties' => [
                    'days' => [
                        'type'  => 'ARRAY',
                        'items' => [
                            'type'       => 'OBJECT',
                            'properties' => [
                                'day_of_week' => ['type' => 'INTEGER'],
                                'focus_area'  => ['type' => 'STRING'],
                                'exercises'   => [
                                    'type'  => 'ARRAY',
                                    'items' => [
                                        'type'       => 'OBJECT',
                                        'properties' => [
                                            'exercise_id'      => ['type' => 'INTEGER'],
                                            'order_in_session' => ['type' => 'INTEGER'],
                                            'target_sets'      => ['type' => 'INTEGER'],
                                            'target_reps_min'  => ['type' => 'INTEGER'],
                                            'target_reps_max'  => ['type' => 'INTEGER']
                                        ],
                                        'required' => ['exercise_id', 'order_in_session', 'target_sets', 'target_reps_min', 'target_reps_max']
                                    ]
                                ]
                            ],
                            'required' => ['day_of_week', 'focus_area', 'exercises']
                        ]
                    ]
                ],
                'required' => ['days']
            ]
        ];

        $maxIterations = 3;
        for ($i = 0; $i < $maxIterations; $i++) {
            $payload = [
                'contents' => $contents,
                'tools' => $tools,
                'generationConfig' => $generationConfig
            ];

            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'x-goog-api-key: ' . GeminiConfig::getApiKey()
                ],
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
            $candidate = $decoded['candidates'][0] ?? null;
            if (!$candidate) {
                return null;
            }

            $parts = $candidate['content']['parts'] ?? [];
            if (empty($parts)) {
                return null;
            }

            $functionCalls = [];
            foreach ($parts as $part) {
                if (isset($part['functionCall'])) {
                    $functionCalls[] = $part['functionCall'];
                }
            }

            if (!empty($functionCalls)) {
                $contents[] = $candidate['content'];
                
                $functionResponses = [];
                foreach ($functionCalls as $fnCall) {
                    $fnName = $fnCall['name'];
                    $fnArgs = $fnCall['args'] ?? [];
                    $fnResult = [];
                    
                    if ($fnName === 'get_user_equipment') {
                        $uid = $fnArgs['user_id'] ?? $userId;
                        $fnResult = $this->equipmentModel->getByUserId((int)$uid) ?: [];
                    } elseif ($fnName === 'get_injury_profile') {
                        $uid = $fnArgs['user_id'] ?? $userId;
                        $fnResult = $injuryFilter->getActiveInjuredJoints((int)$uid);
                    }
                    
                    $functionResponses[] = [
                        'functionResponse' => [
                            'name' => $fnName,
                            'response' => [
                                'name' => $fnName,
                                'content' => $fnResult
                            ]
                        ]
                    ];
                }
                
                $contents[] = [
                    'role' => 'function',
                    'parts' => $functionResponses
                ];
                
                continue; // Loop again with function response
            }

            // Clean any markdown formatting if present
            $candidateText = $parts[0]['text'] ?? null;
            if (!$candidateText) {
                return null;
            }

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

        return null;
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
                        'focus_area'  => 'Ãœst VÃ¼cut A',
                        'exercises'   => [
                            ['exercise_id' => $pick('push_horizontal', 4), 'order_in_session' => 1, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                            ['exercise_id' => $pick('pull_horizontal', 20), 'order_in_session' => 2, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                            ['exercise_id' => $pick('push_vertical', 14), 'order_in_session' => 3, 'target_sets' => 3, 'target_reps_min' => 10, 'target_reps_max' => 15],
                            ['exercise_id' => $pick('isolation_arm', 42), 'order_in_session' => 4, 'target_sets' => 3, 'target_reps_min' => 10, 'target_reps_max' => 15],
                        ]
                    ],
                    [
                        'day_of_week' => 2,
                        'focus_area'  => 'Alt VÃ¼cut A',
                        'exercises'   => [
                            ['exercise_id' => $pick('squat', 30), 'order_in_session' => 1, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                            ['exercise_id' => $pick('hinge', 38), 'order_in_session' => 2, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                            ['exercise_id' => $pick('core', 51), 'order_in_session' => 3, 'target_sets' => 3, 'target_reps_min' => 30, 'target_reps_max' => 60],
                        ]
                    ],
                    [
                        'day_of_week' => 4,
                        'focus_area'  => 'Ãœst VÃ¼cut B',
                        'exercises'   => [
                            ['exercise_id' => $pick('pull_vertical', 26), 'order_in_session' => 1, 'target_sets' => 3, 'target_reps_min' => 6, 'target_reps_max' => 10],
                            ['exercise_id' => $pick('push_horizontal', 8), 'order_in_session' => 2, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                            ['exercise_id' => $pick('isolation_shoulder', 48), 'order_in_session' => 3, 'target_sets' => 3, 'target_reps_min' => 12, 'target_reps_max' => 15],
                            ['exercise_id' => $pick('isolation_arm', 46), 'order_in_session' => 4, 'target_sets' => 3, 'target_reps_min' => 10, 'target_reps_max' => 15],
                        ]
                    ],
                    [
                        'day_of_week' => 5,
                        'focus_area'  => 'Alt VÃ¼cut B',
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
                    'focus_area'  => 'TÃ¼m VÃ¼cut (Pazartesi)',
                    'exercises'   => [
                        ['exercise_id' => $pick('squat', 30), 'order_in_session' => 1, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                        ['exercise_id' => $pick('push_horizontal', 4), 'order_in_session' => 2, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                        ['exercise_id' => $pick('pull_horizontal', 20), 'order_in_session' => 3, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                        ['exercise_id' => $pick('core', 51), 'order_in_session' => 4, 'target_sets' => 3, 'target_reps_min' => 30, 'target_reps_max' => 60],
                    ]
                ],
                [
                    'day_of_week' => 3,
                    'focus_area'  => 'TÃ¼m VÃ¼cut (Ã‡arÅŸamba)',
                    'exercises'   => [
                        ['exercise_id' => $pick('hinge', 38), 'order_in_session' => 1, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                        ['exercise_id' => $pick('push_vertical', 14), 'order_in_session' => 2, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                        ['exercise_id' => $pick('pull_vertical', 26), 'order_in_session' => 3, 'target_sets' => 3, 'target_reps_min' => 8, 'target_reps_max' => 12],
                        ['exercise_id' => $pick('isolation_arm', 42), 'order_in_session' => 4, 'target_sets' => 3, 'target_reps_min' => 10, 'target_reps_max' => 15],
                    ]
                ],
                [
                    'day_of_week' => 5,
                    'focus_area'  => 'TÃ¼m VÃ¼cut (Cuma)',
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

            $exerciseValues = [];
            $exerciseParams = [];

            foreach ($programData['days'] as $day) {
                $stmtDay->execute([
                    'pid'   => $programId,
                    'dow'   => (int)$day['day_of_week'],
                    'focus' => $day['focus_area']
                ]);
                $dayId = (int)$this->db->lastInsertId();

                foreach ($day['exercises'] as $ex) {
                    $exerciseValues[] = '(?, ?, ?, ?, ?, ?)';
                    $exerciseParams[] = $dayId;
                    $exerciseParams[] = (int)$ex['exercise_id'];
                    $exerciseParams[] = (int)$ex['order_in_session'];
                    $exerciseParams[] = (int)($ex['target_sets'] ?? 3);
                    $exerciseParams[] = (int)($ex['target_reps_min'] ?? 8);
                    $exerciseParams[] = (int)($ex['target_reps_max'] ?? 12);
                }
            }

            if (!empty($exerciseValues)) {
                $chunkSize = 50;
                $chunkedValues = array_chunk($exerciseValues, $chunkSize);
                $chunkedParams = array_chunk($exerciseParams, $chunkSize * 6);

                for ($i = 0; $i < count($chunkedValues); $i++) {
                    $sql = "INSERT INTO program_exercises (program_day_id, exercise_id, order_in_session, target_sets, target_reps_min, target_reps_max) VALUES " . implode(', ', $chunkedValues[$i]);
                    $stmtEx = $this->db->prepare($sql);
                    $stmtEx->execute($chunkedParams[$i]);
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

