<?php
declare(strict_types=1);

$csvPath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'contact_messages.csv';
$jsonPath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'contact_messages.json';

// Read CSV data
$csvRows = [];
if (file_exists($csvPath)) {
    if (($handle = fopen($csvPath, 'r')) !== false) {
        $header = fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== false) {
            $csvRows[] = $data;
        }
        fclose($handle);
    }
}

// Read JSON data
$jsonRecords = [];
if (file_exists($jsonPath)) {
    $content = file_get_contents($jsonPath);
    if ($content !== false && $content !== '') {
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $jsonRecords = $decoded;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Saved Form Submissions | StudentHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.html">StudentHub Portal</a>
        <div class="ms-auto">
            <a href="contact.html" class="btn btn-outline-light btn-sm">Contact Form</a>
            <a href="index.html" class="btn btn-light btn-sm ms-2">Home</a>
        </div>
    </div>
</nav>

<main class="site-container my-4">
    <div class="card p-4 shadow-sm">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h3 class="page-title mb-1">Server Stored Form Submissions</h3>
                <p class="text-muted mb-0">Practical 7: Demonstrating file-based persistence using CSV and JSON formats with safe file locking.</p>
            </div>
            <a href="contact.html" class="btn btn-primary btn-sm">+ Add New Submission</a>
        </div>

        <ul class="nav nav-tabs mb-4" id="submissionTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="csv-tab" data-bs-toggle="tab" data-bs-target="#csv-view" type="button" role="tab">
                    CSV Storage (<code>contact_messages.csv</code>) <span class="badge bg-secondary"><?= count($csvRows) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="json-tab" data-bs-toggle="tab" data-bs-target="#json-view" type="button" role="tab">
                    JSON Storage (<code>contact_messages.json</code>) <span class="badge bg-secondary"><?= count($jsonRecords) ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="submissionTabContent">
            <!-- CSV View -->
            <div class="tab-pane fade show active" id="csv-view" role="tabpanel">
                <?php if (empty($csvRows)): ?>
                    <div class="alert alert-info">No CSV entries recorded yet. Submit a message via <a href="contact.html">Contact Form</a> to test.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Timestamp</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($csvRows as $idx => $row): ?>
                                    <tr>
                                        <td><?= $idx + 1 ?></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($row[0] ?? '') ?></small></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($row[1] ?? '') ?></td>
                                        <td><a href="mailto:<?= htmlspecialchars($row[2] ?? '') ?>"><?= htmlspecialchars($row[2] ?? '') ?></a></td>
                                        <td><?= htmlspecialchars($row[3] ?? '-') ?></td>
                                        <td><span class="badge bg-info-subtle text-info border"><?= htmlspecialchars($row[4] ?? '') ?></span></td>
                                        <td><?= htmlspecialchars($row[5] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- JSON View -->
            <div class="tab-pane fade" id="json-view" role="tabpanel">
                <?php if (empty($jsonRecords)): ?>
                    <div class="alert alert-info">No JSON records found yet.</div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 g-3">
                        <?php foreach (array_reverse($jsonRecords) as $item): ?>
                            <div class="col">
                                <div class="card h-100 border p-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title text-primary mb-0"><?= htmlspecialchars($item['name'] ?? '') ?></h5>
                                        <small class="text-muted"><?= htmlspecialchars($item['timestamp'] ?? '') ?></small>
                                    </div>
                                    <p class="mb-1 small"><strong>Email:</strong> <?= htmlspecialchars($item['email'] ?? '') ?></p>
                                    <p class="mb-1 small"><strong>Phone:</strong> <?= htmlspecialchars($item['phone'] ?? '-') ?></p>
                                    <p class="mb-1 small"><strong>Subject:</strong> <?= htmlspecialchars($item['subject'] ?? '') ?></p>
                                    <div class="p-2 bg-white rounded border small mt-2">
                                        <strong>Message:</strong> <?= nl2br(htmlspecialchars($item['message'] ?? '')) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<footer class="bg-light text-center text-muted py-3 mt-5">
    © 2026 StudentHub Portal | ITUE203 Practical 7 Form Processing
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
