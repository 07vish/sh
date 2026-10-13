<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_pdo_connection();
$user = current_user();

$successMsg = '';
$errorMsg = '';

$uploadDir = __DIR__ . '/uploads/posters/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Helper for file upload validation
function handle_poster_upload(array $file, ?string &$error): ?string
{
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload encountered an error code: ' . $file['error'];
        return null;
    }

    // 1. File Size Validation (Max 2MB)
    $maxBytes = 2 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        $error = 'Event poster exceeds maximum allowed size of 2 MB.';
        return null;
    }

    // 2. MIME Type Validation
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!array_key_exists($mimeType, $allowedMimes)) {
        $error = 'Invalid poster image format. Only JPEG, PNG, and WebP files are permitted.';
        return null;
    }

    $ext = $allowedMimes[$mimeType];
    $filename = uniqid('event_poster_', true) . '.' . $ext;
    $destination = __DIR__ . '/uploads/posters/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $error = 'Failed to safely move uploaded poster to server storage.';
        return null;
    }

    return $filename;
}

// Handle Form Submissions: Create, Update, Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title       = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $category    = trim((string)($_POST['category'] ?? 'Technical'));
        $eventDate   = trim((string)($_POST['event_date'] ?? ''));
        $location    = trim((string)($_POST['location'] ?? ''));
        $status      = trim((string)($_POST['status'] ?? 'Upcoming'));

        if (empty($title) || empty($description) || empty($eventDate) || empty($location)) {
            $errorMsg = 'Title, description, date, and location are mandatory fields.';
        } else {
            $uploadError = null;
            $posterFilename = 'default_event.jpg';

            if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploaded = handle_poster_upload($_FILES['poster'], $uploadError);
                if ($uploadError) {
                    $errorMsg = $uploadError;
                } else {
                    $posterFilename = $uploaded ?? 'default_event.jpg';
                }
            }

            if (empty($errorMsg)) {
                try {
                    $stmt = $pdo->prepare('INSERT INTO events (title, description, category, event_date, location, poster_image, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$title, $description, $category, $eventDate, $location, $posterFilename, $status, $user['id']]);
                    log_audit((int)$user['id'], 'EVENT_CREATE', "Created event: {$title}");
                    $successMsg = "Event '{$title}' created successfully with validated poster upload.";
                } catch (Exception $e) {
                    $errorMsg = 'Failed to create event: ' . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'update') {
        $id          = (int)($_POST['id'] ?? 0);
        $title       = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $category    = trim((string)($_POST['category'] ?? 'Technical'));
        $eventDate   = trim((string)($_POST['event_date'] ?? ''));
        $location    = trim((string)($_POST['location'] ?? ''));
        $status      = trim((string)($_POST['status'] ?? 'Upcoming'));

        if ($id <= 0 || empty($title) || empty($description) || empty($eventDate) || empty($location)) {
            $errorMsg = 'Invalid event ID or missing mandatory fields.';
        } else {
            // Check existing record
            $cur = $pdo->prepare('SELECT poster_image FROM events WHERE id = ?');
            $cur->execute([$id]);
            $existing = $cur->fetch();

            if (!$existing) {
                $errorMsg = 'Event not found.';
            } else {
                $posterFilename = $existing['poster_image'];
                if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $uploadError = null;
                    $newPoster = handle_poster_upload($_FILES['poster'], $uploadError);
                    if ($uploadError) {
                        $errorMsg = $uploadError;
                    } elseif ($newPoster) {
                        // Unlink old poster if custom
                        if ($existing['poster_image'] !== 'default_event.jpg') {
                            $oldPath = __DIR__ . '/uploads/posters/' . $existing['poster_image'];
                            if (file_exists($oldPath)) {
                                @unlink($oldPath);
                            }
                        }
                        $posterFilename = $newPoster;
                    }
                }

                if (empty($errorMsg)) {
                    try {
                        $upd = $pdo->prepare('UPDATE events SET title = ?, description = ?, category = ?, event_date = ?, location = ?, poster_image = ?, status = ? WHERE id = ?');
                        $upd->execute([$title, $description, $category, $eventDate, $location, $posterFilename, $status, $id]);
                        log_audit((int)$user['id'], 'EVENT_UPDATE', "Updated event ID {$id}: {$title}");
                        $successMsg = "Event '{$title}' updated successfully.";
                    } catch (Exception $e) {
                        $errorMsg = 'Failed to update event: ' . $e->getMessage();
                    }
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $cur = $pdo->prepare('SELECT title, poster_image FROM events WHERE id = ?');
                $cur->execute([$id]);
                $ev = $cur->fetch();
                if ($ev) {
                    if ($ev['poster_image'] !== 'default_event.jpg') {
                        $oldPath = __DIR__ . '/uploads/posters/' . $ev['poster_image'];
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                    $del = $pdo->prepare('DELETE FROM events WHERE id = ?');
                    $del->execute([$id]);
                    log_audit((int)$user['id'], 'EVENT_DELETE', "Deleted event {$id} ({$ev['title']})");
                    $successMsg = "Event '{$ev['title']}' deleted successfully.";
                }
            } catch (Exception $e) {
                $errorMsg = 'Failed to delete event: ' . $e->getMessage();
            }
        }
    }
}

// Fetch all events
$events = $pdo->query('SELECT * FROM events ORDER BY event_date ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Management CRUD | StudentHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .poster-preview {
            width: 70px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
        }
    </style>
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
                <li class="nav-item"><a class="nav-link" href="students_manage.php">Students</a></li>
                <li class="nav-item"><a class="nav-link active" href="events_manage.php">Events</a></li>
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
                <h3 class="page-title mb-1 text-primary">Event Management CRUD</h3>
                <p class="text-muted mb-0">Practical 12: Add, View, Edit, Delete events with poster image file upload and strict validation.</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEventModal">+ Add New Event</button>
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

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>Poster</th>
                        <th>Title & Category</th>
                        <th>Date</th>
                        <th>Venue / Location</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No events listed yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($events as $ev): ?>
                            <tr>
                                <td>
                                    <?php 
                                        $posterSrc = 'uploads/posters/' . htmlspecialchars($ev['poster_image']);
                                        if (!file_exists(__DIR__ . '/' . $posterSrc)) {
                                            $posterSrc = 'assets/hero_image.jpg';
                                        }
                                    ?>
                                    <img src="<?= $posterSrc ?>" alt="Poster" class="poster-preview">
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($ev['title']) ?></div>
                                    <span class="badge bg-primary-subtle text-primary border"><?= htmlspecialchars($ev['category']) ?></span>
                                </td>
                                <td><small class="fw-semibold text-muted"><?= htmlspecialchars($ev['event_date']) ?></small></td>
                                <td><?= htmlspecialchars($ev['location']) ?></td>
                                <td>
                                    <?php
                                        $statusClass = $ev['status'] === 'Upcoming' ? 'bg-info text-dark' : ($ev['status'] === 'Active' ? 'bg-success' : 'bg-secondary');
                                    ?>
                                    <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($ev['status']) ?></span>
                                </td>
                                <td class="small text-muted" style="max-width: 250px;"><?= htmlspecialchars(mb_strimwidth($ev['description'], 0, 80, '...')) ?></td>
                                <td class="text-center">
                                    <button class="btn btn-outline-primary btn-sm me-1 edit-event-btn"
                                        data-id="<?= $ev['id'] ?>"
                                        data-title="<?= htmlspecialchars($ev['title']) ?>"
                                        data-desc="<?= htmlspecialchars($ev['description']) ?>"
                                        data-cat="<?= htmlspecialchars($ev['category']) ?>"
                                        data-date="<?= htmlspecialchars($ev['event_date']) ?>"
                                        data-loc="<?= htmlspecialchars($ev['location']) ?>"
                                        data-status="<?= htmlspecialchars($ev['status']) ?>">
                                        Edit
                                    </button>
                                    <form method="POST" action="events_manage.php" class="d-inline" onsubmit="return confirm('Delete event: <?= htmlspecialchars($ev['title']) ?>?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $ev['id'] ?>">
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

<!-- Modal: Add Event -->
<div class="modal fade" id="addEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="events_manage.php" enctype="multipart/form-data" class="modal-content">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title">Add New Campus Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-8">
                    <label class="form-label small fw-semibold">Event Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. HackSprint 2026" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                    <select name="category" class="form-select">
                        <option value="Technical" selected>Technical</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Cultural">Cultural</option>
                        <option value="Sports">Sports</option>
                        <option value="Placement">Placement</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Event Date <span class="text-danger">*</span></label>
                    <input type="date" name="event_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Location / Venue <span class="text-danger">*</span></label>
                    <input type="text" name="location" class="form-control" placeholder="Auditorium / Lab 3" required>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Event Poster Image (Max 2MB: JPG, PNG, WebP)</label>
                    <input type="file" name="poster" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <div class="form-text">Validated server-side with MIME inspection and file size limit.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Event Status</label>
                    <select name="status" class="form-select">
                        <option value="Upcoming" selected>Upcoming</option>
                        <option value="Active">Active</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Description <span class="text-danger">*</span></label>
                    <textarea name="description" class="form-control" rows="3" required placeholder="Provide comprehensive description of event, schedule, and participation guidelines..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Publish Event</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Event -->
<div class="modal fade" id="editEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="events_manage.php" enctype="multipart/form-data" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_event_id">
            <div class="modal-header">
                <h5 class="modal-title">Edit Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-8">
                    <label class="form-label small fw-semibold">Event Title</label>
                    <input type="text" name="title" id="edit_event_title" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Category</label>
                    <select name="category" id="edit_event_cat" class="form-select">
                        <option value="Technical">Technical</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Cultural">Cultural</option>
                        <option value="Sports">Sports</option>
                        <option value="Placement">Placement</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Event Date</label>
                    <input type="date" name="event_date" id="edit_event_date" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Location</label>
                    <input type="text" name="location" id="edit_event_loc" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Replace Poster (Optional, Max 2MB)</label>
                    <input type="file" name="poster" class="form-control" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" id="edit_event_status" class="form-select">
                        <option value="Upcoming">Upcoming</option>
                        <option value="Active">Active</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Description</label>
                    <textarea name="description" id="edit_event_desc" class="form-control" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Event</button>
            </div>
        </form>
    </div>
</div>

<footer class="bg-light text-center text-muted py-3 mt-5">
    © 2026 StudentHub Portal | ITUE203 Practical 12 Event CRUD
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('.edit-event-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('edit_event_id').value = this.dataset.id;
            document.getElementById('edit_event_title').value = this.dataset.title;
            document.getElementById('edit_event_desc').value = this.dataset.desc;
            document.getElementById('edit_event_cat').value = this.dataset.cat;
            document.getElementById('edit_event_date').value = this.dataset.date;
            document.getElementById('edit_event_loc').value = this.dataset.loc;
            document.getElementById('edit_event_status').value = this.dataset.status;
            new bootstrap.Modal(document.getElementById('editEventModal')).show();
        });
    });
</script>
</body>
</html>
