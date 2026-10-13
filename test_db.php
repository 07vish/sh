<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';

$connectionType = 'Unknown';
$statusMessage = '';
$tableStats = [];
$error = null;

try {
    $pdo = get_pdo_connection();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $connectionType = strtoupper($driver) . ' Connection';

    // Fetch counts from tables
    $tables = ['users', 'students', 'events', 'registrations', 'audit_logs'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) AS total FROM {$table}");
            $tableStats[$table] = (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            $tableStats[$table] = 'Table not created yet';
        }
    }
    $statusMessage = 'Connected successfully to ' . $connectionType . ' with UTF-8 character encoding!';
} catch (Exception $e) {
    $error = $e->getMessage();
}

if (php_sapi_name() === 'cli') {
    echo "=== StudentHub Database Connectivity Test ===\n";
    if ($error) {
        echo "Error: {$error}\n";
        exit(1);
    }
    echo "Status: {$statusMessage}\n";
    echo "Driver: {$connectionType}\n";
    echo "--- Table Statistics ---\n";
    foreach ($tableStats as $table => $count) {
        echo "  - {$table}: {$count} rows\n";
    }
    echo "=============================================\n";
    exit(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Connectivity Test | StudentHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.html">StudentHub Portal</a>
        <div class="ms-auto">
            <a href="index.html" class="btn btn-light btn-sm">Home</a>
        </div>
    </div>
</nav>

<main class="site-container my-5">
    <div class="card p-4 shadow-sm col-md-8 mx-auto">
        <h3 class="page-title mb-2">Practical 8: Database Connectivity Verification</h3>
        <p class="text-muted">Testing MySQL / PDO database connection and schema initialization status.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <h5>Connection Failed</h5>
                <p class="mb-0"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                <h5 class="alert-heading">Connection Successful!</h5>
                <p class="mb-0"><?= htmlspecialchars($statusMessage) ?></p>
            </div>

            <h5 class="mt-4 mb-3">Database Table Verification:</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Table Name</th>
                            <th>Status / Row Count</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>users</code></td>
                            <td><span class="badge bg-success"><?= $tableStats['users'] ?> records</span></td>
                            <td>Authentication, user roles (admin, student), hashed passwords</td>
                        </tr>
                        <tr>
                            <td><code>students</code></td>
                            <td><span class="badge bg-success"><?= $tableStats['students'] ?> records</span></td>
                            <td>Enrolled students, student IDs, departments, semesters</td>
                        </tr>
                        <tr>
                            <td><code>events</code></td>
                            <td><span class="badge bg-success"><?= $tableStats['events'] ?> records</span></td>
                            <td>College hackathons, workshops, cultural & sports events</td>
                        </tr>
                        <tr>
                            <td><code>registrations</code></td>
                            <td><span class="badge bg-success"><?= $tableStats['registrations'] ?> records</span></td>
                            <td>Event participation and attendance tracking</td>
                        </tr>
                        <tr>
                            <td><code>audit_logs</code></td>
                            <td><span class="badge bg-success"><?= $tableStats['audit_logs'] ?> records</span></td>
                            <td>Administrative security trail and action timestamps</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-3 bg-light rounded border mt-3 small">
                <strong>MySQL Schema Location:</strong> <code>database/schema.sql</code> and <code>database/seed.sql</code><br>
                <strong>Connection Script:</strong> <code>config/db.php</code> using PDO & MySQLi prepared statements.
            </div>
        <?php endif; ?>
    </div>
</main>

<footer class="bg-light text-center text-muted py-3 mt-5">
    © 2026 StudentHub Portal | ITUE203 Practical 8
</footer>
</body>
</html>
