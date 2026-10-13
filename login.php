<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$errorMessage = '';
$infoMessage = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'timeout') {
        $infoMessage = 'Your session has timed out due to 15 minutes of inactivity. Please log in again.';
    } elseif ($_GET['msg'] === 'logged_out') {
        $infoMessage = 'You have successfully logged out of your session.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim((string)($_POST['username'] ?? ''));
    $password   = (string)($_POST['password'] ?? '');
    $rememberMe = isset($_POST['remember']);

    if (empty($identifier) || empty($password)) {
        $errorMessage = 'Please enter both username/email and password.';
    } else {
        try {
            $pdo = get_pdo_connection();
            // Allow login with either username or email
            $stmt = $pdo->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) AND status = ? LIMIT 1');
            $stmt->execute([$identifier, $identifier, 'active']);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Prevent Session Fixation
                session_regenerate_id(true);

                $_SESSION['user_id']       = (int)$user['id'];
                $_SESSION['username']      = $user['username'];
                $_SESSION['email']         = $user['email'];
                $_SESSION['role']          = $user['role'];
                $_SESSION['last_activity'] = time();

                // Remember Me Token handling
                if ($rememberMe) {
                    $token = bin2hex(random_bytes(32));
                    $updateToken = $pdo->prepare('UPDATE users SET remember_token = ? WHERE id = ?');
                    $updateToken->execute([$token, $user['id']]);
                    setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true); // 30 days httponly
                }

                // Update last login timestamp
                $updateLogin = $pdo->prepare('UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?');
                $updateLogin->execute([$user['id']]);

                // Audit log
                log_audit((int)$user['id'], 'LOGIN_SUCCESS', "User {$user['username']} logged in successfully with role {$user['role']}.");

                // Role-based redirection
                if ($user['role'] === 'admin') {
                    header('Location: admin_dashboard.php');
                    exit;
                } else {
                    $redirect = $_GET['redirect'] ?? 'dashboard.php';
                    header('Location: ' . $redirect);
                    exit;
                }
            } else {
                $errorMessage = 'Invalid username/email or password.';
                // Log failed attempt
                log_audit(0, 'LOGIN_FAILED', "Failed login attempt for identifier: {$identifier}.");
            }
        } catch (Exception $e) {
            $errorMessage = 'Authentication service error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Login | StudentHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.html">StudentHub Portal</a>
        <div class="ms-auto">
            <a href="index.html" class="btn btn-outline-light btn-sm">Home</a>
            <a href="register.php" class="btn btn-light btn-sm ms-2">Register</a>
        </div>
    </div>
</nav>

<main class="site-container my-5">
    <div class="card shadow-sm p-4 col-md-6 col-lg-5 mx-auto">
        <h3 class="page-title text-primary text-center mb-1">Account Login</h3>
        <p class="text-muted text-center small mb-3">Practical 10: Role-based access, session fixation protection, and timeout.</p>

        <?php if (!empty($infoMessage)): ?>
            <div class="alert alert-info py-2 small"><?= htmlspecialchars($infoMessage) ?></div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger py-2 small"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <form action="login.php<?= isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>" method="POST" class="needs-validation" novalidate id="loginForm">
            <div class="mb-3">
                <label for="username" class="form-label fw-semibold">Username or Email</label>
                <input type="text" class="form-control" id="username" name="username" required placeholder="Enter username or college email" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Password</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password">
            </div>

            <div class="mb-3 form-check d-flex justify-content-between align-items-center">
                <div>
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label small" for="remember">Remember me on this device</label>
                </div>
                <a href="contact.html" class="small text-decoration-none">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">Sign In</button>
        </form>

        <div class="mt-4 p-3 bg-light rounded border small">
            <h6 class="fw-bold text-dark mb-1">Demo Credentials for Evaluation:</h6>
            <div class="d-flex justify-content-between">
                <span><strong>Admin Role:</strong> <code>admin</code></span>
                <span>Password: <code>Admin@123</code></span>
            </div>
            <div class="d-flex justify-content-between mt-1">
                <span><strong>Student Role:</strong> <code>vishnu</code></span>
                <span>Password: <code>Student@123</code></span>
            </div>
        </div>

        <div class="text-center mt-3 small text-muted">
            Don't have an account yet? <a href="register.php">Register here</a>
        </div>
    </div>
</main>

<footer class="bg-light text-center text-muted py-3 mt-5">
    © 2026 StudentHub Portal | ITUE203 Practical 10 Authentication
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
