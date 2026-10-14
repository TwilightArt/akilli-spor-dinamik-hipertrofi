<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class BodyMeasurement extends Model
{
    protected string $table = 'body_measurements';

    /**
     * Log a new body measurement entry
     */
    public function logMeasurement(int $userId, array $data): int
    {
        $payload = [
            'user_id'       => $userId,
            'measured_date' => $data['measured_date'] ?? date('Y-m-d'),
            'neck_cm'       => isset($data['neck_cm']) && $data['neck_cm'] !== '' ? (float)$data['neck_cm'] : null,
            'shoulder_cm'   => isset($data['shoulder_cm']) && $data['shoulder_cm'] !== '' ? (float)$data['shoulder_cm'] : null,
            'chest_cm'      => isset($data['chest_cm']) && $data['chest_cm'] !== '' ? (float)$data['chest_cm'] : null,
            'arm_cm'        => isset($data['arm_cm']) && $data['arm_cm'] !== '' ? (float)$data['arm_cm'] : null,
            'waist_cm'      => isset($data['waist_cm']) && $data['waist_cm'] !== '' ? (float)$data['waist_cm'] : null,
            'hip_cm'        => isset($data['hip_cm']) && $data['hip_cm'] !== '' ? (float)$data['hip_cm'] : null,
            'thigh_cm'      => isset($data['thigh_cm']) && $data['thigh_cm'] !== '' ? (float)$data['thigh_cm'] : null,
            'calf_cm'       => isset($data['calf_cm']) && $data['calf_cm'] !== '' ? (float)$data['calf_cm'] : null,
            'body_fat_pct'  => isset($data['body_fat_pct']) && $data['body_fat_pct'] !== '' ? (float)$data['body_fat_pct'] : null,
        ];

        return $this->insert($payload);
    }

    /**
     * Get recent measurement records for user
     */
    public function getRecentHistory(int $userId, int $limit = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM body_measurements
            WHERE user_id = :uid
            ORDER BY measured_date DESC, id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get latest measurement entry
     */
    public function getLatest(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM body_measurements
            WHERE user_id = :uid
            ORDER BY measured_date DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get first (baseline) measurement entry
     */
    public function getFirst(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM body_measurements
            WHERE user_id = :uid
            ORDER BY measured_date ASC, id ASC
            LIMIT 1
        ");
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Compute difference (delta) between first baseline and latest measurement
     */
    public function getDeltas(int $userId): array
    {
        $first = $this->getFirst($userId);
        $latest = $this->getLatest($userId);

        if (!$first || !$latest || $first['id'] === $latest['id']) {
            return [];
        }

        $fields = ['neck_cm', 'shoulder_cm', 'chest_cm', 'arm_cm', 'waist_cm', 'hip_cm', 'thigh_cm', 'calf_cm', 'body_fat_pct'];
        $deltas = [];

        foreach ($fields as $f) {
            if ($first[$f] !== null && $latest[$f] !== null) {
                $diff = round((float)$latest[$f] - (float)$first[$f], 2);
                $deltas[$f] = [
                    'baseline' => (float)$first[$f],
                    'current'  => (float)$latest[$f],
                    'diff'     => $diff,
                    'direction'=> $diff > 0 ? 'increase' : ($diff < 0 ? 'decrease' : 'neutral')
                ];
            }
        }

        return $deltas;
    }

    /**
     * Delete a measurement record
     */
    public function deleteMeasurement(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM body_measurements WHERE id = :id AND user_id = :uid");
        return $stmt->execute(['id' => $id, 'uid' => $userId]);
    }
}
