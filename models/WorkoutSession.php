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

    /**
     * Get user streak stats (this week completed sessions, consecutive active weeks, total completed)
     */
    public function getUserStreakStats(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT ended_at 
            FROM workout_sessions 
            WHERE user_id = :uid AND ended_at IS NOT NULL 
            ORDER BY ended_at DESC
        ");
        $stmt->execute(['uid' => $userId]);
        $dates = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];

        $totalCompleted = count($dates);

        // This week count (Monday this week to Sunday)
        $mondayThisWeek = strtotime('monday this week 00:00:00');
        $thisWeekCount = 0;
        foreach ($dates as $d) {
            if (strtotime((string)$d) >= $mondayThisWeek) {
                $thisWeekCount++;
            }
        }

        // Consecutive weeks calculation
        // Group dates into Year-Week format ('o-W' in PHP handles ISO-8601 week numbers correctly)
        $activeWeeks = [];
        foreach ($dates as $d) {
            $weekKey = date('o-W', strtotime((string)$d));
            $activeWeeks[$weekKey] = true;
        }

        $streakWeeks = 0;
        $currentWeekTime = strtotime('this week monday 00:00:00');
        $currentWeekKey = date('o-W', $currentWeekTime);

        // If user worked out this week, start from this week; otherwise check last week
        if (!empty($activeWeeks[$currentWeekKey])) {
            $streakWeeks = 1;
            $checkTime = strtotime('-1 week', $currentWeekTime);
        } else {
            // Check if last week had workouts
            $lastWeekTime = strtotime('-1 week', $currentWeekTime);
            $lastWeekKey = date('o-W', $lastWeekTime);
            if (!empty($activeWeeks[$lastWeekKey])) {
                $streakWeeks = 1;
                $checkTime = strtotime('-1 week', $lastWeekTime);
            } else {
                $checkTime = null;
            }
        }

        // Trace backwards week by week
        while ($checkTime !== null) {
            $key = date('o-W', $checkTime);
            if (!empty($activeWeeks[$key])) {
                $streakWeeks++;
                $checkTime = strtotime('-1 week', $checkTime);
            } else {
                break;
            }
        }

        return [
            'this_week_count' => $thisWeekCount,
            'streak_weeks'    => $streakWeeks,
            'total_completed' => $totalCompleted
        ];
    }
}
