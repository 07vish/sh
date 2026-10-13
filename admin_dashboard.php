<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

// Practical 14: Restrict access to admin role only
require_role('admin');

$pdo = get_pdo_connection();
$user = current_user();

// Log admin dashboard visit in audit_logs
log_audit((int)$user['id'], 'ADMIN_DASHBOARD_ACCESS', 'Admin viewed dashboard overview.');

// 1. Database Aggregations for Key Metric Cards
$countStudents = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$countEvents = (int)$pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
$countRegistrations = (int)$pdo->query('SELECT COUNT(*) FROM registrations')->fetchColumn();
$countUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();

// 2. Department Breakdown
$deptCounts = $pdo->query('SELECT department, COUNT(*) AS count FROM students GROUP BY department ORDER BY count DESC')->fetchAll();

// 3. Recent Audit Logs
$recentLogs = $pdo->query('SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 10')->fetchAll();

// 4. Latest Student Registrations
$latestStudents = $pdo->query('SELECT student_id, full_name, email, department, semester, created_at FROM students ORDER BY id DESC LIMIT 5')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | StudentHub Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .admin-stat-card {
            border: none;
            border-radius: 12px;
            transition: transform 0.2s;
        }
        .admin-stat-card:hover {
            transform: translateY(-3px);
        }
        .badge-role {
            background-color: #f59e0b;
            color: #ffffff;
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 20px;
        }
    </style>
</head>
<body class="bg-light">

<!-- Dynamic Role-Based Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="admin_dashboard.php">
            <span class="badge bg-danger">ADMIN</span>
            <span class="fw-bold">StudentHub Control Panel</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="admin_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="students_manage.php">Student CRUD</a></li>
                <li class="nav-item"><a class="nav-link" href="events_manage.php">Event CRUD</a></li>
                <li class="nav-item"><a class="nav-link" href="events_ajax.php">AJAX API</a></li>
                <li class="nav-item"><a class="nav-link" href="view_submissions.php">Form Inquiries</a></li>
                <li class="nav-item"><a class="nav-link" href="test_db.php">DB Status</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <span class="badge badge-role">Logged as: <?= htmlspecialchars($user['username']) ?> (Admin)</span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Sign Out</a>
            </div>
        </div>
    </div>
</nav>

<main class="container my-4">
    <!-- Header Greeting -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h2 class="fw-bold mb-0 text-dark">Portal Administration Dashboard</h2>
            <p class="text-muted mb-0">Practical 14: Role-Based Access Control, dynamic menu routing, and automated audit logging.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="students_manage.php" class="btn btn-primary btn-sm">+ Add Student</a>
            <a href="events_manage.php" class="btn btn-outline-dark btn-sm">+ Add Event</a>
            <a href="students_manage.php?action=export_csv" class="btn btn-outline-success btn-sm">Export Data</a>
        </div>
    </div>

    <!-- 4 Metrics Row -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3 mb-4">
        <div class="col">
            <div class="card p-3 shadow-sm admin-stat-card bg-white border-start border-primary border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Enrolled Students</span>
                        <h2 class="fw-bold text-primary mb-0 mt-1"><?= $countStudents ?></h2>
                    </div>
                    <span class="badge bg-primary-subtle text-primary p-2">Records</span>
                </div>
                <small class="text-muted mt-2 d-block">Active in current semester.</small>
            </div>
        </div>

        <div class="col">
            <div class="card p-3 shadow-sm admin-stat-card bg-white border-start border-success border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Campus Events</span>
                        <h2 class="fw-bold text-success mb-0 mt-1"><?= $countEvents ?></h2>
                    </div>
                    <span class="badge bg-success-subtle text-success p-2">Active</span>
                </div>
                <small class="text-muted mt-2 d-block">Hackathons & workshops.</small>
            </div>
        </div>

        <div class="col">
            <div class="card p-3 shadow-sm admin-stat-card bg-white border-start border-warning border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Event Registrations</span>
                        <h2 class="fw-bold text-warning mb-0 mt-1"><?= $countRegistrations ?></h2>
                    </div>
                    <span class="badge bg-warning-subtle text-dark p-2">Total</span>
                </div>
                <small class="text-muted mt-2 d-block">Student participations.</small>
            </div>
        </div>

        <div class="col">
            <div class="card p-3 shadow-sm admin-stat-card bg-white border-start border-info border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Active User Accounts</span>
                        <h2 class="fw-bold text-info mb-0 mt-1"><?= $countUsers ?></h2>
                    </div>
                    <span class="badge bg-info-subtle text-info p-2">Verified</span>
                </div>
                <small class="text-muted mt-2 d-block">Admin & Student logins.</small>
            </div>
        </div>
    </div>

    <!-- Middle Section: Recent Students & Department Distribution -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm p-4 h-100 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">Recently Enrolled Students</h5>
                    <a href="students_manage.php" class="btn btn-outline-primary btn-sm">Manage All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Semester</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latestStudents as $s): ?>
                                <tr>
                                    <td><span class="badge bg-light text-primary border"><?= htmlspecialchars($s['student_id']) ?></span></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($s['full_name']) ?></td>
                                    <td><?= htmlspecialchars($s['department']) ?></td>
                                    <td>Sem <?= (int)$s['semester'] ?></td>
                                    <td><a href="mailto:<?= htmlspecialchars($s['email']) ?>" class="small"><?= htmlspecialchars($s['email']) ?></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm p-4 h-100 bg-white">
                <h5 class="fw-bold text-dark mb-3">Branch Distribution</h5>
                <ul class="list-group list-group-flush mb-3">
                    <?php foreach ($deptCounts as $d): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="fw-semibold"><?= htmlspecialchars($d['department']) ?> Department</span>
                            <span class="badge bg-primary rounded-pill"><?= $d['count'] ?> Students</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="p-3 bg-light rounded border small text-muted">
                    <strong>RBAC Enforcement:</strong> Only accounts with <code>role = 'admin'</code> are authorized to view this administrative command interface.
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Logs Section -->
    <div class="card shadow-sm p-4 bg-white mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold text-dark mb-0">System Audit Trail (Activity Logs)</h5>
                <small class="text-muted">Security traceability for user logins, CRUD modifications, and administrator actions.</small>
            </div>
            <span class="badge bg-secondary"><?= count($recentLogs) ?> Recent Entries</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-striped table-bordered align-middle mb-0 small">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Timestamp</th>
                        <th>Action Performed</th>
                        <th>Triggered By</th>
                        <th>IP Address</th>
                        <th>Event Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLogs as $log): ?>
                        <tr>
                            <td><?= $log['id'] ?></td>
                            <td><span class="text-muted"><?= htmlspecialchars($log['created_at']) ?></span></td>
                            <td>
                                <span class="badge bg-dark-subtle text-dark border font-monospace"><?= htmlspecialchars($log['action']) ?></span>
                            </td>
                            <td><strong><?= htmlspecialchars($log['username'] ?? 'System/Guest') ?></strong></td>
                            <td><code><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></code></td>
                            <td><?= htmlspecialchars($log['details'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<footer class="bg-dark text-white-50 text-center py-3 mt-5 small">
    © 2026 StudentHub Portal | ITUE203 Practical 14 Admin Control Panel & Audit Logs
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
