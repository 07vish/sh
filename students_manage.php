<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_pdo_connection();
$user = current_user();

$successMsg = '';
$errorMsg = '';

// Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students_export_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Student ID', 'Full Name', 'Email', 'Phone', 'Department', 'Semester', 'Gender', 'CGPA']);

    $exportStmt = $pdo->query('SELECT id, student_id, full_name, email, phone, department, semester, gender, cgpa FROM students ORDER BY student_id ASC');
    while ($row = $exportStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Handle POST actions: Add, Edit, Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $studentId  = strtoupper(trim((string)($_POST['student_id'] ?? '')));
        $fullName   = trim((string)($_POST['full_name'] ?? ''));
        $email      = strtolower(trim((string)($_POST['email'] ?? '')));
        $phone      = trim((string)($_POST['phone'] ?? ''));
        $department = trim((string)($_POST['department'] ?? 'IT'));
        $semester   = (int)($_POST['semester'] ?? 3);
        $gender     = trim((string)($_POST['gender'] ?? 'Male'));
        $cgpa       = (float)($_POST['cgpa'] ?? 8.0);

        if (empty($studentId) || empty($fullName) || empty($email)) {
            $errorMsg = 'Student ID, Full Name, and Email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Invalid email address format.';
        } else {
            try {
                // Prepared statement to check duplicate
                $chk = $pdo->prepare('SELECT id FROM students WHERE student_id = ? OR email = ? LIMIT 1');
                $chk->execute([$studentId, $email]);
                if ($chk->fetch()) {
                    $errorMsg = 'A student with this Student ID or Email already exists.';
                } else {
                    $insertStmt = $pdo->prepare('INSERT INTO students (student_id, full_name, email, phone, department, semester, gender, cgpa) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    $insertStmt->execute([$studentId, $fullName, $email, $phone, $department, $semester, $gender, $cgpa]);
                    log_audit((int)$user['id'], 'STUDENT_ADD', "Added student {$studentId} ({$fullName})");
                    $successMsg = "Student record for {$fullName} ({$studentId}) added successfully.";
                }
            } catch (Exception $e) {
                $errorMsg = 'Failed to add student: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'update') {
        $id         = (int)($_POST['id'] ?? 0);
        $studentId  = strtoupper(trim((string)($_POST['student_id'] ?? '')));
        $fullName   = trim((string)($_POST['full_name'] ?? ''));
        $email      = strtolower(trim((string)($_POST['email'] ?? '')));
        $phone      = trim((string)($_POST['phone'] ?? ''));
        $department = trim((string)($_POST['department'] ?? 'IT'));
        $semester   = (int)($_POST['semester'] ?? 3);
        $gender     = trim((string)($_POST['gender'] ?? 'Male'));
        $cgpa       = (float)($_POST['cgpa'] ?? 8.0);

        if ($id <= 0 || empty($studentId) || empty($fullName) || empty($email)) {
            $errorMsg = 'Invalid student record or missing required fields.';
        } else {
            try {
                $updStmt = $pdo->prepare('UPDATE students SET student_id = ?, full_name = ?, email = ?, phone = ?, department = ?, semester = ?, gender = ?, cgpa = ? WHERE id = ?');
                $updStmt->execute([$studentId, $fullName, $email, $phone, $department, $semester, $gender, $cgpa, $id]);
                log_audit((int)$user['id'], 'STUDENT_UPDATE', "Updated student ID {$id} ({$studentId})");
                $successMsg = "Student record {$studentId} updated successfully.";
            } catch (Exception $e) {
                $errorMsg = 'Failed to update record: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $delStmt = $pdo->prepare('DELETE FROM students WHERE id = ?');
                $delStmt->execute([$id]);
                log_audit((int)$user['id'], 'STUDENT_DELETE', "Deleted student ID: {$id}");
                $successMsg = "Student record removed successfully.";
            } catch (Exception $e) {
                $errorMsg = 'Failed to delete record: ' . $e->getMessage();
            }
        }
    }
}

// Search and Filter parameters
$searchQuery = trim((string)($_GET['search'] ?? ''));
$filterDept  = trim((string)($_GET['department'] ?? 'all'));
$filterSem   = trim((string)($_GET['semester'] ?? 'all'));

$whereClauses = [];
$params = [];

if ($searchQuery !== '') {
    $whereClauses[] = '(student_id LIKE ? OR full_name LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $wildcard = "%{$searchQuery}%";
    $params[] = $wildcard;
    $params[] = $wildcard;
    $params[] = $wildcard;
    $params[] = $wildcard;
}

if ($filterDept !== 'all' && $filterDept !== '') {
    $whereClauses[] = 'department = ?';
    $params[] = $filterDept;
}

if ($filterSem !== 'all' && $filterSem !== '') {
    $whereClauses[] = 'semester = ?';
    $params[] = (int)$filterSem;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM students {$whereSql}");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();

// Fetch filtered rows
$querySql = "SELECT * FROM students {$whereSql} ORDER BY student_id ASC";
$stmt = $pdo->prepare($querySql);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Management CRUD | StudentHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">StudentHub Portal</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="students_manage.php">Students</a></li>
                <li class="nav-item"><a class="nav-link" href="events_manage.php">Events</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Admin Panel</a></li>
                <li class="nav-item ms-2"><a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="site-container my-4">
    <div class="card p-4 shadow-sm">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h3 class="page-title mb-1 text-primary">Student Management CRUD</h3>
                <p class="text-muted mb-0">Practical 11: Add, View, Edit, Delete, Search, and Filter records using MySQL prepared statements.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="students_manage.php?action=export_csv" class="btn btn-outline-success btn-sm">Export to CSV</a>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">+ Add Student</button>
            </div>
        </div>

        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($successMsg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($errorMsg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Search & Filter Controls -->
        <form method="GET" action="students_manage.php" class="row g-2 mb-4 p-3 bg-light rounded border align-items-end">
            <div class="col-md-5">
                <label for="search" class="form-label fw-semibold small mb-1">Search Keyword</label>
                <input type="search" name="search" id="search" class="form-control form-control-sm" placeholder="Name, ID, Email, or Phone" value="<?= htmlspecialchars($searchQuery) ?>">
            </div>
            <div class="col-md-3">
                <label for="department" class="form-label fw-semibold small mb-1">Department</label>
                <select name="department" id="department" class="form-select form-select-sm">
                    <option value="all" <?= $filterDept === 'all' ? 'selected' : '' ?>>All Departments</option>
                    <option value="IT" <?= $filterDept === 'IT' ? 'selected' : '' ?>>IT</option>
                    <option value="CE" <?= $filterDept === 'CE' ? 'selected' : '' ?>>CE</option>
                    <option value="EC" <?= $filterDept === 'EC' ? 'selected' : '' ?>>EC</option>
                    <option value="ME" <?= $filterDept === 'ME' ? 'selected' : '' ?>>ME</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="semester" class="form-label fw-semibold small mb-1">Semester</label>
                <select name="semester" id="semester" class="form-select form-select-sm">
                    <option value="all" <?= $filterSem === 'all' ? 'selected' : '' ?>>All Semesters</option>
                    <?php for ($s = 1; $s <= 8; $s++): ?>
                        <option value="<?= $s ?>" <?= $filterSem === (string)$s ? 'selected' : '' ?>>Semester <?= $s ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                <a href="students_manage.php" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted small">Total Records Found: <strong><?= $totalRecords ?></strong></span>
        </div>

        <!-- Student Records Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Dept / Sem</th>
                        <th>Gender</th>
                        <th>CGPA</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No students found matching the criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $i => $st): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><span class="badge bg-light text-primary border fw-bold"><?= htmlspecialchars($st['student_id']) ?></span></td>
                                <td class="fw-semibold"><?= htmlspecialchars($st['full_name']) ?></td>
                                <td><a href="mailto:<?= htmlspecialchars($st['email']) ?>"><?= htmlspecialchars($st['email']) ?></a></td>
                                <td><?= htmlspecialchars($st['phone'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($st['department']) ?> (Sem <?= (int)$st['semester'] ?>)</td>
                                <td><?= htmlspecialchars($st['gender']) ?></td>
                                <td><span class="badge bg-success-subtle text-success"><?= number_format((float)$st['cgpa'], 2) ?></span></td>
                                <td class="text-center">
                                    <button class="btn btn-outline-primary btn-sm me-1 edit-btn"
                                        data-id="<?= $st['id'] ?>"
                                        data-student_id="<?= htmlspecialchars($st['student_id']) ?>"
                                        data-name="<?= htmlspecialchars($st['full_name']) ?>"
                                        data-email="<?= htmlspecialchars($st['email']) ?>"
                                        data-phone="<?= htmlspecialchars($st['phone'] ?? '') ?>"
                                        data-dept="<?= htmlspecialchars($st['department']) ?>"
                                        data-sem="<?= (int)$st['semester'] ?>"
                                        data-gender="<?= htmlspecialchars($st['gender']) ?>"
                                        data-cgpa="<?= $st['cgpa'] ?>">
                                        Edit
                                    </button>
                                    <form method="POST" action="students_manage.php" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete student <?= htmlspecialchars($st['student_id']) ?>?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $st['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal: Add Student -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="students_manage.php" class="modal-content">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Student ID <span class="text-danger">*</span></label>
                    <input type="text" name="student_id" class="form-control" placeholder="D26IT121" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" required placeholder="Full Name">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required placeholder="student@charusat.edu.in">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Phone</label>
                    <input type="tel" name="phone" class="form-control" placeholder="10-digit phone" pattern="[0-9]{10}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Department</label>
                    <select name="department" class="form-select">
                        <option value="IT" selected>IT</option>
                        <option value="CE">CE</option>
                        <option value="EC">EC</option>
                        <option value="ME">ME</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Semester</label>
                    <select name="semester" class="form-select">
                        <?php for ($s=1;$s<=8;$s++): ?>
                            <option value="<?= $s ?>" <?= $s===3?'selected':'' ?>>Sem <?= $s ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">CGPA</label>
                    <input type="number" step="0.01" min="0" max="10" name="cgpa" class="form-control" value="8.50">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="Male" selected>Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Student</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Student -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="students_manage.php" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header">
                <h5 class="modal-title">Edit Student Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Student ID</label>
                    <input type="text" name="student_id" id="edit_student_id" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Full Name</label>
                    <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Email</label>
                    <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Phone</label>
                    <input type="tel" name="phone" id="edit_phone" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Department</label>
                    <select name="department" id="edit_department" class="form-select">
                        <option value="IT">IT</option>
                        <option value="CE">CE</option>
                        <option value="EC">EC</option>
                        <option value="ME">ME</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Semester</label>
                    <select name="semester" id="edit_semester" class="form-select">
                        <?php for ($s=1;$s<=8;$s++): ?>
                            <option value="<?= $s ?>">Sem <?= $s ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">CGPA</label>
                    <input type="number" step="0.01" min="0" max="10" name="cgpa" id="edit_cgpa" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Gender</label>
                    <select name="gender" id="edit_gender" class="form-select">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Student</button>
            </div>
        </form>
    </div>
</div>

<footer class="bg-light text-center text-muted py-3 mt-5">
    © 2026 StudentHub Portal | ITUE203 Practical 11 Student CRUD
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_student_id').value = this.dataset.student_id;
            document.getElementById('edit_full_name').value = this.dataset.name;
            document.getElementById('edit_email').value = this.dataset.email;
            document.getElementById('edit_phone').value = this.dataset.phone;
            document.getElementById('edit_department').value = this.dataset.dept;
            document.getElementById('edit_semester').value = this.dataset.sem;
            document.getElementById('edit_gender').value = this.dataset.gender;
            document.getElementById('edit_cgpa').value = this.dataset.cgpa;
            new bootstrap.Modal(document.getElementById('editStudentModal')).show();
        });
    });
</script>
</body>
</html>
