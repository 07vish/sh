<?php
declare(strict_types=1);

/**
 * Database Configuration & Connection Handler
 * StudentHub Portal - ITUE203 Practical 8
 * Supports both PDO and MySQLi connections with prepared statements.
 */

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: 3306);
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'studenthub');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a PDO database connection instance.
 * Automatically falls back to local SQLite if MySQL is offline.
 */
function get_pdo_connection(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 2,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Fallback to local SQLite file for standalone testing/dev
        $sqliteDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database';
        if (!is_dir($sqliteDir)) {
            mkdir($sqliteDir, 0755, true);
        }
        $sqlitePath = $sqliteDir . DIRECTORY_SEPARATOR . 'studenthub.sqlite';
        $isNew = !file_exists($sqlitePath) || filesize($sqlitePath) === 0;

        $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        if ($isNew) {
            initialize_sqlite_schema($pdo);
        }
        return $pdo;
    }
}

/**
 * Returns a MySQLi connection instance (or null/exception if MySQL offline).
 */
function get_mysqli_connection(): ?mysqli
{
    static $mysqli = null;
    if ($mysqli !== null) {
        return $mysqli;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
        $mysqli->set_charset(DB_CHARSET);
        return $mysqli;
    } catch (mysqli_sql_exception $e) {
        // Return null if MySQL server is not running
        return null;
    }
}

/**
 * Helper to initialize SQLite schema and seed records when running standalone
 */
function initialize_sqlite_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT DEFAULT 'student',
            status TEXT DEFAULT 'active',
            remember_token TEXT DEFAULT NULL,
            last_login TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER DEFAULT NULL,
            student_id TEXT NOT NULL UNIQUE,
            full_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            phone TEXT DEFAULT NULL,
            department TEXT NOT NULL DEFAULT 'IT',
            semester INTEGER NOT NULL DEFAULT 3,
            gender TEXT DEFAULT 'Male',
            cgpa REAL DEFAULT 8.00,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            category TEXT NOT NULL DEFAULT 'Technical',
            event_date TEXT NOT NULL,
            location TEXT NOT NULL,
            poster_image TEXT DEFAULT 'default_event.jpg',
            status TEXT DEFAULT 'Upcoming',
            created_by INTEGER DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS registrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id INTEGER NOT NULL,
            student_id INTEGER NOT NULL,
            registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            status TEXT DEFAULT 'Confirmed',
            attendance TEXT DEFAULT 'Absent'
        );

        CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER DEFAULT NULL,
            action TEXT NOT NULL,
            details TEXT DEFAULT NULL,
            ip_address TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Seed default admin and user
    $hash = password_hash('Admin@123', PASSWORD_DEFAULT);
    $stdHash = password_hash('Student@123', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (id, username, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([1, 'admin', 'admin@charusat.edu.in', $hash, 'admin']);
    $stmt->execute([2, 'vishnu', 'vishnu.j@charusat.edu.in', $stdHash, 'student']);

    // Seed 20 students
    $students = [
        ['D26IT113', 'Vishnu Jariwala', 'vishnu.j@charusat.edu.in', '9876543210', 'IT', 3, 'Male', 9.25],
        ['D26IT101', 'Aarav Patel', 'aarav.p@charusat.edu.in', '9825012345', 'IT', 3, 'Male', 8.85],
        ['D26IT102', 'Bhavya Shah', 'bhavya.s@charusat.edu.in', '9825012346', 'IT', 3, 'Male', 9.12],
        ['D26IT103', 'Chirag Joshi', 'chirag.j@charusat.edu.in', '9825012347', 'IT', 3, 'Male', 8.45],
        ['D26IT104', 'Diya Mehta', 'diya.m@charusat.edu.in', '9825012348', 'IT', 3, 'Female', 9.40],
        ['D26IT105', 'Eshan Trivedi', 'eshan.t@charusat.edu.in', '9825012349', 'CE', 3, 'Male', 8.20],
        ['D26IT106', 'Falguni Dave', 'falguni.d@charusat.edu.in', '9825012350', 'IT', 3, 'Female', 8.75],
        ['D26IT107', 'Gaurav Parmar', 'gaurav.p@charusat.edu.in', '9825012351', 'CE', 3, 'Male', 7.95],
        ['D26IT108', 'Heena Vora', 'heena.v@charusat.edu.in', '9825012352', 'IT', 3, 'Female', 9.05],
        ['D26IT109', 'Ishan Sharma', 'ishan.s@charusat.edu.in', '9825012353', 'EC', 3, 'Male', 8.35],
        ['D26IT110', 'Jhanvi Desai', 'jhanvi.d@charusat.edu.in', '9825012354', 'IT', 3, 'Female', 8.90],
        ['D26IT111', 'Karan Prajapati', 'karan.p@charusat.edu.in', '9825012355', 'CE', 3, 'Male', 8.10],
        ['D26IT112', 'Lata Rathod', 'lata.r@charusat.edu.in', '9825012356', 'IT', 3, 'Female', 8.65],
        ['D26IT114', 'Mihir Solanki', 'mihir.s@charusat.edu.in', '9825012357', 'IT', 3, 'Male', 8.40],
        ['D26IT115', 'Nidhi Chaudhari', 'nidhi.c@charusat.edu.in', '9825012358', 'EC', 3, 'Female', 8.70],
        ['D26IT116', 'Omkar Pandya', 'omkar.p@charusat.edu.in', '9825012359', 'CE', 3, 'Male', 8.55],
        ['D26IT117', 'Pooja Barot', 'pooja.b@charusat.edu.in', '9825012360', 'IT', 3, 'Female', 8.95],
        ['D26IT118', 'Rajesh Koli', 'rajesh.k@charusat.edu.in', '9825012361', 'CE', 3, 'Male', 8.30],
        ['D26IT119', 'Sneha Mistry', 'sneha.m@charusat.edu.in', '9825012362', 'IT', 3, 'Female', 9.15],
        ['D26IT120', 'Tanmay Bhatt', 'tanmay.b@charusat.edu.in', '9825012363', 'IT', 3, 'Male', 8.60],
    ];

    $insStmt = $pdo->prepare("INSERT OR IGNORE INTO students (student_id, full_name, email, phone, department, semester, gender, cgpa) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($students as $s) {
        $insStmt->execute($s);
    }

    // Seed events
    $events = [
        ['TechPulse 2026 Hackathon', '36-hour offline hackathon on Web3, AI, and IoT solutions.', 'Technical', '2026-10-25', 'IT Lab Complex & Auditorium', 'event_techpulse.jpg', 'Upcoming'],
        ['DevFest Cloud & Kubernetes Summit', 'Hands-on workshops on Docker, Kubernetes, and AWS architecture.', 'Workshop', '2026-10-28', 'Seminar Hall B', 'event_devfest.jpg', 'Upcoming'],
        ['CyberShield Ethical Hacking Bootcamp', 'Learn network vulnerabilities, OWASP Top 10, and defense tools.', 'Technical', '2026-11-02', 'Cyber Security Lab', 'event_cybershield.jpg', 'Upcoming'],
        ['Spandan Annual Cultural Night', 'Annual cultural festival with music, drama, and fine arts.', 'Cultural', '2026-11-08', 'University Amphitheatre', 'event_spandan.jpg', 'Upcoming'],
        ['Inter-Department Football Tournament', '7-a-side inter-branch football tournament.', 'Sports', '2026-11-12', 'University Sports Ground', 'event_football.jpg', 'Upcoming'],
    ];

    $evStmt = $pdo->prepare("INSERT OR IGNORE INTO events (title, description, category, event_date, location, poster_image, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($events as $ev) {
        $evStmt->execute($ev);
    }

    // Seed initial audit log
    $pdo->exec("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (1, 'SYSTEM_INIT', 'Database schema created and seeded.', '127.0.0.1')");
}
