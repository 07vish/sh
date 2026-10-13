<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/db.php';

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName        = trim((string)($_POST['full_name'] ?? ''));
    $studentId       = strtoupper(trim((string)($_POST['student_id'] ?? '')));
    $email           = strtolower(trim((string)($_POST['email'] ?? '')));
    $phone           = trim((string)($_POST['phone'] ?? ''));
    $department      = trim((string)($_POST['department'] ?? 'IT'));
    $semester        = (int)($_POST['semester'] ?? 3);
    $gender          = trim((string)($_POST['gender'] ?? 'Male'));
    $password        = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    $termsAccepted   = isset($_POST['terms']);

    // 1. Server-side validations
    if (strlen($fullName) < 2 || !preg_match('/^[A-Za-z\s]+$/', $fullName)) {
        $errors[] = 'Full name must contain at least 2 letters and only alphabets/spaces.';
    }

    if (empty($studentId) || !preg_match('/^[A-Z0-9]{5,15}$/', $studentId)) {
        $errors[] = 'Student ID must be 5 to 15 alphanumeric characters (e.g. D26IT113).';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = 'Phone number must be exactly 10 digits.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if (!hash_equals($password, $confirmPassword)) {
        $errors[] = 'Password confirmation does not match.';
    }

    if (!$termsAccepted && !isset($_POST['is_ajax'])) {
        $errors[] = 'You must accept the terms and conditions.';
    }

    // 2. Database Duplicate Check & Insert
    if (empty($errors)) {
        try {
            $pdo = get_pdo_connection();

            // Check duplicate email in users or students
            $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $errors[] = 'An account with this email address already exists. Please login instead.';
            }

            // Check duplicate student_id in students
            if (empty($errors)) {
                $checkStd = $pdo->prepare('SELECT id FROM students WHERE student_id = ? LIMIT 1');
                $checkStd->execute([$studentId]);
                if ($checkStd->fetch()) {
                    $errors[] = 'A student with this Student ID is already registered.';
                }
            }

            if (empty($errors)) {
                // Secure password hashing
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $username = strtolower(explode('@', $email)[0]);

                $pdo->beginTransaction();

                // Insert user
                $userStmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)');
                $userStmt->execute([$username, $email, $passwordHash, 'student', 'active']);
                $userId = (int)$pdo->lastInsertId();

                // Insert student profile
                $studentStmt = $pdo->prepare('INSERT INTO students (user_id, student_id, full_name, email, phone, department, semester, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $studentStmt->execute([$userId, $studentId, $fullName, $email, $phone, $department, $semester, $gender]);

                // Audit log
                $auditStmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)');
                $auditStmt->execute([$userId, 'USER_REGISTER', "Student {$studentId} ({$email}) registered successfully.", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                $pdo->commit();

                $successMessage = 'Registration completed successfully! Your account has been securely created with password hashing.';

                if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => $successMessage]);
                    exit;
                }
            }
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }

    if (!empty($errors) && isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
        header('Content-Type: application/json');
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => implode(' ', $errors), 'errors' => $errors]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Registration | StudentHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.html">StudentHub Portal</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="index.html">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="events.html">Events</a></li>
                <li class="nav-item"><a class="nav-link" href="notice.html">Notice</a></li>
                <li class="nav-item"><a class="nav-link" href="login.html">Login</a></li>
                <li class="nav-item"><a class="nav-link active" href="register.php">Register</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.html">Contact</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="site-container my-4">
    <div class="card shadow-sm p-4 col-lg-8 mx-auto">
        <h2 class="page-title text-primary mb-1">Secure Student Registration</h2>
        <p class="text-muted mb-3">Practical 9: Frontend and backend validation with duplicate checking and <code>password_hash()</code>.</p>

        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <h5 class="alert-heading">Registration Successful!</h5>
                <p><?= htmlspecialchars($successMessage) ?></p>
                <hr>
                <a href="login.php" class="btn btn-primary btn-sm">Proceed to Login</a>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <h6 class="fw-bold mb-1">Please correct the following errors:</h6>
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="needs-validation" novalidate id="registerForm">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="full_name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required placeholder="e.g. Vishnu Jariwala" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" pattern="[A-Za-z\s]{2,}">
                    <div class="invalid-feedback">Enter at least 2 alphabetic characters.</div>
                </div>

                <div class="col-md-6">
                    <label for="student_id" class="form-label fw-semibold">Student ID <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="student_id" name="student_id" required placeholder="e.g. D26IT113" value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>" pattern="[A-Za-z0-9]{5,15}">
                    <div class="invalid-feedback">Enter a valid 5-15 character student ID.</div>
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" required placeholder="name@charusat.edu.in" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <div class="invalid-feedback">Enter a valid email address.</div>
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label fw-semibold">Mobile Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="10-digit mobile" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" pattern="[0-9]{10}">
                    <div class="invalid-feedback">Must be a 10-digit mobile number.</div>
                </div>

                <div class="col-md-4">
                    <label for="department" class="form-label fw-semibold">Department</label>
                    <select class="form-select" id="department" name="department">
                        <option value="IT" selected>Information Technology (IT)</option>
                        <option value="CE">Computer Engineering (CE)</option>
                        <option value="EC">Electronics & Comm. (EC)</option>
                        <option value="ME">Mechanical Engineering (ME)</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="semester" class="form-label fw-semibold">Semester</label>
                    <select class="form-select" id="semester" name="semester">
                        <?php for ($s = 1; $s <= 8; $s++): ?>
                            <option value="<?= $s ?>" <?= $s === 3 ? 'selected' : '' ?>>Semester <?= $s ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold d-block">Gender</label>
                    <div class="form-check form-check-inline pt-1">
                        <input class="form-check-input" type="radio" name="gender" id="genderMale" value="Male" checked>
                        <label class="form-check-label" for="genderMale">Male</label>
                    </div>
                    <div class="form-check form-check-inline pt-1">
                        <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="Female">
                        <label class="form-check-label" for="genderFemale">Female</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="8" placeholder="Minimum 8 characters">
                    <div class="invalid-feedback">Password must be at least 8 characters long.</div>
                </div>

                <div class="col-md-6">
                    <label for="confirm_password" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Re-enter password">
                    <div class="invalid-feedback">Passwords must match.</div>
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required checked>
                        <label class="form-check-label small" for="terms">
                            I accept the student code of conduct and portal privacy terms.
                        </label>
                        <div class="invalid-feedback">You must accept the terms before registering.</div>
                    </div>
                </div>

                <div class="col-12 mt-4 d-flex justify-content-between align-items-center">
                    <button type="submit" class="btn btn-primary px-4 py-2">Create Account</button>
                    <span class="text-muted small">Already registered? <a href="login.php">Login here</a></span>
                </div>
            </div>
        </form>
    </div>
</main>

<footer class="bg-light text-center text-muted py-3 mt-5">
    © 2026 StudentHub Portal | ITUE203 Practical 9 Secure Registration
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Client-side bootstrap validation
    (function () {
        'use strict'
        const form = document.getElementById('registerForm');
        form.addEventListener('submit', function (event) {
            const pwd = document.getElementById('password');
            const cpwd = document.getElementById('confirm_password');
            if (pwd.value !== cpwd.value) {
                cpwd.setCustomValidity('Passwords do not match');
            } else {
                cpwd.setCustomValidity('');
            }

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    })();
</script>
</body>
</html>