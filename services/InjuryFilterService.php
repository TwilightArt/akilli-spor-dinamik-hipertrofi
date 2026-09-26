<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;

/**
 * Multi-Joint Injury Filter Service
 * Excludes exercises that load sensitive joints and swaps with biomechanically safe alternatives
 */
class InjuryFilterService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Get user active injured joint areas
     *
     * @param int $userId
     * @return array List of joint names e.g. ['shoulder', 'lower_back']
     */
    public function getActiveInjuredJoints(int $userId): array
    {
        $cutoff = date('Y-m-d', strtotime('-30 days'));
        $stmt = $this->db->prepare("
            SELECT DISTINCT joint_area 
            FROM injury_reports 
            WHERE user_id = :uid 
              AND reported_date >= :cutoff
        ");
        $stmt->execute(['uid' => $userId, 'cutoff' => $cutoff]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Check if a specific exercise causes stress to any of the user's injured joints
     */
    public function isExerciseHarmful(int $exerciseId, array $injuredJoints): bool
    {
        if (empty($injuredJoints)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($injuredJoints), '?'));
        $sql = "
            SELECT COUNT(*) 
            FROM exercise_joint_stress 
            WHERE exercise_id = ? 
              AND joint_area IN ({$placeholders})
        ";
        $params = array_merge([$exerciseId], $injuredJoints);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return ((int)$stmt->fetchColumn()) > 0;
    }

    /**
     * Find a safe alternative exercise for the same movement pattern
     */
    public function findSafeAlternative(int $problematicExerciseId, array $injuredJoints, array $availableEquipment): ?array
    {
        // Get problem exercise details
        $stmt = $this->db->prepare("SELECT * FROM exercises WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $problematicExerciseId]);
        $orig = $stmt->fetch();
        if (!$orig) {
            return null;
        }

        // Search for alternative with same movement pattern that has NO stress on injured joints
        $placeholdersEquip = implode(',', array_fill(0, count($availableEquipment), '?'));
        $placeholdersJoints = !empty($injuredJoints) ? implode(',', array_fill(0, count($injuredJoints), '?')) : "''";

        $sql = "
            SELECT e.* 
            FROM exercises e
            WHERE e.id != ?
              AND e.movement_pattern = ?
              AND e.required_equipment IN ({$placeholdersEquip})
              AND e.id NOT IN (
                  SELECT exercise_id 
                  FROM exercise_joint_stress 
                  WHERE joint_area IN ({$placeholdersJoints})
              )
            ORDER BY e.difficulty_level ASC, e.id ASC
            LIMIT 1
        ";

        $params = array_merge([$problematicExerciseId, $orig['movement_pattern']], $availableEquipment);
        if (!empty($injuredJoints)) {
            $params = array_merge($params, $injuredJoints);
        }

        $stmtAlt = $this->db->prepare($sql);
        $stmtAlt->execute($params);
        $alt = $stmtAlt->fetch();

        return $alt ?: null;
    }

    /**
     * Filter and sanitize a list of workout exercises for injured joints
     */
    public function filterProgramExercises(int $userId, array $exerciseList, array $availableEquipment): array
    {
        $injuredJoints = $this->getActiveInjuredJoints($userId);
        if (empty($injuredJoints)) {
            return $exerciseList;
        }

        $sanitized = [];
        foreach ($exerciseList as $item) {
            $exerciseId = (int)$item['exercise_id'];
            if ($this->isExerciseHarmful($exerciseId, $injuredJoints)) {
                $alt = $this->findSafeAlternative($exerciseId, $injuredJoints, $availableEquipment);
                if ($alt) {
                    $item['exercise_id'] = (int)$alt['id'];
                    $item['exercise_name'] = $alt['name'];
                    $item['note'] = "Sakatlık koruması gereği güvenli alternatif atandı.";
                }
            }
            $sanitized[] = $item;
        }

        return $sanitized;
    }
}
