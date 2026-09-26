<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Base Controller
 */
abstract class Controller
{
    public function __construct()
    {
        Security::startSession();
    }

    /**
     * Render View inside layouts
     */
    protected function render(string $viewPath, array $data = [], bool $withLayout = true): void
    {
        // Extract variables for view access
        extract($data);

        // Standard layout helpers
        $currentUser = $this->getCurrentUser();
        $csrfToken = Security::generateCsrfToken();
        $flash = $this->getFlash();

        $viewFile = dirname(__DIR__) . "/views/{$viewPath}.php";
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("Görünüm dosyası bulunamadı: views/{$viewPath}.php");
        }

        if ($withLayout) {
            $headerFile = dirname(__DIR__) . "/views/layouts/header.php";
            $footerFile = dirname(__DIR__) . "/views/layouts/footer.php";

            if (file_exists($headerFile)) {
                require $headerFile;
            }

            require $viewFile;

            if (file_exists($footerFile)) {
                require $footerFile;
            }
        } else {
            require $viewFile;
        }
    }

    /**
     * JSON Response for API endpoints
     */
    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Redirect to path
     */
    protected function redirect(string $path): void
    {
        header("Location: {$path}");
        exit;
    }

    /**
     * Get Current Authenticated User from Session
     */
    protected function getCurrentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Require authenticated session or redirect to login
     */
    protected function requireAuth(): array
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            $this->setFlash('warning', 'Bu sayfayı görüntülemek için lütfen giriş yapın.');
            $this->redirect('/login');
        }
        return $user;
    }

    /**
     * Require guest (not logged in) or redirect to dashboard
     */
    protected function requireGuest(): void
    {
        if ($this->getCurrentUser()) {
            $this->redirect('/workout/dashboard');
        }
    }

    /**
     * Flash messages
     */
    protected function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type'    => $type, // 'success', 'danger', 'warning', 'info'
            'message' => $message
        ];
    }

    protected function getFlash(): ?array
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}
