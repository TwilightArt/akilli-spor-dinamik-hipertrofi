<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Config\Constants;

class PasswordReset extends Model
{
    protected string $table = 'password_resets';

    public function createResetToken(string $email): string
    {
        // Delete older requests for this email
        $stmt = $this->db->prepare("DELETE FROM password_resets WHERE email = :email");
        $stmt->execute(['email' => $email]);

        // Cryptographically secure 32 bytes token
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        $hours = Constants::PASSWORD_RESET_EXPIRY_HOURS;
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));

        $this->insert([
            'email'      => $email,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt
        ]);

        return $rawToken;
    }

    public function verifyToken(string $email, string $rawToken): bool
    {
        $tokenHash = hash('sha256', $rawToken);
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("
            SELECT id FROM password_resets 
            WHERE email = :email 
              AND token_hash = :hash 
              AND expires_at > :now
            LIMIT 1
        ");
        $stmt->execute([
            'email' => $email,
            'hash'  => $tokenHash,
            'now'   => $now
        ]);
        return (bool)$stmt->fetch();
    }

    public function consumeToken(string $email): void
    {
        $stmt = $this->db->prepare("DELETE FROM password_resets WHERE email = :email");
        $stmt->execute(['email' => $email]);
    }
}
