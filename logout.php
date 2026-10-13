<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$userId = $_SESSION['user_id'] ?? 0;
if ($userId > 0) {
    log_audit((int)$userId, 'LOGOUT', 'User logged out intentionally.');
}

// Clear remember token in DB if set
if (!empty($_COOKIE['remember_token'])) {
    try {
        $pdo = get_pdo_connection();
        $stmt = $pdo->prepare('UPDATE users SET remember_token = NULL WHERE remember_token = ?');
        $stmt->execute([$_COOKIE['remember_token']]);
    } catch (Exception $e) {}
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destroy session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header('Location: login.php?msg=logged_out');
exit;
