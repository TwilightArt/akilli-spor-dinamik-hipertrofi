<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class WorkoutSession extends Model
{
    protected string $table = 'workout_sessions';

    public function getActiveSession(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, pd.focus_area, pd.day_of_week
            FROM workout_sessions s
            LEFT JOIN program_days pd ON s.program_day_id = pd.id
            WHERE s.user_id = :uid AND s.ended_at IS NULL
            ORDER BY s.id DESC
            LIMIT 1
        ");
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function startSession(int $userId, ?int $programDayId): int
    {
        // If there is already an active session, return its id
        $active = $this->getActiveSession($userId);
        if ($active) {
            return (int)$active['id'];
        }

        return $this->insert([
            'user_id'        => $userId,
            'program_day_id' => $programDayId,
            'started_at'     => date('Y-m-d H:i:s')
        ]);
    }

    public function endSession(int $sessionId, int $userId): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("
            UPDATE workout_sessions 
            SET ended_at = :ended_at 
            WHERE id = :id AND user_id = :uid AND ended_at IS NULL
        ");
        return $stmt->execute([
            'id'       => $sessionId,
            'uid'      => $userId,
            'ended_at' => $now
        ]);
    }

    public function getSessionSummary(int $sessionId, int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, pd.focus_area,
                   COUNT(wl.id) as total_sets_completed,
                   SUM(wl.actual_weight * COALESCE(wl.actual_reps, 1)) as total_volume_kg
            FROM workout_sessions s
            LEFT JOIN program_days pd ON s.program_day_id = pd.id
            LEFT JOIN workout_logs wl ON s.id = wl.session_id
            WHERE s.id = :id AND s.user_id = :uid
            GROUP BY s.id
            LIMIT 1
        ");
        $stmt->execute(['id' => $sessionId, 'uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
