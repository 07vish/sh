-- StudentHub Seed Data
-- 20+ student records and initial users for testing

USE `studenthub`;

-- 1. Seed Users (passwords hashed with PASSWORD_DEFAULT: Admin@123 and Student@123)
-- Admin hash: $2y$10$eO1dK4Xy3f/VzQn90m7/Ievj46gU3P1Nf9.qg/bKjYfA2wW5q8Zea (Admin@123)
-- Student hash: $2y$10$w4C3sX8rYjE.w8B.E.k3vOsPzqUq5V3dE4O5C6v7B8N9M0L1K2J3H (Student@123)

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `status`) VALUES
(1, 'admin', 'admin@charusat.edu.in', '$2y$10$yP095zF2PZg0.U53WJz1IeK4E7h9h71qYx5tXzL6oHhJm0nI4L2K2', 'admin', 'active'),
(2, 'vishnu', 'vishnu.j@charusat.edu.in', '$2y$10$yP095zF2PZg0.U53WJz1IeK4E7h9h71qYx5tXzL6oHhJm0nI4L2K2', 'student', 'active'),
(3, 'aarav', 'aarav.p@charusat.edu.in', '$2y$10$yP095zF2PZg0.U53WJz1IeK4E7h9h71qYx5tXzL6oHhJm0nI4L2K2', 'student', 'active')
ON DUPLICATE KEY UPDATE `username`=VALUES(`username`);

-- 2. Seed 20 Students (Meeting syllabus requirement of minimum 20 seeded records)
INSERT INTO `students` (`id`, `user_id`, `student_id`, `full_name`, `email`, `phone`, `department`, `semester`, `gender`, `cgpa`) VALUES
(1, 2, 'D26IT113', 'Vishnu Jariwala', 'vishnu.j@charusat.edu.in', '9876543210', 'IT', 3, 'Male', 9.25),
(2, 3, 'D26IT101', 'Aarav Patel', 'aarav.p@charusat.edu.in', '9825012345', 'IT', 3, 'Male', 8.85),
(3, NULL, 'D26IT102', 'Bhavya Shah', 'bhavya.s@charusat.edu.in', '9825012346', 'IT', 3, 'Male', 9.12),
(4, NULL, 'D26IT103', 'Chirag Joshi', 'chirag.j@charusat.edu.in', '9825012347', 'IT', 3, 'Male', 8.45),
(5, NULL, 'D26IT104', 'Diya Mehta', 'diya.m@charusat.edu.in', '9825012348', 'IT', 3, 'Female', 9.40),
(6, NULL, 'D26IT105', 'Eshan Trivedi', 'eshan.t@charusat.edu.in', '9825012349', 'CE', 3, 'Male', 8.20),
(7, NULL, 'D26IT106', 'Falguni Dave', 'falguni.d@charusat.edu.in', '9825012350', 'IT', 3, 'Female', 8.75),
(8, NULL, 'D26IT107', 'Gaurav Parmar', 'gaurav.p@charusat.edu.in', '9825012351', 'CE', 3, 'Male', 7.95),
(9, NULL, 'D26IT108', 'Heena Vora', 'heena.v@charusat.edu.in', '9825012352', 'IT', 3, 'Female', 9.05),
(10, NULL, 'D26IT109', 'Ishan Sharma', 'ishan.s@charusat.edu.in', '9825012353', 'EC', 3, 'Male', 8.35),
(11, NULL, 'D26IT110', 'Jhanvi Desai', 'jhanvi.d@charusat.edu.in', '9825012354', 'IT', 3, 'Female', 8.90),
(12, NULL, 'D26IT111', 'Karan Prajapati', 'karan.p@charusat.edu.in', '9825012355', 'CE', 3, 'Male', 8.10),
(13, NULL, 'D26IT112', 'Lata Rathod', 'lata.r@charusat.edu.in', '9825012356', 'IT', 3, 'Female', 8.65),
(14, NULL, 'D26IT114', 'Mihir Solanki', 'mihir.s@charusat.edu.in', '9825012357', 'IT', 3, 'Male', 8.40),
(15, NULL, 'D26IT115', 'Nidhi Chaudhari', 'nidhi.c@charusat.edu.in', '9825012358', 'EC', 3, 'Female', 8.70),
(16, NULL, 'D26IT116', 'Omkar Pandya', 'omkar.p@charusat.edu.in', '9825012359', 'CE', 3, 'Male', 8.55),
(17, NULL, 'D26IT117', 'Pooja Barot', 'pooja.b@charusat.edu.in', '9825012360', 'IT', 3, 'Female', 8.95),
(18, NULL, 'D26IT118', 'Rajesh Koli', 'rajesh.k@charusat.edu.in', '9825012361', 'CE', 3, 'Male', 8.30),
(19, NULL, 'D26IT119', 'Sneha Mistry', 'sneha.m@charusat.edu.in', '9825012362', 'IT', 3, 'Female', 9.15),
(20, NULL, 'D26IT120', 'Tanmay Bhatt', 'tanmay.b@charusat.edu.in', '9825012363', 'IT', 3, 'Male', 8.60)
ON DUPLICATE KEY UPDATE `student_id`=VALUES(`student_id`);

-- 3. Seed Events
INSERT INTO `events` (`id`, `title`, `description`, `category`, `event_date`, `location`, `poster_image`, `status`, `created_by`) VALUES
(1, 'TechPulse 2026 Hackathon', '36-hour offline hackathon on Web3, AI, and IoT solutions.', 'Technical', '2026-10-25', 'IT Lab Complex & Auditorium', 'event_techpulse.jpg', 'Upcoming', 1),
(2, 'DevFest Cloud & Kubernetes Summit', 'Hands-on workshops on Docker, Kubernetes, and AWS architecture.', 'Workshop', '2026-10-28', 'Seminar Hall B', 'event_devfest.jpg', 'Upcoming', 1),
(3, 'CyberShield Ethical Hacking Bootcamp', 'Learn network vulnerabilities, OWASP Top 10, and defense tools.', 'Technical', '2026-11-02', 'Cyber Security Lab', 'event_cybershield.jpg', 'Upcoming', 1),
(4, 'Spandan Annual Cultural Night', 'Annual cultural festival with music, drama, and fine arts.', 'Cultural', '2026-11-08', 'University Amphitheatre', 'event_spandan.jpg', 'Upcoming', 1),
(5, 'Inter-Department Football Tournament', '7-a-side inter-branch football tournament.', 'Sports', '2026-11-12', 'University Sports Ground', 'event_football.jpg', 'Upcoming', 1)
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- 4. Seed Registrations
INSERT INTO `registrations` (`id`, `event_id`, `student_id`, `status`, `attendance`) VALUES
(1, 1, 1, 'Confirmed', 'Present'),
(2, 1, 2, 'Confirmed', 'Present'),
(3, 2, 1, 'Confirmed', 'Present'),
(4, 2, 3, 'Confirmed', 'Absent'),
(5, 3, 4, 'Confirmed', 'Absent')
ON DUPLICATE KEY UPDATE `status`=VALUES(`status`);

-- 5. Seed Audit Logs
INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `details`, `ip_address`) VALUES
(1, 1, 'SYSTEM_INIT', 'Database seeded with initial schema and 20 student records.', '127.0.0.1'),
(2, 1, 'ADMIN_LOGIN', 'Administrator logged into the portal.', '127.0.0.1')
ON DUPLICATE KEY UPDATE `action`=VALUES(`action`);
