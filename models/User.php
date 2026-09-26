<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => strtolower(trim($email))]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function register(
        string $name,
        string $email,
        string $plainPassword,
        string $gender,
        string $birthDate,
        float $heightCm,
        float $currentWeightKg = 75.0,
        string $fitnessLevel = 'beginner'
    ): int {
        // Enforce Argon2id password hashing as specified in architecture
        $hash = password_hash($plainPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost'   => 4,
            'threads'     => 1
        ]);

        return $this->insert([
            'name'              => trim($name),
            'email'             => strtolower(trim($email)),
            'password_hash'     => $hash,
            'gender'            => $gender,
            'birth_date'        => $birthDate,
            'height_cm'         => $heightCm,
            'current_weight_kg' => $currentWeightKg,
            'fitness_level'     => $fitnessLevel
        ]);
    }

    public function verifyPassword(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }

    public function updatePassword(int $userId, string $plainPassword): bool
    {
        $hash = password_hash($plainPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost'   => 4,
            'threads'     => 1
        ]);
        return $this->update($userId, ['password_hash' => $hash]);
    }

    public function updateFitnessLevel(int $userId, string $level): bool
    {
        return $this->update($userId, ['fitness_level' => $level]);
    }
}
