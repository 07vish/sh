<?php
declare(strict_types=1);

/**
 * RESTful JSON API for Events
 * StudentHub Portal - ITUE203 Practical 13
 * Provides asynchronous CRUD endpoints consumed via Fetch API without page reload.
 */

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/db.php';

function json_respond(int $statusCode, bool $success, string $message, $data = null, array $errors = []): never
{
    http_response_code($statusCode);
    $response = [
        'success'   => $success,
        'message'   => $message,
        'timestamp' => date('c'),
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

try {
    $pdo = get_pdo_connection();
} catch (Exception $e) {
    json_respond(500, false, 'Database connection failed: ' . $e->getMessage());
}

$method = $_SERVER['REQUEST_METHOD'];

// Parse JSON input if sent as application/json
$rawInput = file_get_contents('php://input');
$jsonBody = [];
if ($rawInput !== false && $rawInput !== '') {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $jsonBody = $decoded;
    }
}

// Support method overriding via parameter or header
if ($method === 'POST') {
    $override = $_POST['_method'] ?? $jsonBody['_method'] ?? '';
    if (strtoupper($override) === 'PUT') {
        $method = 'PUT';
    } elseif (strtoupper($override) === 'DELETE') {
        $method = 'DELETE';
    }
}

switch ($method) {
    // -------------------------------------------------------------------------
    // GET: List all events or get a specific event by ID
    // -------------------------------------------------------------------------
    case 'GET':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $event = $stmt->fetch();
            if (!$event) {
                json_respond(404, false, "Event with ID {$id} not found.");
            }
            json_respond(200, true, 'Event retrieved successfully.', $event);
        }

        $search = trim((string)($_GET['search'] ?? ''));
        $category = trim((string)($_GET['category'] ?? 'all'));

        $sql = 'SELECT * FROM events WHERE 1=1';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (title LIKE ? OR description LIKE ? OR location LIKE ?)';
            $wildcard = "%{$search}%";
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
        }

        if ($category !== 'all' && $category !== '') {
            $sql .= ' AND category = ?';
            $params[] = $category;
        }

        $sql .= ' ORDER BY event_date ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();

        json_respond(200, true, 'Events list fetched successfully.', $events);
        break;

    // -------------------------------------------------------------------------
    // POST: Create a new event
    // -------------------------------------------------------------------------
    case 'POST':
        $data = !empty($jsonBody) ? $jsonBody : $_POST;

        $title       = trim((string)($data['title'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $category    = trim((string)($data['category'] ?? 'Technical'));
        $eventDate   = trim((string)($data['event_date'] ?? ''));
        $location    = trim((string)($data['location'] ?? ''));
        $status      = trim((string)($data['status'] ?? 'Upcoming'));

        $errors = [];
        if (strlen($title) < 3) $errors[] = 'Title must be at least 3 characters.';
        if (strlen($description) < 5) $errors[] = 'Description must be at least 5 characters.';
        if (empty($eventDate)) $errors[] = 'Event date is required.';
        if (empty($location)) $errors[] = 'Event location is required.';

        if (!empty($errors)) {
            json_respond(400, false, 'Validation failed for new event.', null, $errors);
        }

        try {
            $stmt = $pdo->prepare('INSERT INTO events (title, description, category, event_date, location, poster_image, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$title, $description, $category, $eventDate, $location, 'default_event.jpg', $status]);
            $newId = (int)$pdo->lastInsertId();

            $fetchStmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
            $fetchStmt->execute([$newId]);
            $createdEvent = $fetchStmt->fetch();

            json_respond(201, true, 'New event published successfully via API.', $createdEvent);
        } catch (Exception $e) {
            json_respond(500, false, 'Database error while saving event: ' . $e->getMessage());
        }
        break;

    // -------------------------------------------------------------------------
    // PUT: Update an existing event
    // -------------------------------------------------------------------------
    case 'PUT':
        $data = !empty($jsonBody) ? $jsonBody : $_POST;
        $id = (int)($data['id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            json_respond(400, false, 'Valid event ID is required for update.');
        }

        $title       = trim((string)($data['title'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $category    = trim((string)($data['category'] ?? 'Technical'));
        $eventDate   = trim((string)($data['event_date'] ?? ''));
        $location    = trim((string)($data['location'] ?? ''));
        $status      = trim((string)($data['status'] ?? 'Upcoming'));

        $errors = [];
        if (strlen($title) < 3) $errors[] = 'Title must be at least 3 characters.';
        if (strlen($description) < 5) $errors[] = 'Description must be at least 5 characters.';
        if (empty($eventDate)) $errors[] = 'Event date is required.';
        if (empty($location)) $errors[] = 'Event location is required.';

        if (!empty($errors)) {
            json_respond(400, false, 'Validation failed for event update.', null, $errors);
        }

        try {
            $upd = $pdo->prepare('UPDATE events SET title = ?, description = ?, category = ?, event_date = ?, location = ?, status = ? WHERE id = ?');
            $upd->execute([$title, $description, $category, $eventDate, $location, $status, $id]);

            $fetchStmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
            $fetchStmt->execute([$id]);
            $updatedEvent = $fetchStmt->fetch();

            if (!$updatedEvent) {
                json_respond(404, false, "Event ID {$id} not found.");
            }

            json_respond(200, true, "Event '{$title}' updated successfully via API.", $updatedEvent);
        } catch (Exception $e) {
            json_respond(500, false, 'Database error while updating event: ' . $e->getMessage());
        }
        break;

    // -------------------------------------------------------------------------
    // DELETE: Remove an event
    // -------------------------------------------------------------------------
    case 'DELETE':
        $data = !empty($jsonBody) ? $jsonBody : $_POST;
        $id = (int)($data['id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            json_respond(400, false, 'Valid event ID is required for deletion.');
        }

        try {
            $check = $pdo->prepare('SELECT * FROM events WHERE id = ?');
            $check->execute([$id]);
            $target = $check->fetch();

            if (!$target) {
                json_respond(404, false, "Event ID {$id} not found.");
            }

            $del = $pdo->prepare('DELETE FROM events WHERE id = ?');
            $del->execute([$id]);

            json_respond(200, true, "Event '{$target['title']}' deleted successfully without page reload.", ['id' => $id]);
        } catch (Exception $e) {
            json_respond(500, false, 'Database error while deleting event: ' . $e->getMessage());
        }
        break;

    default:
        json_respond(405, false, "HTTP Method {$method} not allowed.");
}
