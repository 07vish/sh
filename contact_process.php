<?php
declare(strict_types=1);

session_start();

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim((string)($_POST['name'] ?? ''));
    $email   = trim((string)($_POST['email'] ?? ''));
    $phone   = trim((string)($_POST['phone'] ?? ''));
    $subject = trim((string)($_POST['subject'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));

    // Server-side validation
    if (empty($name) || strlen($name) < 2 || !preg_match('/^[a-zA-Z\s]+$/', $name)) {
        $errors[] = 'Full Name must contain at least 2 alphabetic characters.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = 'Phone number must be exactly 10 digits.';
    }

    if (empty($subject) || strlen($subject) < 3) {
        $errors[] = 'Subject must be at least 3 characters long.';
    }

    if (empty($message) || strlen($message) < 10) {
        $errors[] = 'Message must be at least 10 characters long.';
    }

    // Sanitize values
    $nameSanitized    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $emailSanitized   = filter_var($email, FILTER_SANITIZE_EMAIL);
    $phoneSanitized   = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
    $subjectSanitized = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    $messageSanitized = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    if (empty($errors)) {
        $dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');

        // 1. Save to CSV file safely with flock
        $csvFile = $dataDir . DIRECTORY_SEPARATOR . 'contact_messages.csv';
        $isNewCsv = !file_exists($csvFile) || filesize($csvFile) === 0;

        $fp = fopen($csvFile, 'a');
        if ($fp && flock($fp, LOCK_EX)) {
            if ($isNewCsv) {
                fputcsv($fp, ['Timestamp', 'Full Name', 'Email', 'Phone', 'Subject', 'Message']);
            }
            fputcsv($fp, [$timestamp, $nameSanitized, $emailSanitized, $phoneSanitized, $subjectSanitized, $messageSanitized]);
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        // 2. Save to JSON file safely with flock
        $jsonFile = $dataDir . DIRECTORY_SEPARATOR . 'contact_messages.json';
        $jsonFp = fopen($jsonFile, 'c+');
        if ($jsonFp && flock($jsonFp, LOCK_EX)) {
            $contents = stream_get_contents($jsonFp);
            $messages = ($contents !== false && $contents !== '') ? json_decode($contents, true) : [];
            if (!is_array($messages)) {
                $messages = [];
            }

            $messages[] = [
                'id' => count($messages) + 1,
                'timestamp' => $timestamp,
                'name' => $nameSanitized,
                'email' => $emailSanitized,
                'phone' => $phoneSanitized,
                'subject' => $subjectSanitized,
                'message' => $messageSanitized,
            ];

            ftruncate($jsonFp, 0);
            rewind($jsonFp);
            fwrite($jsonFp, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            flock($jsonFp, LOCK_UN);
            fclose($jsonFp);
        }

        $successMessage = 'Thank you! Your message has been received, sanitized, and safely stored in CSV and JSON formats.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Submission Result | StudentHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.html">StudentHub Portal</a>
        <div class="ms-auto">
            <a href="contact.html" class="btn btn-outline-light btn-sm">Back to Contact Form</a>
            <a href="view_submissions.php" class="btn btn-light btn-sm ms-2">View Stored Records</a>
        </div>
    </div>
</nav>

<main class="site-container my-5">
    <div class="card p-4 shadow-sm col-md-8 mx-auto">
        <h3 class="mb-3">Server-Side Form Processing Status</h3>

        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success">
                <h5 class="alert-heading">Success!</h5>
                <p class="mb-2"><?= htmlspecialchars($successMessage) ?></p>
                <hr>
                <p class="mb-1"><strong>Submitted By:</strong> <?= $nameSanitized ?> (<?= $emailSanitized ?>)</p>
                <p class="mb-1"><strong>Subject:</strong> <?= $subjectSanitized ?></p>
                <p class="mb-0"><strong>Stored In:</strong> <code>data/contact_messages.csv</code> and <code>data/contact_messages.json</code></p>
            </div>
            <div class="d-flex gap-2 mt-3">
                <a href="contact.html" class="btn btn-primary">Submit Another Query</a>
                <a href="view_submissions.php" class="btn btn-outline-secondary">Inspect CSV/JSON Submissions</a>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <h5 class="alert-heading">Validation Errors Occurred:</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <a href="javascript:history.back()" class="btn btn-warning mt-2">Go Back and Correct Fields</a>
        <?php endif; ?>
    </div>
</main>

<footer class="bg-light text-center text-muted py-3 mt-5">
    © 2026 StudentHub Portal | ITUE203 Practical 7
</footer>
</body>
</html>
