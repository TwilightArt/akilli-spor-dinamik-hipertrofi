<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class DailyWeighIn extends Model
{
    protected string $table = 'daily_weigh_ins';

    public function logWeight(int $userId, float $weightKg, string $date, ?string $notes = null): bool
    {
        $existing = $this->findOneWhere(['user_id' => $userId, 'logged_date' => $date]);
        if ($existing) {
            return $this->update((int)$existing['id'], [
                'weight_kg' => $weightKg,
                'notes'     => $notes
            ]);
        }

        return (bool)$this->insert([
            'user_id'     => $userId,
            'weight_kg'   => $weightKg,
            'logged_date' => $date,
            'notes'       => $notes
        ]);
    }

    public function getRecentHistory(int $userId, int $days = 30): array
    {
        $cutoff = date('Y-m-d', strtotime("-{$days} days"));
        $stmt = $this->db->prepare("
            SELECT * FROM daily_weigh_ins
            WHERE user_id = :uid 
              AND logged_date >= :cutoff
            ORDER BY logged_date ASC
        ");
        $stmt->execute(['uid' => $userId, 'cutoff' => $cutoff]);
        return $stmt->fetchAll();
    }

    /**
     * Compute 7-day Moving Average of bodyweight
     */
    public function getMovingAverage(int $userId, int $days = 7, ?string $endDate = null): ?float
    {
        $end = $endDate ?: date('Y-m-d');
        $daysDiff = $days - 1;
        $start = date('Y-m-d', strtotime("-{$daysDiff} days", strtotime($end)));

        $stmt = $this->db->prepare("
            SELECT AVG(weight_kg) as moving_avg, COUNT(*) as cnt
            FROM daily_weigh_ins
            WHERE user_id = :uid 
              AND logged_date BETWEEN :start_date AND :end_date
        ");
        $stmt->execute([
            'uid'        => $userId,
            'start_date' => $start,
            'end_date'   => $end
        ]);
        $row = $stmt->fetch();
        if ($row && $row['cnt'] > 0 && $row['moving_avg'] !== null) {
            return round((float)$row['moving_avg'], 2);
        }
        return null;
    }

    /**
     * Compute weekly rate of change between current 7-day average and previous 7-day average
     * Returns signed percentage (e.g. -0.015 for -1.5% loss, +0.005 for +0.5% gain)
     */
    public function getWeeklyRateOfChange(int $userId): ?float
    {
        $current7Avg = $this->getMovingAverage($userId, 7, date('Y-m-d'));
        $prevEndDate = date('Y-m-d', strtotime('-7 days'));
        $prev7Avg = $this->getMovingAverage($userId, 7, $prevEndDate);

        if ($current7Avg === null || $prev7Avg === null || $prev7Avg <= 0.0) {
            return null;
        }

        return ($current7Avg - $prev7Avg) / $prev7Avg;
    }
}
