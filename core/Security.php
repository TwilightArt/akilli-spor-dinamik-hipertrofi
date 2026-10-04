<?php
declare(strict_types=1);

namespace App\Core;

use App\Config\Database;
use App\Config\Constants;

/**
 * Security & Hardening Core Helper
 * CSRF Protection, IP Rate Limiting, Session Guard, XSS Filter, IDOR Protection
 */
class Security
{
    /**
     * Start secure session with production cookies
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isSecure,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

            session_start();
        }
    }

    /**
     * Regenerate session ID safely
     */
    public static function regenerateSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Generate or return existing CSRF token
     */
    public static function generateCsrfToken(): string
    {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token against session
     */
    public static function validateCsrfToken(?string $token): bool
    {
        self::startSession();
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Verify CSRF from POST, HTTP Header (X-CSRF-Token) or JSON Body
     */
    public static function verifyRequestCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        
        if (!$token && function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $k => $v) {
                if (strcasecmp($k, 'X-CSRF-Token') === 0) {
                    $token = (string)$v;
                    break;
                }
            }
        }

        if (!$token) {
            $raw = file_get_contents('php://input');
            if (!empty($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && !empty($decoded['csrf_token'])) {
                    $token = (string)$decoded['csrf_token'];
                }
            }
        }

        if (!self::validateCsrfToken($token)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Geçersiz veya süresi dolmuş CSRF güvenlik doğrulaması. Lütfen sayfayı yenileyin.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Get Client IP address safely handling proxies
     * Do NOT trust X-Forwarded-For or CF-Connecting-IP unless TRUST_PROXIES=true is explicitly set in .env
     */
    public static function getClientIp(): string
    {
        $trustProxy = strtolower((string)(getenv('TRUST_PROXIES') ?: '')) === 'true';
        if ($trustProxy) {
            if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                return $_SERVER['HTTP_CF_CONNECTING_IP'];
            }
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                return trim($ips[0]);
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Brute-Force Check: Checks failed logins for IP in the last windowMinutes
     */
    public static function checkLoginRateLimit(string $ip, ?string $email = null): bool
    {
        $db = Database::getConnection();
        $minutes = Constants::LOGIN_LOCKOUT_MINUTES;
        $maxAttempts = Constants::MAX_LOGIN_ATTEMPTS;
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        $stmt = $db->prepare("
            SELECT COUNT(*) AS fail_count 
            FROM login_attempts 
            WHERE ip_address = :ip 
              AND success = 0 
              AND attempted_at >= :cutoff
        ");
        $stmt->execute([
            'ip'     => $ip,
            'cutoff' => $cutoff
        ]);
        $row = $stmt->fetch();
        $count = (int)($row['fail_count'] ?? 0);

        return $count < $maxAttempts;
    }

    /**
     * Record login attempt
     */
    public static function recordLoginAttempt(string $ip, ?string $email, bool $success): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO login_attempts (ip_address, email, success) 
            VALUES (:ip, :email, :success)
        ");
        $stmt->execute([
            'ip'      => $ip,
            'email'   => $email ? substr($email, 0, 150) : null,
            'success' => $success ? 1 : 0
        ]);
    }

    /**
     * Rate limit for /api/log_set.php: Max 3 set logs per minute per user
     */
    public static function checkSetLogRateLimit(int $userId): bool
    {
        $db = Database::getConnection();
        $cutoff = date('Y-m-d H:i:s', strtotime('-1 minute'));
        $stmt = $db->prepare("
            SELECT COUNT(*) AS recent_count 
            FROM workout_logs 
            WHERE user_id = :user_id 
              AND created_at >= :cutoff
        ");
        $stmt->execute([
            'user_id' => $userId,
            'cutoff'  => $cutoff
        ]);
        $row = $stmt->fetch();
        $count = (int)($row['recent_count'] ?? 0);

        return $count < Constants::MAX_SET_LOGS_PER_MINUTE;
    }

    /**
     * IDOR Guard: Verify ownership of a record
     */
    public static function enforceOwnership(string $table, int $recordId, int $userId): void
    {
        $db = Database::getConnection();
        // Whitelist allowed tables to prevent SQL injection in table name
        $allowedTables = [
            'workout_sessions',
            'user_programs',
            'user_equipment',
            'daily_weigh_ins',
            'workout_logs',
            'injury_reports',
            'user_calibration_log'
        ];

        if (!in_array($table, $allowedTables, true)) {
            throw new \InvalidArgumentException("Geçersiz tablo doğrulaması.");
        }

        $stmt = $db->prepare("SELECT id FROM `{$table}` WHERE id = :id AND user_id = :uid LIMIT 1");
        $stmt->execute(['id' => $recordId, 'uid' => $userId]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Erişim yetkiniz bulunmamaktadır (IDOR Koruması).'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Sanitize output against XSS
     */
    public static function e(?string $string): string
    {
        return htmlspecialchars((string)$string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Alias of e(). Views and controllers call Security::escape().
     */
    public static function escape(?string $string): string
    {
        return self::e($string);
    }

    /**
     * True only when APP_ENV=local is explicitly set (default is production-safe: false).
     */
    public static function isLocalEnv(): bool
    {
        return strtolower((string)(getenv('APP_ENV') ?: '')) === 'local';
    }
}