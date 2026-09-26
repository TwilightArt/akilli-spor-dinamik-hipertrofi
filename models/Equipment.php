<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Equipment extends Model
{
    protected string $table = 'user_equipment';

    public function getByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM user_equipment WHERE user_id = :uid LIMIT 1");
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch();
        if ($row && !empty($row['available_plates']) && is_string($row['available_plates'])) {
            $row['plates_array'] = json_decode($row['available_plates'], true) ?: [];
        }
        return $row ?: null;
    }

    public function saveOrUpdate(
        int $userId,
        bool $hasBarbell,
        bool $hasDumbbells,
        float $barbellWeightKg,
        float $dumbbellHandleWeightKg,
        array $availablePlates,
        string $workoutLocation
    ): int {
        $existing = $this->getByUserId($userId);
        $platesJson = json_encode($availablePlates, JSON_UNESCAPED_UNICODE);

        $payload = [
            'has_barbell'                => $hasBarbell ? 1 : 0,
            'has_dumbbells'              => $hasDumbbells ? 1 : 0,
            'barbell_weight_kg'          => $barbellWeightKg,
            'dumbbell_handle_weight_kg'  => $dumbbellHandleWeightKg,
            'available_plates'           => $platesJson,
            'workout_location'           => $workoutLocation
        ];

        if ($existing) {
            $this->update((int)$existing['id'], $payload);
            return (int)$existing['id'];
        }

        $payload['user_id'] = $userId;
        return $this->insert($payload);
    }
}
