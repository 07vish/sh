<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$user = current_user();
$student = null;

try {
    $pdo = get_pdo_connection();
    $stmt = $pdo->prepare('SELECT * FROM students WHERE email = ? OR user_id = ? LIMIT 1');
    $stmt->execute([$user['email'], $user['id']]);
    $student = $stmt->fetch();
} catch (Exception $e) {}

$displayName = $student['full_name'] ?? $user['username'];
$studentId = $student['student_id'] ?? 'D26IT113';
$department = $student['department'] ?? 'IT';
$semester = $student['semester'] ?? 3;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard | StudentHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">StudentHub Portal</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                <li class="nav-item"><a class="nav-link" href="index.html">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="events.html">Events</a></li>
                <li class="nav-item"><a class="nav-link" href="notice.html">Notice</a></li>
                <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="attendance.html">Attendance</a></li>
                <li class="nav-item"><a class="nav-link" href="assignment.html">Assignment</a></li>
                <li class="nav-item"><a class="nav-link" href="timetable.html">Timetable</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.html">Profile</a></li>
                <li class="nav-item ms-lg-2">
                    <span class="badge bg-light text-primary py-2 px-3">Hi, <?= htmlspecialchars($displayName) ?></span>
                </li>
                <li class="nav-item ms-2">
                    <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="site-container my-4">
    <!-- Welcome Header -->
    <div class="card p-4 shadow-sm mb-4 bg-primary text-white border-0">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="mb-1">Welcome back, <?= htmlspecialchars($displayName) ?>!</h2>
                <p class="mb-0 opacity-75">Student ID: <strong><?= htmlspecialchars($studentId) ?></strong> | <?= htmlspecialchars($department) ?> Department (Sem <?= $semester ?>)</p>
            </div>
            <div>
                <span class="badge bg-white text-primary p-2 fs-6">Session Active (Role: <?= strtoupper($user['role']) ?>)</span>
            </div>
        </div>
    </div>

    <!-- Metric Widgets -->
    <div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
        <div class="col">
            <div class="card p-3 shadow-sm border-0 border-start border-primary border-4">
                <h6 class="text-muted text-uppercase mb-1 small fw-bold">Overall Attendance</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="fw-bold text-primary mb-0">92.4%</h3>
                    <span class="badge bg-success-subtle text-success">Good Standing</span>
                </div>
                <small class="text-muted mt-2 d-block">Eligible for end-semester exams (min 80%).</small>
            </div>
        </div>
        <div class="col">
            <div class="card p-3 shadow-sm border-0 border-start border-warning border-4">
                <h6 class="text-muted text-uppercase mb-1 small fw-bold">Pending Assignments</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="fw-bold text-warning mb-0">2 Tasks</h3>
                    <span class="badge bg-warning-subtle text-dark">Due this week</span>
                </div>
                <small class="text-muted mt-2 d-block">WDF Practical 10 & 11 submission.</small>
            </div>
        </div>
        <div class="col">
            <div class="card p-3 shadow-sm border-0 border-start border-info border-4">
                <h6 class="text-muted text-uppercase mb-1 small fw-bold">Cumulative CGPA</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="fw-bold text-info mb-0"><?= number_format((float)($student['cgpa'] ?? 9.25), 2) ?></h3>
                    <span class="badge bg-info-subtle text-info">Semester 3</span>
                </div>
                <small class="text-muted mt-2 d-block">Dean's Honour Roll percentile.</small>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Grid -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card p-4 shadow-sm h-100">
                <h4 class="mb-3 text-primary">Academic Modules</h4>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <a href="attendance.html" class="p-3 bg-light rounded text-decoration-none d-block border hover-shadow">
                            <h6 class="fw-bold text-dark mb-1">Attendance Tracker</h6>
                            <small class="text-muted">Subject-wise lecture and laboratory attendance.</small>
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="assignment.html" class="p-3 bg-light rounded text-decoration-none d-block border hover-shadow">
                            <h6 class="fw-bold text-dark mb-1">Assignments & Tasks</h6>
                            <small class="text-muted">Upload code submissions and track deadlines.</small>
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="timetable.html" class="p-3 bg-light rounded text-decoration-none d-block border hover-shadow">
                            <h6 class="fw-bold text-dark mb-1">Class Timetable</h6>
                            <small class="text-muted">Weekly schedule of lectures and lab batches.</small>
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="events.html" class="p-3 bg-light rounded text-decoration-none d-block border hover-shadow">
                            <h6 class="fw-bold text-dark mb-1">Campus Events</h6>
                            <small class="text-muted">Hackathons, sports tournaments, and workshops.</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-4 shadow-sm h-100">
                <h4 class="mb-3 text-primary">Quick Actions</h4>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><a href="profile.html" class="btn btn-outline-primary w-100 text-start">View My Profile</a></li>
                    <li class="mb-2"><a href="notice.html" class="btn btn-outline-secondary w-100 text-start">Check Circulars</a></li>
                    <li class="mb-2"><a href="contact.html" class="btn btn-outline-info w-100 text-start">Contact Department</a></li>
                    <li class="mt-4 pt-3 border-top"><a href="logout.php" class="btn btn-danger w-100">Sign Out (Logout)</a></li>
                </ul>
            </div>
        </div>
    </div>
</main>

<footer class="bg-light text-center text-muted py-3 mt-5">
    © 2026 StudentHub Portal | ITUE203 Practical 10 Dashboard
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
