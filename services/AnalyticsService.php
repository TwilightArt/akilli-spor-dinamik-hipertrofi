<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;

class AnalyticsService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Get all exercises for which the user has logged sets, augmented with active program exercises
     */
    public function getUserExercisesList(int $userId): array
    {
        // 1. Exercises with actual logs
        $sql = "
            SELECT 
                e.id, 
                e.name, 
                e.movement_pattern, 
                e.primary_muscle_group, 
                e.required_equipment,
                COUNT(wl.id) as total_sets_logged,
                MAX(wl.actual_weight) as max_weight_ever,
                MIN(wl.actual_weight) as min_weight_ever,
                MAX(wl.actual_weight * (1.0 + (COALESCE(wl.actual_reps, 1) / 30.0))) as max_estimated_1rm,
                SUM(wl.actual_weight * COALESCE(wl.actual_reps, 1)) as total_volume_kg,
                MAX(wl.created_at) as last_trained_at,
                MIN(wl.created_at) as first_trained_at
            FROM workout_logs wl
            JOIN exercises e ON wl.exercise_id = e.id
            WHERE wl.user_id = :uid
            GROUP BY e.id, e.name, e.movement_pattern, e.primary_muscle_group, e.required_equipment
            ORDER BY total_sets_logged DESC, max_weight_ever DESC, e.name ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        $logged = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $existingIds = array_map(fn($row) => (int)$row['id'], $logged);

        // 2. Also get exercises from active program that haven't been logged yet
        $progSql = "
            SELECT DISTINCT
                e.id, 
                e.name, 
                e.movement_pattern, 
                e.primary_muscle_group, 
                e.required_equipment,
                0 as total_sets_logged,
                0.0 as max_weight_ever,
                0.0 as min_weight_ever,
                0.0 as max_estimated_1rm,
                0.0 as total_volume_kg,
                NULL as last_trained_at,
                NULL as first_trained_at
            FROM program_exercises pe
            JOIN program_days pd ON pe.program_day_id = pd.id
            JOIN exercises e ON pe.exercise_id = e.id
            WHERE pd.program_id = (SELECT id FROM user_programs WHERE user_id = :uid ORDER BY id DESC LIMIT 1)
        ";
        $stmt2 = $this->db->prepare($progSql);
        $stmt2->execute(['uid' => $userId]);
        $unlogged = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        foreach ($unlogged as $uRow) {
            if (!in_array((int)$uRow['id'], $existingIds, true)) {
                $logged[] = $uRow;
            }
        }

        return $logged;
    }

    /**
     * Get session-by-session timeline for an exercise (for Chart.js timeline)
     */
    public function getExerciseTimeline(int $userId, int $exerciseId): array
    {
        $sql = "
            SELECT 
                DATE(ws.started_at) as session_date,
                ws.id as session_id,
                MAX(wl.actual_weight) as peak_weight,
                ROUND(MAX(wl.actual_weight * (1.0 + (COALESCE(wl.actual_reps, 1) / 30.0))), 1) as peak_estimated_1rm,
                ROUND(SUM(wl.actual_weight * COALESCE(wl.actual_reps, 1)), 1) as session_volume,
                COUNT(wl.id) as set_count,
                MAX(wl.actual_reps) as max_reps_at_peak
            FROM workout_logs wl
            JOIN workout_sessions ws ON wl.session_id = ws.id
            WHERE wl.user_id = :uid AND wl.exercise_id = :eid
            GROUP BY ws.id, DATE(ws.started_at)
            ORDER BY ws.started_at ASC, wl.id ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $userId, 'eid' => $exerciseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get detailed set-by-set logs for an exercise with Personal Record (PR) flags
     */
    public function getExerciseDetailedSets(int $userId, int $exerciseId): array
    {
        $sql = "
            SELECT 
                wl.id,
                wl.session_id,
                wl.set_number,
                wl.set_type,
                wl.actual_weight,
                wl.actual_reps,
                wl.duration_seconds,
                wl.rir_bucket,
                wl.form_breakdown,
                wl.created_at,
                ROUND(wl.actual_weight * (1.0 + (COALESCE(wl.actual_reps, 1) / 30.0)), 1) as estimated_1rm,
                ws.started_at as session_started_at
            FROM workout_logs wl
            JOIN workout_sessions ws ON wl.session_id = ws.id
            WHERE wl.user_id = :uid AND wl.exercise_id = :eid
            ORDER BY wl.created_at DESC, wl.set_number DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $userId, 'eid' => $exerciseId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Determine all-time PR weight
        $allTimeMax = 0.0;
        foreach ($rows as $r) {
            $w = (float)$r['actual_weight'];
            if ($w > $allTimeMax) {
                $allTimeMax = $w;
            }
        }

        foreach ($rows as &$r) {
            $r['is_pr'] = ((float)$r['actual_weight'] >= $allTimeMax && $allTimeMax > 0);
        }
        unset($r);

        return $rows;
    }

    /**
     * Get comparative all-time peak weight across ALL exercises trained by user
     * Directly answers: "hareket hareket kaç kiloya kadar çıkılmış diye grafikle göster"
     */
    public function getAllExercisesPeakWeights(int $userId): array
    {
        $sql = "
            SELECT 
                e.id,
                e.name,
                e.primary_muscle_group,
                e.movement_pattern,
                MAX(wl.actual_weight) as peak_weight,
                ROUND(MAX(wl.actual_weight * (1.0 + (COALESCE(wl.actual_reps, 1) / 30.0))), 1) as peak_1rm,
                COUNT(wl.id) as total_sets
            FROM workout_logs wl
            JOIN exercises e ON wl.exercise_id = e.id
            WHERE wl.user_id = :uid
            GROUP BY e.id, e.name, e.primary_muscle_group, e.movement_pattern
            ORDER BY peak_weight DESC, e.name ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Seed realistic 4-week progressive overload history for demo and immediate testing
     */
    public function seedRealisticOverloadHistory(int $userId): void
    {
        // 1. Get exercises from user's active program or fallback to top compound exercises
        $sql = "
            SELECT DISTINCT e.id, e.name, e.movement_pattern
            FROM program_exercises pe
            JOIN program_days pd ON pe.program_day_id = pd.id
            JOIN exercises e ON pe.exercise_id = e.id
            WHERE pd.program_id = (SELECT id FROM user_programs WHERE user_id = :uid ORDER BY id DESC LIMIT 1)
            LIMIT 6
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($exercises)) {
            // Fallback to top compound exercises from database
            $exercises = $this->db->query("
                SELECT id, name, movement_pattern FROM exercises 
                WHERE name IN ('Barbell Flat Bench Press', 'Barbell Back Squat', 'Overhead Press (OHP)', 'Barbell Bent-Over Row', 'Dumbbell Biceps Curl', 'Bench Triceps Dip (Bodyweight)')
                LIMIT 6
            ")->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($exercises)) {
            $exercises = $this->db->query("SELECT id, name, movement_pattern FROM exercises LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
        }

        // Base weights for common movements
        $baseWeights = [
            'Barbell Flat Bench Press' => 50.0,
            'Barbell Back Squat'       => 60.0,
            'Overhead Press (OHP)'     => 30.0,
            'Barbell Bent-Over Row'    => 45.0,
            'Dumbbell Biceps Curl'     => 12.0,
            'Wall Push-Up'             => 25.0,
            'Door Towel Row'           => 30.0,
            'Pike Push-Up with Knees'  => 20.0,
            'Knee Plank (Duration)'    => 0.0,
            'Chair Assisted Bodyweight Squat' => 20.0
        ];

        // 4 Weeks, 3 workouts per week = 12 historical sessions
        $now = time();
        $this->db->beginTransaction();

        try {
            $logParams = [];
            $logPlaceholders = [];

            for ($week = 1; $week <= 4; $week++) {
                for ($sessionIdx = 1; $sessionIdx <= 3; $sessionIdx++) {
                    $daysAgo = (4 - $week) * 7 + (3 - $sessionIdx) * 2 + 1;
                    $sessionTimestamp = date('Y-m-d H:i:s', $now - ($daysAgo * 86400));
                    $endTimestamp = date('Y-m-d H:i:s', $now - ($daysAgo * 86400) + 3300);

                    // Insert workout session
                    $sStmt = $this->db->prepare("
                        INSERT INTO workout_sessions (user_id, started_at, ended_at)
                        VALUES (:uid, :start, :end)
                    ");
                    $sStmt->execute([
                        'uid'   => $userId,
                        'start' => $sessionTimestamp,
                        'end'   => $endTimestamp
                    ]);
                    $sessionId = (int)$this->db->lastInsertId();

                    // Progressive overload increments over weeks
                    $weekIncrement = ($week - 1) * 2.5; // +0kg, +2.5kg, +5.0kg, +7.5kg

                    // Add logs for 3-4 exercises per session
                    $sessionExercises = array_slice($exercises, ($sessionIdx % 2 == 1 ? 0 : 2), 3);
                    if (empty($sessionExercises)) {
                        $sessionExercises = array_slice($exercises, 0, 3);
                    }

                    foreach ($sessionExercises as $ex) {
                        $exName = $ex['name'];
                        $baseW = $baseWeights[$exName] ?? 35.0;
                        $actualW = $baseW + $weekIncrement;

                        // 3 Sets per exercise
                        for ($setNum = 1; $setNum <= 3; $setNum++) {
                            // If set 3, maybe add an extra overload
                            $setWeight = ($setNum === 3 && $week >= 3) ? $actualW + 2.5 : $actualW;
                            $reps = rand(8, 12);
                            $rir = ($week == 4 && $setNum == 3) ? '0' : ($setNum == 1 ? '3+' : '1-2');

                            $logParams[] = $userId;
                            $logParams[] = $sessionId;
                            $logParams[] = (int)$ex['id'];
                            $logParams[] = $setNum;
                            $logParams[] = $setWeight;
                            $logParams[] = $setWeight;
                            $logParams[] = $reps;
                            $logParams[] = $rir;
                            $logParams[] = date('Y-m-d H:i:s', strtotime($sessionTimestamp) + ($setNum * 180));

                            $logPlaceholders[] = "(?, ?, ?, ?, 'reps', ?, ?, 10, ?, ?, 0, ?)";
                        }
                    }
                }
            }

            if (!empty($logPlaceholders)) {
                // Determine maximum placeholders allowed by the database (SQLite typically limits to 999 or 32766)
                // We'll chunk the inserts to be safe. We have 9 parameters per row.
                // Let's use chunks of 50 rows (50 * 9 = 450 parameters).
                $chunkSize = 50;
                $totalRows = count($logPlaceholders);

                for ($i = 0; $i < $totalRows; $i += $chunkSize) {
                    $chunkPlaceholders = array_slice($logPlaceholders, $i, $chunkSize);
                    $chunkParams = array_slice($logParams, $i * 9, count($chunkPlaceholders) * 9);

                    $sql = "INSERT INTO workout_logs (
                                user_id, session_id, exercise_id, set_number, set_type,
                                target_weight, actual_weight, target_reps, actual_reps,
                                rir_bucket, form_breakdown, created_at
                            ) VALUES " . implode(', ', $chunkPlaceholders);

                    $this->db->prepare($sql)->execute($chunkParams);
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
