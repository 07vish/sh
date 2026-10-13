# StudentHub Portal

A unified, full-stack campus web portal developed as part of the **Web Development Frameworks (ITUE203)** course at **Charotar University of Science and Technology (CHARUSAT)**.

StudentHub provides students and administrators with centralized tools to manage notices, events, attendance, assignments, timetable schedules, and user registrations with role-based access control and RESTful APIs.

---

## Student Information

- **Name:** Vishnu Jariwala
- **Student ID:** D26IT113
- **Course:** B.Tech Information Technology (Semester 3)
- **Subject:** Web Development Frameworks (ITUE203)
- **Repository:** [https://github.com/07vish/sh.git](https://github.com/07vish/sh.git)

---

## Practicals Overview (Practical 1 to 14)

| Practical | Title | Core Technologies |
| :---: | :--- | :--- |
| **PR 1** | Project Initiation, Sitemap, Wireframe & GitHub Setup | Git, Wireframes, Project Structuring |
| **PR 2** | Semantic HTML5 Pages with Accessibility-Ready Structure | Semantic HTML5, ARIA, Accessibility |
| **PR 3** | Responsive UI Design using CSS Grid, Flexbox & Bootstrap | CSS3, Flexbox, CSS Grid, Media Queries |
| **PR 4** | JavaScript DOM Manipulation & UI Interactivity | Vanilla JavaScript, DOM, Events, Theme Toggle |
| **PR 5** | Registration Form with Frontend Validation | Regular Expressions, Client-side Validation |
| **PR 6** | Rendering External JSON Data using Fetch API, Search & Filter | Fetch API, Async/Await, Array Filter/Sort |
| **PR 7** | PHP Form Processing with Server-Side Validation & File Storage | PHP POST, Data Sanitization, CSV & JSON Storage |
| **PR 8** | MySQL Schema Design, ER Model & Database Connectivity | MySQL, 3NF Schema, PDO & MySQLi Connections |
| **PR 9** | Secure User Registration with Duplicate Checks & Password Hashing | `password_hash()`, Prepared Statements, Validation |
| **PR 10** | Secure Login/Logout with Sessions, RBAC & Inactivity Timeout | PHP Sessions, `password_verify()`, Timeout |
| **PR 11** | Student Management CRUD with Search & Filter | MySQLi Prepared Statements, CRUD, CSV Export |
| **PR 12** | Event Management CRUD with Poster Upload & File Validation | Multipart Uploads, MIME Inspection, File Validation |
| **PR 13** | PHP JSON API & AJAX/Fetch-Based CRUD without Page Reload | RESTful JSON API, Fetch API, DOM Manipulation |
| **PR 14** | Admin Dashboard with Role-Based Access Control & Audit Log | RBAC Dashboard, SQL Aggregations, Audit Trails |

---

## Technology Stack

- **Frontend:** HTML5 (Semantic elements), CSS3, Bootstrap 5.3, JavaScript (ES6+), Fetch API
- **Backend:** PHP 8.2+
- **Database:** MySQL / MariaDB (with portable SQLite auto-fallback)
- **Version Control:** Git & GitHub

---

## How to Run Locally

### Option 1: Using PHP Built-In Server (Fastest)

1. Open PowerShell or Terminal in the project directory:
   ```bash
   cd sh
   ```
2. Start the built-in development server:
   ```bash
   php -S localhost:8000
   ```
3. Open your browser and navigate to:
   - Homepage: `http://localhost:8000/index.html`
   - Notice Board (Fetch API): `http://localhost:8000/notice.html`
   - Events (Fetch API): `http://localhost:8000/events.html`
   - Student Login: `http://localhost:8000/login.php`
   - Admin Dashboard: `http://localhost:8000/admin_dashboard.php`
   - AJAX Event CRUD: `http://localhost:8000/events_ajax.php`

### Option 2: Using XAMPP / WAMP

1. Copy the `sh` directory into your `C:\xampp\htdocs\` folder.
2. Start Apache and MySQL from XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin/` and import `database/schema.sql` followed by `database/seed.sql`.
4. Access the portal at `http://localhost/sh/`.

---

## Demo Credentials for Evaluation

- **Administrator:**
  - Username: `admin`
  - Password: `Admin@123`
- **Student User:**
  - Username: `vishnu`
  - Password: `Student@123`

---

## Project Structure

```text
sh/
├── assets/                 # Static media and images
├── config/
│   └── db.php             # Unified PDO/MySQLi connection handler
├── css/
│   └── style.css          # Custom styling and dark-theme variables
├── data/                  # External datasets (JSON & CSV storage)
│   ├── events.json        # 16 sample events dataset
│   ├── faqs.json          # 16 portal FAQs dataset
│   ├── notices.json       # 16 campus notices dataset
│   └── students.json      # 16 student profiles dataset
├── database/
│   ├── schema.sql         # MySQL 3NF database schema
│   └── seed.sql           # 20+ student records and test data
├── includes/
│   ├── auth.php           # Session management, RBAC, and timeout guard
│   └── unauthorized.php   # 403 Forbidden page
├── js/
│   └── script.js          # DOM manipulation, theme toggle, fetch rendering
├── api/
│   └── events_api.php     # RESTful JSON API endpoint for AJAX operations
├── admin_dashboard.php    # Practical 14: Admin control panel with audit logs
├── events_ajax.php        # Practical 13: Asynchronous Fetch CRUD page
├── events_manage.php      # Practical 12: Event CRUD with poster upload
├── students_manage.php    # Practical 11: Student CRUD with search & filter
├── login.php              # Practical 10: Secure login with sessions
├── logout.php             # Practical 10: Session cleanup & logout
├── register.php           # Practical 9: Secure registration & hashing
├── test_db.php            # Practical 8: Database connectivity tester
├── contact_process.php    # Practical 7: PHP server-side form processor
├── view_submissions.php   # Practical 7: CSV & JSON viewer
├── events.html            # Practical 6: Dynamic events directory
├── notice.html            # Practical 6: Dynamic notice board
└── index.html             # Homepage
```

---

## License

Developed strictly for academic purposes under FTE CHARUSAT Web Development Frameworks course curriculum.
