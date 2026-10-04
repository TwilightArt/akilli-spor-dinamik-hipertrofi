<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class WorkoutLog extends Model
{
    protected string $table = 'workout_logs';

    public function logSet(array $data): int
    {
        return $this->insert($data);
    }

    public function getLogsForSession(int $sessionId): array
    {
        $stmt = $this->db->prepare("
            SELECT wl.*, e.name as exercise_name, e.movement_pattern, e.required_equipment 
            FROM workout_logs wl
            JOIN exercises e ON wl.exercise_id = e.id
            WHERE wl.session_id = :sid
            ORDER BY wl.id ASC
        ");
        $stmt->execute(['sid' => $sessionId]);
        return $stmt->fetchAll();
    }

    public function getExerciseLogsForSession(int $sessionId, int $exerciseId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM workout_logs 
            WHERE session_id = :sid AND exercise_id = :eid 
            ORDER BY set_number ASC
        ");
        $stmt->execute(['sid' => $sessionId, 'eid' => $exerciseId]);
        return $stmt->fetchAll();
    }

    public function getFirstSetWeight(int $sessionId, int $exerciseId): ?float
    {
        $stmt = $this->db->prepare("
            SELECT actual_weight 
            FROM workout_logs 
            WHERE session_id = :sid 
              AND exercise_id = :eid 
              AND set_type != 'warmup'
            ORDER BY set_number ASC 
            LIMIT 1
        ");
        $stmt->execute(['sid' => $sessionId, 'eid' => $exerciseId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (float)$val : null;
    }

    /**
     * How many times weight was increased in this session for this exercise
     */
    public function getSessionIncreasesCount(int $sessionId, int $exerciseId): int
    {
        $logs = $this->getExerciseLogsForSession($sessionId, $exerciseId);
        if (count($logs) <= 1) {
            return 0;
        }

        $increases = 0;
        for ($i = 1; $i < count($logs); $i++) {
            if ((float)$logs[$i]['actual_weight'] > (float)$logs[$i - 1]['actual_weight']) {
                $increases++;
            }
        }
        return $increases;
    }

    /**
     * Count recent consecutive RIR 0 entries for this exercise in session
     */
    public function getConsecutiveRir0Count(int $sessionId, int $exerciseId): int
    {
        $stmt = $this->db->prepare("
            SELECT rir_bucket 
            FROM workout_logs 
            WHERE session_id = :sid AND exercise_id = :eid 
            ORDER BY set_number DESC
        ");
        $stmt->execute(['sid' => $sessionId, 'eid' => $exerciseId]);
        $buckets = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $count = 0;
        foreach ($buckets as $b) {
            if ($b === '0') {
                $count++;
            } else {
                break;
            }
        }
        return $count;
    }
}
