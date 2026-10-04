<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Models\User;
use App\Models\PasswordReset;
use App\Models\Equipment;
use App\Config\Constants;

class AuthController extends Controller
{
    private User $userModel;
    private PasswordReset $passwordResetModel;
    private Equipment $equipmentModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->passwordResetModel = new PasswordReset();
        $this->equipmentModel = new Equipment();
    }

    public function showLogin(): void
    {
        $this->requireGuest();
        $this->render('auth/login', [
            'pageTitle' => 'Giriş Yap — Akıllı Hipertrofi'
        ]);
    }

    public function login(): void
    {
        $this->requireGuest();
        Security::verifyRequestCsrf();

        $ip = Security::getClientIp();
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        // 1. IP & Brute-Force Check (5 failed attempts in 15 mins locks IP)
        if (!Security::checkLoginRateLimit($ip, $email)) {
            Security::recordLoginAttempt($ip, $email, false);
            $this->setFlash('danger', 'Çok fazla başarısız deneme yapıldı. Güvenliğiniz için IP adresiniz 15 dakika boyunca kilitlenmiştir.');
            $this->redirect('/login');
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user || !$this->userModel->verifyPassword($password, $user['password_hash'])) {
            Security::recordLoginAttempt($ip, $email, false);
            $this->setFlash('danger', 'E-posta adresi veya parola hatalı.');
            $this->redirect('/login');
        }

        // Record successful login
        Security::recordLoginAttempt($ip, $email, true);
        Security::regenerateSession();

        // Remove hash from session
        unset($user['password_hash']);
        $_SESSION['user'] = $user;

        // Check if onboarding is completed
        $equipment = $this->equipmentModel->getByUserId((int)$user['id']);
        if (!$equipment) {
            $this->redirect('/onboarding/assessment');
        }

        $this->setFlash('success', "Hoş geldiniz, {$user['name']}! Antrenman paneliniz hazır.");
        $this->redirect('/workout/dashboard');
    }

    public function showRegister(): void
    {
        $this->render('auth/register', [
            'pageTitle' => 'Kayıt Ol — Akıllı Hipertrofi',
            'currentUser' => $this->getCurrentUser()
        ]);
    }

    public function register(): void
    {
        Security::verifyRequestCsrf();

        $name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $gender = (string)($_POST['gender'] ?? 'male');
        $birthDate = (string)($_POST['birth_date'] ?? '');
        $heightCm = (float)($_POST['height_cm'] ?? 175.0);
        $currentWeightKg = (float)($_POST['current_weight_kg'] ?? 75.0);

        if (empty($name) || empty($email) || strlen($password) < 8 || empty($birthDate)) {
            $_SESSION['old_input'] = $_POST;
            $this->setFlash('danger', 'Lütfen tüm alanları eksiksiz doldurun. Parola en az 8 karakter olmalıdır.');
            $this->redirect('/register');
        }

        if (!in_array($gender, ['male', 'female'], true)) {
            $_SESSION['old_input'] = $_POST;
            $this->setFlash('danger', 'Lütfen geçerli bir cinsiyet seçin.');
            $this->redirect('/register');
        }

        $bDateObj = \DateTime::createFromFormat('Y-m-d', $birthDate);
        if (!$bDateObj || $bDateObj->format('Y-m-d') !== $birthDate) {
            $_SESSION['old_input'] = $_POST;
            $this->setFlash('danger', 'Lütfen geçerli bir doğum tarihi (Yıl-Ay-Gün) girin.');
            $this->redirect('/register');
        }
        $age = (int)(new \DateTime())->diff($bDateObj)->y;
        if ($age < 13 || $age > 100) {
            $_SESSION['old_input'] = $_POST;
            $this->setFlash('danger', 'Platformumuzu kullanabilmek için yaşınız 13 ile 100 arasında olmalıdır.');
            $this->redirect('/register');
        }

        if ($heightCm < 100.0 || $heightCm > 250.0) {
            $_SESSION['old_input'] = $_POST;
            $this->setFlash('danger', 'Boy 100 cm ile 250 cm arasında olmalıdır.');
            $this->redirect('/register');
        }

        if ($currentWeightKg < 30.0 || $currentWeightKg > 300.0) {
            $_SESSION['old_input'] = $_POST;
            $this->setFlash('danger', 'Kilo 30 kg ile 300 kg arasında olmalıdır.');
            $this->redirect('/register');
        }

        if ($this->userModel->findByEmail($email)) {
            $_SESSION['old_input'] = $_POST;
            $this->setFlash('danger', 'Bu e-posta adresiyle (' . Security::escape($email) . ') zaten kayıtlı bir hesap bulunmaktadır. Lütfen giriş yapın veya farklı bir e-posta kullanın.');
            $this->redirect('/register');
        }

        try {
            $userId = $this->userModel->register(
                $name,
                $email,
                $password,
                $gender,
                $birthDate,
                $heightCm,
                $currentWeightKg,
                'beginner'
            );

            // Log initial weight so weigh-in history and moving average start immediately
            $weighInModel = new \App\Models\DailyWeighIn();
            $weighInModel->logWeight($userId, $currentWeightKg, date('Y-m-d'), 'Kayıt Anı Başlangıç Kilosu');

            $user = $this->userModel->findById($userId);
            unset($user['password_hash']);
            Security::regenerateSession();
            $_SESSION['user'] = $user;
            unset($_SESSION['old_input']);

            $this->setFlash('info', "🎯 Tebrikler {$user['name']}! Hesabınız açıldı. Şimdi antrenman programınızı ve ağırlıklarınızı hassas hesaplamak için 4 parametreli kalistenik testinizi tamamlayın.");
            $this->redirect('/onboarding/assessment');
        } catch (\Throwable $e) {
            error_log("Registration error: " . $e->getMessage());
            $_SESSION['old_input'] = $_POST;
            $this->setFlash('danger', 'Kayıt sırasında bir sistem hatası oluştu. Lütfen bilgilerinizi kontrol edip tekrar deneyin.');
            $this->redirect('/register');
        }
    }

    public function logout(): void
    {
        Security::startSession();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        header("Location: /login");
        exit;
    }

    public function showForgotPassword(): void
    {
        $this->render('auth/forgot-password', [
            'pageTitle' => 'Şifremi Unuttum — Akıllı Hipertrofi'
        ]);
    }

    public function forgotPassword(): void
    {
        Security::verifyRequestCsrf();
        $email = strtolower(trim($_POST['email'] ?? ''));
        $user = $this->userModel->findByEmail($email);

        if ($user) {
            $token = $this->passwordResetModel->createResetToken($email);

            // Base URL comes from config, NOT from the Host header (prevents host header poisoning).
            $baseUrl = rtrim((string)(getenv('APP_URL') ?: ''), '/');
            if ($baseUrl === '') {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            }
            $resetLink = $baseUrl . '/reset-password?email=' . urlencode($email) . '&token=' . $token;

            if (Security::isLocalEnv()) {
                // Local development only: show the link on screen, never in production.
                error_log("Password reset link generated for {$email}: {$resetLink}");
                $_SESSION['dev_reset_link'] = $resetLink;
            } else {
                // Production: the link is delivered ONLY by e-mail, never to the requester's browser.
                $from = (string)(getenv('MAIL_FROM') ?: 'no-reply@' . parse_url($baseUrl, PHP_URL_HOST));
                $subject = '=?UTF-8?B?' . base64_encode('Parola sıfırlama bağlantınız') . '?=';
                $body = "Merhaba,\r\n\r\nParolanızı sıfırlamak için aşağıdaki bağlantıyı kullanın "
                      . "(" . Constants::PASSWORD_RESET_EXPIRY_HOURS . " saat geçerlidir):\r\n\r\n{$resetLink}\r\n\r\n"
                      . "Bu talebi siz yapmadıysanız bu e-postayı yok sayabilirsiniz.\r\n";
                $headers = "From: {$from}\r\nContent-Type: text/plain; charset=UTF-8\r\n";
                if (!@mail($email, $subject, $body, $headers)) {
                    error_log("Password reset mail failed for user id " . (int)$user['id']);
                }
            }
        }

        $this->setFlash('info', 'Eğer kayıtlı bir e-posta adresi girdiyseniz, şifre sıfırlama bağlantısı oluşturuldu.');
        $this->redirect('/forgot-password');
    }

    public function showResetPassword(): void
    {
        $email = trim($_GET['email'] ?? '');
        $token = trim($_GET['token'] ?? '');

        if (empty($email) || empty($token) || !$this->passwordResetModel->verifyToken($email, $token)) {
            $this->setFlash('danger', 'Geçersiz veya süresi dolmuş şifre sıfırlama bağlantısı.');
            $this->redirect('/forgot-password');
        }

        $this->render('auth/reset-password', [
            'pageTitle' => 'Yeni Parola Belirle — Akıllı Hipertrofi',
            'email'     => $email,
            'token'     => $token
        ]);
    }

    public function resetPassword(): void
    {
        Security::verifyRequestCsrf();
        $email = trim($_POST['email'] ?? '');
        $token = trim($_POST['token'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if (strlen($password) < 8) {
            $this->setFlash('danger', 'Yeni parola en az 8 karakter olmalıdır.');
            $this->redirect("/reset-password?email=" . urlencode($email) . "&token=" . urlencode($token));
        }

        if (!$this->passwordResetModel->verifyToken($email, $token)) {
            $this->setFlash('danger', 'Geçersiz veya süresi dolmuş sıfırlama bağlantısı.');
            $this->redirect('/forgot-password');
        }

        $user = $this->userModel->findByEmail($email);
        if ($user) {
            $this->userModel->updatePassword((int)$user['id'], $password);
            $this->passwordResetModel->consumeToken($email);
            $this->setFlash('success', 'Parolanız başarıyla güncellendi! Yeni parolanızla giriş yapabilirsiniz.');
            $this->redirect('/login');
        }

        $this->redirect('/login');
    }
}
