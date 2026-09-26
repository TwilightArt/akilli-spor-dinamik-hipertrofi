<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Exercise extends Model
{
    protected string $table = 'exercises';

    public function getAllWithJointStress(): array
    {
        $sql = "
            SELECT e.*, 
                   GROUP_CONCAT(ejs.joint_area) AS joint_stresses
            FROM exercises e
            LEFT JOIN exercise_joint_stress ejs ON e.id = ejs.exercise_id
            GROUP BY e.id
            ORDER BY e.movement_pattern, e.difficulty_level
        ";
        return $this->db->query($sql)->fetchAll();
    }

    public function getValidIds(): array
    {
        $stmt = $this->db->query("SELECT id FROM exercises");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getByIdWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT e.*, 
                   GROUP_CONCAT(ejs.joint_area) AS joint_stresses
            FROM exercises e
            LEFT JOIN exercise_joint_stress ejs ON e.id = ejs.exercise_id
            WHERE e.id = :id
            GROUP BY e.id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getExercisesForAI(string $location, bool $hasBarbell, bool $hasDumbbells): array
    {
        $allowedEquipment = ['bodyweight'];
        if ($location === 'commercial_gym') {
            $allowedEquipment = ['bodyweight', 'barbell', 'dumbbell', 'machine', 'cable'];
        } else {
            if ($hasBarbell) {
                $allowedEquipment[] = 'barbell';
            }
            if ($hasDumbbells) {
                $allowedEquipment[] = 'dumbbell';
            }
        }

        $placeholders = implode(',', array_fill(0, count($allowedEquipment), '?'));
        $stmt = $this->db->prepare("
            SELECT id, name, movement_pattern, primary_muscle_group, required_equipment, difficulty_level 
            FROM exercises 
            WHERE required_equipment IN ({$placeholders})
            ORDER BY movement_pattern, difficulty_level
        ");
        $stmt->execute($allowedEquipment);
        return $stmt->fetchAll();
    }

    public function getRegression(int $exerciseId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT r.* 
            FROM exercises e
            JOIN exercises r ON e.is_regression_of = r.id
            WHERE e.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $exerciseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
