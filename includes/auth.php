<?php
declare(strict_types=1);

/**
 * Authentication and Session Management Middleware
 * StudentHub Portal - ITUE203 Practical 10
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/db.php';

// Session Inactivity Timeout (15 minutes = 900 seconds)
define('SESSION_TIMEOUT_SECONDS', 900);

function check_session_timeout(): void
{
    if (isset($_SESSION['user_id'])) {
        $lastActivity = $_SESSION['last_activity'] ?? time();
        if ((time() - $lastActivity) > SESSION_TIMEOUT_SECONDS) {
            // Inactivity timeout expired
            $userId = (int)($_SESSION['user_id'] ?? 0);
            session_unset();
            session_destroy();
            if (isset($_COOKIE['remember_token'])) {
                setcookie('remember_token', '', time() - 3600, '/');
            }
            header('Location: login.php?msg=timeout');
            exit;
        }
        $_SESSION['last_activity'] = time();
    }
}

check_session_timeout();

function is_logged_in(): bool
{
    if (isset($_SESSION['user_id'])) {
        return true;
    }

    // Auto-login via Remember-Me Cookie if session expired
    if (!empty($_COOKIE['remember_token'])) {
        try {
            $pdo = get_pdo_connection();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE remember_token = ? AND status = ? LIMIT 1');
            $stmt->execute([$_COOKIE['remember_token'], 'active']);
            $user = $stmt->fetch();
            if ($user) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                return true;
            }
        } catch (Exception $e) {
            // Ignore DB error during cookie check
        }
    }

    return false;
}

function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'] ?? 0,
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 'student',
    ];
}

function require_login(): void
{
    if (!is_logged_in()) {
        $currentPage = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
        header('Location: login.php?redirect=' . urlencode($currentPage));
        exit;
    }
}

function require_role(string $requiredRole): void
{
    require_login();
    $user = current_user();
    if (!$user || $user['role'] !== $requiredRole) {
        http_response_code(403);
        include __DIR__ . '/unauthorized.php';
        exit;
    }
}

function log_audit(int $userId, string $action, string $details = ''): void
{
    try {
        $pdo = get_pdo_connection();
        $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    } catch (Exception $e) {
        // fail silently for audit
    }
}
