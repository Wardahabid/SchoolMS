# School Management System — Project Blueprint
> **Stack:** PHP 8+ · MySQL 8+ · Vanilla JS · No frameworks required  
> **UI Theme:** Minimal, clean, responsive — white/light-grey base, slate accent, single bold color for CTAs  
> **Purpose:** Academic project fulfilling requirements for a school management system

---

## Table of Contents
1. [Tech Stack & Requirements](#tech-stack--requirements)
2. [MySQL DDL — Full Schema](#mysql-ddl--full-schema)
3. [Directory Structure](#directory-structure)
4. [Modules & Pages](#modules--pages)
5. [UI/UX Guidelines](#uiux-guidelines)
6. [Global Files Detail](#global-files-detail)
7. [Module Implementation Details](#module-implementation-details)
8. [Authentication & Session Flow](#authentication--session-flow)
9. [Build Checklist](#build-checklist)

---

## Tech Stack & Requirements

| Layer | Choice |
|---|---|
| Backend | PHP 8.1+ (no framework) |
| Database | MySQL 8.0+ |
| Frontend | HTML5 + CSS3 + Vanilla JS (ES6) |
| CSS | Custom CSS (no Tailwind / Bootstrap) |
| Icons | Lucide Icons via CDN |
| Fonts | Google Fonts — `Inter` (body) + `DM Sans` (headings) |
| Server | Apache / Nginx with mod_rewrite or XAMPP locally |

---

## MySQL DDL — Full Schema

Run these statements **in order** in your MySQL client.

```sql
-- ============================================================
-- DATABASE
-- ============================================================
CREATE DATABASE IF NOT EXISTS school_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE school_db;

-- ============================================================
-- 1. ROLE
-- ============================================================
CREATE TABLE Role (
    role_id   INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
);

-- ============================================================
-- 2. USER
-- ============================================================
CREATE TABLE User (
    user_id           INT AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(100) NOT NULL,
    email             VARCHAR(150) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    password          VARCHAR(255) NOT NULL,
    remember_token    VARCHAR(100) NULL,
    role_id           INT NOT NULL,
    CONSTRAINT fk_user_role FOREIGN KEY (role_id) REFERENCES Role(role_id)
);

-- ============================================================
-- 3. CLASS
-- ============================================================
CREATE TABLE Class (
    class_id   INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL
);

-- ============================================================
-- 4. SECTION
-- ============================================================
CREATE TABLE Section (
    section_id   INT AUTO_INCREMENT PRIMARY KEY,
    class_id     INT NOT NULL,
    section_name VARCHAR(50) NOT NULL,
    CONSTRAINT fk_section_class FOREIGN KEY (class_id) REFERENCES Class(class_id)
);

-- ============================================================
-- 5. STUDENT
-- ============================================================
CREATE TABLE Student (
    student_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL UNIQUE,
    class_id       INT NOT NULL,
    parent_id      INT NULL,           -- nullable; filled after Parent insert
    admission_date DATE NOT NULL,
    CONSTRAINT fk_student_user  FOREIGN KEY (user_id)  REFERENCES User(user_id),
    CONSTRAINT fk_student_class FOREIGN KEY (class_id) REFERENCES Class(class_id)
);

-- ============================================================
-- 6. PARENT
-- ============================================================
CREATE TABLE Parent (
    parent_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id   INT NOT NULL UNIQUE,
    phone     VARCHAR(20) NOT NULL,
    CONSTRAINT fk_parent_user FOREIGN KEY (user_id) REFERENCES User(user_id)
);

-- Add FK from Student → Parent now that Parent table exists
ALTER TABLE Student
    ADD CONSTRAINT fk_student_parent
    FOREIGN KEY (parent_id) REFERENCES Parent(parent_id);

-- ============================================================
-- 7. TEACHER
-- ============================================================
CREATE TABLE Teacher (
    teacher_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL UNIQUE,
    specialization  VARCHAR(100) NOT NULL,
    CONSTRAINT fk_teacher_user FOREIGN KEY (user_id) REFERENCES User(user_id)
);

-- ============================================================
-- 8. STAFF
-- ============================================================
CREATE TABLE Staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id  INT NOT NULL UNIQUE,
    position VARCHAR(100) NOT NULL,
    CONSTRAINT fk_staff_user FOREIGN KEY (user_id) REFERENCES User(user_id)
);

-- ============================================================
-- 9. SUBJECT
-- ============================================================
CREATE TABLE Subject (
    subject_id   INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL
);

-- ============================================================
-- 10. CLASS ASSIGNMENT (Teacher → Class+Section+Subject)
-- ============================================================
CREATE TABLE ClassAssignment (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id    INT NOT NULL,
    subject_id    INT NOT NULL,
    class_id      INT NOT NULL,
    section_id    INT NOT NULL,
    CONSTRAINT fk_ca_teacher  FOREIGN KEY (teacher_id)  REFERENCES Teacher(teacher_id),
    CONSTRAINT fk_ca_subject  FOREIGN KEY (subject_id)  REFERENCES Subject(subject_id),
    CONSTRAINT fk_ca_class    FOREIGN KEY (class_id)    REFERENCES Class(class_id),
    CONSTRAINT fk_ca_section  FOREIGN KEY (section_id)  REFERENCES Section(section_id)
);

-- ============================================================
-- 11. TIMETABLE
-- ============================================================
CREATE TABLE Timetable (
    assignment_id INT NOT NULL,       -- reuses ClassAssignment PK as PK here (1-to-1)
    teacher_id    INT NOT NULL,
    section_id    INT NOT NULL,
    time_slot     VARCHAR(50) NOT NULL,
    PRIMARY KEY (assignment_id),
    CONSTRAINT fk_tt_assignment FOREIGN KEY (assignment_id) REFERENCES ClassAssignment(assignment_id),
    CONSTRAINT fk_tt_teacher    FOREIGN KEY (teacher_id)    REFERENCES Teacher(teacher_id),
    CONSTRAINT fk_tt_section    FOREIGN KEY (section_id)    REFERENCES Section(section_id)
);

-- ============================================================
-- 12. ATTENDANCE SESSION
-- ============================================================
CREATE TABLE AttendanceSession (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    exam_name  VARCHAR(100) NOT NULL,
    date       DATE NOT NULL,
    CONSTRAINT fk_as_section FOREIGN KEY (section_id) REFERENCES Section(section_id)
);

-- ============================================================
-- 13. ATTENDANCE RECORD
-- ============================================================
CREATE TABLE AttendanceRecord (
    record_id  INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    status     ENUM('Present','Absent','Late') NOT NULL DEFAULT 'Present',
    CONSTRAINT fk_ar_session FOREIGN KEY (session_id) REFERENCES AttendanceSession(session_id),
    CONSTRAINT fk_ar_student FOREIGN KEY (student_id) REFERENCES Student(student_id)
);

-- ============================================================
-- 14. EXAM
-- ============================================================
CREATE TABLE Exam (
    exam_id   INT AUTO_INCREMENT PRIMARY KEY,
    exam_name VARCHAR(100) NOT NULL,
    date      DATE NOT NULL
);

-- ============================================================
-- 15. SUBJECT EXAM
-- ============================================================
CREATE TABLE SubjectExam (
    subject_exam_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id         INT NOT NULL,
    subject_id      INT NOT NULL,
    max_marks       DECIMAL(5,2) NOT NULL,
    CONSTRAINT fk_se_exam    FOREIGN KEY (exam_id)    REFERENCES Exam(exam_id),
    CONSTRAINT fk_se_subject FOREIGN KEY (subject_id) REFERENCES Subject(subject_id)
);

-- ============================================================
-- 16. MARKS
-- ============================================================
CREATE TABLE Marks (
    marks_id        INT AUTO_INCREMENT PRIMARY KEY,
    student_id      INT NOT NULL,
    subject_exam_id INT NOT NULL,
    obtained_marks  DECIMAL(5,2) NOT NULL,
    CONSTRAINT fk_marks_student  FOREIGN KEY (student_id)      REFERENCES Student(student_id),
    CONSTRAINT fk_marks_se       FOREIGN KEY (subject_exam_id) REFERENCES SubjectExam(subject_exam_id)
);

-- ============================================================
-- 17. REPORT
-- ============================================================
CREATE TABLE Report (
    report_id    INT AUTO_INCREMENT PRIMARY KEY,
    student_id   INT NOT NULL,
    exam_id      INT NOT NULL,
    total_marks  DECIMAL(6,2) NOT NULL,
    grade        VARCHAR(5) NOT NULL,
    remarks      TEXT NULL,
    CONSTRAINT fk_report_student FOREIGN KEY (student_id) REFERENCES Student(student_id),
    CONSTRAINT fk_report_exam    FOREIGN KEY (exam_id)    REFERENCES Exam(exam_id)
);

-- ============================================================
-- 18. FEE
-- ============================================================
CREATE TABLE Fee (
    fee_id     INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount     DECIMAL(10,2) NOT NULL,
    due_date   DATE NOT NULL,
    CONSTRAINT fk_fee_student FOREIGN KEY (student_id) REFERENCES Student(student_id)
);

-- ============================================================
-- 19. FEE PAYMENT
-- ============================================================
CREATE TABLE FeePayment (
    payment_id   INT AUTO_INCREMENT PRIMARY KEY,
    fee_id       INT NOT NULL,
    amount_paid  DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    CONSTRAINT fk_fp_fee FOREIGN KEY (fee_id) REFERENCES Fee(fee_id)
);

-- ============================================================
-- SEED DATA — Default Roles
-- ============================================================
INSERT INTO Role (role_name) VALUES
    ('Admin'),
    ('Teacher'),
    ('Student'),
    ('Parent'),
    ('Staff');

-- ============================================================
-- SEED DATA — Default Admin User
-- password: admin123  (bcrypt hash — change in production)
-- ============================================================
INSERT INTO User (name, email, password, role_id) VALUES
    ('Administrator', 'admin@school.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);
```

---

## Directory Structure

```
school_management/
│
├── index.php                   # Entry point — redirects to login or dashboard
├── .htaccess                   # URL rewrite rules (optional)
├── config/
│   └── db.php                  # PDO database connection
│
├── auth/
│   ├── login.php               # Login page (GET + POST)
│   ├── logout.php              # Destroys session, redirects to login
│   └── middleware.php          # Session check helper (include at top of every page)
│
├── assets/
│   ├── css/
│   │   ├── global.css          # CSS variables, reset, typography
│   │   ├── layout.css          # Sidebar, topbar, main content grid
│   │   └── components.css      # Cards, tables, forms, badges, buttons
│   └── js/
│       ├── sidebar.js          # Toggle sidebar on mobile
│       └── utils.js            # Fetch helpers, toast notifications
│
├── includes/
│   ├── header.php              # <head>, CSS links, font imports
│   ├── sidebar.php             # Left navigation based on role
│   ├── topbar.php              # Top bar with user name + logout
│   └── footer.php             # Closing tags, JS includes
│
├── dashboard/
│   └── index.php               # Role-aware dashboard with stat cards
│
├── users/
│   ├── index.php               # List all users (Admin only)
│   ├── create.php              # Add user form
│   ├── edit.php                # Edit user form (?id=)
│   └── delete.php              # Delete handler (POST)
│
├── students/
│   ├── index.php               # Student list with search/filter
│   ├── create.php              # Add student (creates User + Student row)
│   ├── edit.php                # Edit student
│   ├── view.php                # Student profile card
│   └── delete.php
│
├── teachers/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   ├── view.php
│   └── delete.php
│
├── parents/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   └── delete.php
│
├── staff/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   └── delete.php
│
├── classes/
│   ├── index.php               # Class + Sections list
│   ├── create.php
│   ├── edit.php
│   └── delete.php
│
├── sections/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   └── delete.php
│
├── subjects/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   └── delete.php
│
├── class_assignments/
│   ├── index.php               # Assign teacher to class/section/subject
│   ├── create.php
│   ├── edit.php
│   └── delete.php
│
├── timetable/
│   ├── index.php               # View timetable (filterable by section)
│   └── manage.php              # Add/edit time slots
│
├── attendance/
│   ├── index.php               # List attendance sessions
│   ├── take.php                # Take attendance (checkboxes per student)
│   ├── view.php                # View session records
│   └── report.php              # Attendance summary per student
│
├── exams/
│   ├── index.php               # Exam list
│   ├── create.php
│   ├── edit.php
│   ├── subjects.php            # Manage SubjectExam for an exam
│   └── delete.php
│
├── marks/
│   ├── index.php               # Enter/view marks per exam+subject
│   ├── enter.php               # Bulk marks entry form
│   └── view.php                # Student marks view
│
├── reports/
│   ├── index.php               # List generated reports
│   ├── generate.php            # Generate report for a student/exam
│   └── view.php                # Print-friendly report card
│
├── fees/
│   ├── index.php               # Fee records list
│   ├── create.php              # Assign fee to student
│   ├── pay.php                 # Record payment
│   └── view.php                # Fee status per student
│
└── README.md
```

---

## Modules & Pages

| Module | URL Path | Roles Allowed |
|---|---|---|
| Login | `/auth/login.php` | All |
| Dashboard | `/dashboard/index.php` | All (role-filtered content) |
| Users | `/users/` | Admin |
| Students | `/students/` | Admin, Teacher |
| Teachers | `/teachers/` | Admin |
| Parents | `/parents/` | Admin |
| Staff | `/staff/` | Admin |
| Classes | `/classes/` | Admin |
| Sections | `/sections/` | Admin |
| Subjects | `/subjects/` | Admin |
| Class Assignments | `/class_assignments/` | Admin |
| Timetable | `/timetable/` | Admin, Teacher, Student |
| Attendance | `/attendance/` | Teacher (take), Admin (view all) |
| Exams | `/exams/` | Admin |
| Marks | `/marks/` | Teacher (enter), Student (view own) |
| Reports | `/reports/` | Admin, Student, Parent |
| Fees | `/fees/` | Admin |

---

## UI/UX Guidelines

### Color Palette (CSS Variables)
```css
:root {
  --bg:         #F8F9FB;      /* page background */
  --surface:    #FFFFFF;      /* cards, sidebar */
  --border:     #E4E7EC;      /* dividers */
  --text-main:  #111827;      /* primary text */
  --text-muted: #6B7280;      /* labels, hints */
  --accent:     #2563EB;      /* buttons, links, active nav */
  --accent-lt:  #EFF6FF;      /* accent background tint */
  --danger:     #DC2626;
  --success:    #16A34A;
  --warning:    #D97706;
  --radius:     8px;
  --shadow:     0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.04);
}
```

### Typography
```css
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Inter:wght@400;500&display=swap');

body        { font-family: 'Inter', sans-serif; font-size: 14px; }
h1,h2,h3   { font-family: 'DM Sans', sans-serif; font-weight: 600; }
```

### Layout
- **Sidebar:** 240px fixed left, collapses to icon-only on mobile (toggle via hamburger)
- **Topbar:** 60px height, shadow-sm, shows page title + user avatar/name
- **Main:** `margin-left: 240px`, padding `24px`, max-width `1200px` on large screens
- **Cards:** `border-radius: var(--radius)`, `box-shadow: var(--shadow)`, `padding: 20px`

### Tables
- Striped rows (`nth-child(even)` → `#FAFAFA`)
- Sticky header
- Action buttons: small icon buttons (Edit = blue, Delete = red)
- Responsive: horizontal scroll on small screens

### Forms
- Full-width inputs with `border: 1px solid var(--border)`
- `:focus` → `border-color: var(--accent)` + `box-shadow: 0 0 0 3px var(--accent-lt)`
- Label above input, muted helper text below
- Submit = `--accent` filled button, Cancel = ghost button

### Buttons
```
.btn-primary  → background: var(--accent), color: white
.btn-ghost    → background: transparent, border: 1px solid var(--border)
.btn-danger   → background: var(--danger), color: white
.btn-sm       → padding: 4px 10px, font-size: 12px
```

### Status Badges
```
.badge-success  → background: #DCFCE7, color: #16A34A
.badge-danger   → background: #FEE2E2, color: #DC2626
.badge-warning  → background: #FEF3C7, color: #D97706
.badge-info     → background: #DBEAFE, color: #2563EB
```

---

## Global Files Detail

### `config/db.php`
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'school_db');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}
```

### `auth/middleware.php`
```php
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}
// Helper
function hasRole(string ...$roles): bool {
    return in_array($_SESSION['role_name'], $roles);
}
function requireRole(string ...$roles): void {
    if (!hasRole(...$roles)) {
        http_response_code(403);
        die('<h1>403 — Access Denied</h1>');
    }
}
```

### `auth/login.php` — Logic
```php
// POST handler (before HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $db = getDB();
    $stmt = $db->prepare("SELECT u.*, r.role_name FROM User u JOIN Role r ON u.role_id = r.role_id WHERE u.email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        session_start();
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['name']      = $user['name'];
        $_SESSION['role_name'] = $user['role_name'];
        header('Location: /dashboard/index.php');
        exit;
    }
    $error = 'Invalid email or password.';
}
```

### `includes/sidebar.php` — Nav Structure
```
- Dashboard             (all roles)
──── PEOPLE ────
- Students              (Admin, Teacher)
- Teachers              (Admin)
- Parents               (Admin)
- Staff                 (Admin)
──── ACADEMICS ────
- Classes & Sections    (Admin)
- Subjects              (Admin)
- Class Assignments     (Admin)
- Timetable             (Admin, Teacher, Student)
──── ASSESSMENT ────
- Attendance            (Admin, Teacher)
- Exams                 (Admin)
- Marks                 (Teacher, Admin, Student)
- Reports               (Admin, Student, Parent)
──── FINANCE ────
- Fees                  (Admin)
──── SETTINGS ────
- Users                 (Admin)
```

---

## Module Implementation Details

### Dashboard (`dashboard/index.php`)
Show stat cards based on role:
- **Admin:** Total Students, Teachers, Classes, Fees Due
- **Teacher:** My Classes, Today's Attendance Sessions, Upcoming Exams
- **Student:** Attendance %, Last Exam Marks, Fees Status
- **Parent:** Child's Attendance %, Last Report

Each stat card: icon (Lucide) + big number + label + small trend note.

### Students (`students/`)

**`index.php`**
- Paginated table (20 per page): Name, Class, Section, Admission Date, Actions
- Search by name, filter by class dropdown
- "Add Student" button top-right

**`create.php`** — Two-step form in one page:
1. User fields: name, email, password, (role auto = Student)
2. Student fields: class, section (dynamic dropdown via JS fetch), admission date, parent (optional)

**`view.php`** — Profile card showing:
- Personal info, class/section
- Recent attendance (last 5)
- Recent marks
- Fee status

### Attendance (`attendance/`)

**`take.php`**
- Select section → loads students via AJAX (`?ajax=students&section_id=X`)
- Shows checklist: each student row with Present / Absent / Late radio
- Submit creates `AttendanceSession` + one `AttendanceRecord` per student

**`report.php`**
- Select student → shows month-wise summary table
- Counts Present / Absent / Late with percentage

### Marks (`marks/enter.php`)
- Select Exam → Select Subject → loads SubjectExam + students of relevant class
- Table: Student Name | Max Marks (readonly) | Obtained Marks (input)
- Bulk submit via single form POST

### Reports (`reports/generate.php`)
- Select Student + Exam
- Calculates total from Marks table
- Determines grade (A: ≥90, B: ≥75, C: ≥60, D: ≥45, F: <45)
- Inserts into Report table
- Redirects to `view.php` for print-friendly view

### Fees (`fees/`)

**`index.php`** — Table: Student | Amount | Due Date | Paid | Balance | Status badge

**`pay.php`** — Form: select fee, amount paid, date → insert FeePayment

---

## Authentication & Session Flow

```
User visits any page
       │
       ▼
middleware.php included?
  │ No session → redirect /auth/login.php
  │ Session OK → continue
       │
       ▼
Role check via requireRole() or hasRole()
  │ Wrong role → 403
  │ OK → render page
```

**Session variables stored at login:**
```php
$_SESSION['user_id']
$_SESSION['name']
$_SESSION['role_name']   // 'Admin' | 'Teacher' | 'Student' | 'Parent' | 'Staff'
```

---

## Build Checklist

Use this checklist to track progress:

### Setup
- [ ] Create MySQL database and run DDL
- [ ] Configure `config/db.php`
- [ ] Set up `auth/middleware.php`
- [ ] Build global CSS files (`global.css`, `layout.css`, `components.css`)
- [ ] Build `includes/` partials (header, sidebar, topbar, footer)

### Auth
- [ ] Login page (with validation + error display)
- [ ] Logout handler
- [ ] Session-based role middleware

### Core Modules
- [ ] Dashboard (role-aware stat cards)
- [ ] Users CRUD
- [ ] Students CRUD + view profile
- [ ] Teachers CRUD
- [ ] Parents CRUD
- [ ] Staff CRUD
- [ ] Classes CRUD
- [ ] Sections CRUD (linked to class)
- [ ] Subjects CRUD
- [ ] Class Assignments CRUD
- [ ] Timetable view + manage

### Academic Modules
- [ ] Attendance — take attendance page
- [ ] Attendance — view/report page
- [ ] Exams CRUD + SubjectExam management
- [ ] Marks — bulk entry
- [ ] Marks — student view
- [ ] Reports — generate + print view

### Finance
- [ ] Fees — list + create
- [ ] Fee Payments — record payment

### Polish
- [ ] Responsive sidebar (mobile hamburger toggle)
- [ ] Flash messages (success/error after form submit)
- [ ] Empty state illustrations on empty tables
- [ ] Print CSS for Report Card page
- [ ] Confirm dialogs before delete actions

---

## Notes for the Agent

1. **No external PHP frameworks.** Pure PHP with PDO only.
2. **Every page** must `require_once '../auth/middleware.php'` and `require_once '../config/db.php'` before any output.
3. **Passwords** must be stored with `password_hash($pass, PASSWORD_BCRYPT)` and verified with `password_verify()`.
4. **CSRF protection** (minimal): use a hidden `$_SESSION['csrf_token']` and verify on every POST.
5. **Pagination:** Use `LIMIT 20 OFFSET ?` in SQL; pass `?page=N` in URLs.
6. **Dynamic dropdowns** (e.g., Section depends on Class): use a small PHP endpoint returning JSON — `?ajax=sections&class_id=X` — fetched by `fetch()` in JS.
7. **Flash messages:** Store in `$_SESSION['flash']` = `['type'=>'success','msg'=>'...']`, display and unset in `includes/topbar.php`.
8. **Print view** (`reports/view.php`): add `@media print { .sidebar, .topbar { display:none; } }` in CSS.
9. **All SQL** must use prepared statements — no string interpolation in queries.
10. The default admin login is `admin@school.com` / `admin123` — make sure to re-hash if the seed hash doesn't match your PHP version.
