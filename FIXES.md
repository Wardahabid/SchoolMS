# School Management System — Fix Instructions for Agent

> **Purpose:** This document lists every discrepancy found between the current codebase (built from `PROJECT_BLUEPRINT.md`) and the canonical ERD. Apply every fix below in the stated order. The application is already built — do **not** rebuild from scratch; only apply targeted changes.

---

## How to Use This Document

1. Read each numbered fix fully before touching any file.
2. Apply database migrations **first**, then update PHP files.
3. Test each module after its fix before moving to the next.
4. All SQL must continue to use prepared statements — no string interpolation.

---

## Fix 1 — Add `section_id` to the `Student` Table

### Problem
The ERD shows `Student` has a direct `section_id` FK (in addition to `class_id`). The current schema omits this column. As a result, the system cannot determine which section a student belongs to without querying through `ClassAssignment` or another indirect path, which breaks attendance, timetable, and marks lookups.

### Database Migration
```sql
-- Run on school_db
ALTER TABLE Student
    ADD COLUMN section_id INT NULL AFTER class_id,
    ADD CONSTRAINT fk_student_section
        FOREIGN KEY (section_id) REFERENCES Section(section_id);
```

> `NULL` is intentional for backward compatibility with existing rows. After migration, populate `section_id` for existing students manually or via a one-time update script, then tighten to `NOT NULL` if desired.

### PHP Changes

**`students/create.php`**
- The form already has a dynamic Section dropdown (loaded via `?ajax=sections&class_id=X`). Add `section_id` to the hidden fields that are submitted.
- In the INSERT logic, add `section_id` to the Student insert:
```php
// Before (current)
$stmt = $db->prepare("INSERT INTO Student (user_id, class_id, parent_id, admission_date) VALUES (?,?,?,?)");
$stmt->execute([$user_id, $class_id, $parent_id ?: null, $admission_date]);

// After (fixed)
$stmt = $db->prepare("INSERT INTO Student (user_id, class_id, section_id, parent_id, admission_date) VALUES (?,?,?,?,?)");
$stmt->execute([$user_id, $class_id, $section_id, $parent_id ?: null, $admission_date]);
```

**`students/edit.php`**
- Load current `section_id` from the Student row and pre-select it in the Section dropdown.
- Add `section_id` to the UPDATE statement:
```php
// After (fixed)
$stmt = $db->prepare("UPDATE Student SET class_id=?, section_id=?, parent_id=?, admission_date=? WHERE student_id=?");
$stmt->execute([$class_id, $section_id, $parent_id ?: null, $admission_date, $student_id]);
```

**`students/index.php`**
- The paginated table currently shows Class and Section. If Section is currently derived by a JOIN through ClassAssignment, replace it with a direct JOIN:
```sql
-- Replace complex join with:
SELECT s.student_id, u.name, c.class_name, sec.section_name, s.admission_date
FROM Student s
JOIN User u ON u.user_id = s.user_id
JOIN Class c ON c.class_id = s.class_id
LEFT JOIN Section sec ON sec.section_id = s.section_id
```

**`attendance/take.php`** — AJAX endpoint `?ajax=students&section_id=X`
- This endpoint currently queries students by matching through ClassAssignment or similar. Simplify it to query Student directly:
```sql
SELECT s.student_id, u.name
FROM Student s
JOIN User u ON u.user_id = s.user_id
WHERE s.section_id = ?
ORDER BY u.name ASC
```

---

## Fix 2 — Correct the `AttendanceSession` Table

### Problem
The current schema has an `exam_name VARCHAR(100)` column in `AttendanceSession`. This is **wrong** — attendance sessions are not linked to exams. The ERD shows `AttendanceSession` must have a `teacher_id` FK instead, so the system knows which teacher took the session.

### Database Migration
```sql
-- Step 1: Drop the wrong column
ALTER TABLE AttendanceSession
    DROP COLUMN exam_name;

-- Step 2: Add the correct column
ALTER TABLE AttendanceSession
    ADD COLUMN teacher_id INT NOT NULL AFTER section_id,
    ADD CONSTRAINT fk_as_teacher
        FOREIGN KEY (teacher_id) REFERENCES Teacher(teacher_id);
```

> If existing rows exist, you must assign a default `teacher_id` before making it `NOT NULL`. Temporarily use `NULL`, populate, then apply `NOT NULL`.

### PHP Changes

**`attendance/take.php`**
- When creating the `AttendanceSession`, include `teacher_id` sourced from the logged-in teacher's session:
```php
// Resolve teacher_id from session user_id
$stmt = $db->prepare("SELECT teacher_id FROM Teacher WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch();
$teacher_id = $teacher['teacher_id'];

// Insert session
$stmt = $db->prepare("INSERT INTO AttendanceSession (section_id, teacher_id, date) VALUES (?,?,?)");
$stmt->execute([$section_id, $teacher_id, $date]);
```

- Remove any reference to `exam_name` in the attendance take form and its POST handler.

**`attendance/index.php`** (session list)
- Update the listing query to JOIN Teacher and User so the teacher's name is shown:
```sql
SELECT ats.session_id, ats.date, sec.section_name, c.class_name, u.name AS teacher_name
FROM AttendanceSession ats
JOIN Section sec ON sec.section_id = ats.section_id
JOIN Class c ON c.class_id = sec.class_id
JOIN Teacher t ON t.teacher_id = ats.teacher_id
JOIN User u ON u.user_id = t.user_id
ORDER BY ats.date DESC
```

**`attendance/view.php`** — Show teacher name alongside the session header.

---

## Fix 3 — Fix the `Timetable` Table Structure

### Problem
The current schema defines `Timetable` with `assignment_id` as its **PRIMARY KEY** (reusing the `ClassAssignment` PK), making Timetable a strict 1-to-1 extension of ClassAssignment. The ERD shows Timetable should have its **own auto-increment PK** and its own FK pointing to `ClassAssignment`. This allows multiple time slots to be associated with one class assignment (e.g., a subject taught on Monday and Wednesday).

### Database Migration
```sql
-- Drop and recreate Timetable with correct structure
DROP TABLE IF EXISTS Timetable;

CREATE TABLE Timetable (
    timetable_id  INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    section_id    INT NOT NULL,
    teacher_id    INT NOT NULL,
    time_slot     VARCHAR(50) NOT NULL,
    CONSTRAINT fk_tt_assignment FOREIGN KEY (assignment_id) REFERENCES ClassAssignment(assignment_id),
    CONSTRAINT fk_tt_teacher    FOREIGN KEY (teacher_id)    REFERENCES Teacher(teacher_id),
    CONSTRAINT fk_tt_section    FOREIGN KEY (section_id)    REFERENCES Section(section_id)
);
```

> If Timetable rows already exist, export them first (`SELECT * FROM Timetable`), drop, recreate, then re-import with a new `timetable_id` column.

### PHP Changes

**`timetable/manage.php`**
- The form should allow adding **multiple time slots** for the same `assignment_id`.
- Change INSERT to include `timetable_id` (auto) and accept `assignment_id` as a foreign key input:
```php
$stmt = $db->prepare("INSERT INTO Timetable (assignment_id, section_id, teacher_id, time_slot) VALUES (?,?,?,?)");
$stmt->execute([$assignment_id, $section_id, $teacher_id, $time_slot]);
```
- Add a DELETE handler keyed on `timetable_id`, not `assignment_id`.

**`timetable/index.php`**
- Update the listing query:
```sql
SELECT tt.timetable_id, tt.time_slot,
       ca.assignment_id,
       sec.section_name, c.class_name,
       sub.subject_name,
       u.name AS teacher_name
FROM Timetable tt
JOIN ClassAssignment ca ON ca.assignment_id = tt.assignment_id
JOIN Section sec ON sec.section_id = tt.section_id
JOIN Class c ON c.class_id = sec.class_id
JOIN Subject sub ON sub.subject_id = ca.subject_id
JOIN Teacher t ON t.teacher_id = tt.teacher_id
JOIN User u ON u.user_id = t.user_id
ORDER BY sec.section_name, tt.time_slot
```

---

## Fix 4 — Remove Redundant `class_id` from `ClassAssignment`

### Problem
The current `ClassAssignment` table has both `class_id` and `section_id`. The ERD only shows `section_id` (because Section already carries `class_id`). Keeping `class_id` directly on ClassAssignment creates a data integrity risk — the `class_id` on ClassAssignment could disagree with the `class_id` on the referenced Section.

### Database Migration
```sql
-- Drop the FK first, then the column
ALTER TABLE ClassAssignment
    DROP FOREIGN KEY fk_ca_class,
    DROP COLUMN class_id;
```

### PHP Changes

**`class_assignments/create.php`**
- Remove the Class dropdown. Only keep the Section dropdown (which already implies the class).
- The form now only needs: Teacher, Subject, Section.
- Update INSERT:
```php
// After (fixed)
$stmt = $db->prepare("INSERT INTO ClassAssignment (teacher_id, subject_id, section_id) VALUES (?,?,?)");
$stmt->execute([$teacher_id, $subject_id, $section_id]);
```

**`class_assignments/index.php`**
- Update the listing query to derive class name through Section:
```sql
SELECT ca.assignment_id,
       u.name AS teacher_name,
       sub.subject_name,
       sec.section_name,
       c.class_name
FROM ClassAssignment ca
JOIN Teacher t ON t.teacher_id = ca.teacher_id
JOIN User u ON u.user_id = t.user_id
JOIN Subject sub ON sub.subject_id = ca.subject_id
JOIN Section sec ON sec.section_id = ca.section_id
JOIN Class c ON c.class_id = sec.class_id
ORDER BY c.class_name, sec.section_name
```

**`class_assignments/edit.php`**
- Remove the Class field. Keep only Teacher, Subject, Section. Update the UPDATE query to match.

**Anywhere `class_id` is referenced on ClassAssignment** across the codebase — search for `ca.class_id` or `ClassAssignment.class_id` and replace with `sec.class_id` (via the Section join).

---

## Fix 5 — Correct `FeePayment` Column Name

### Problem
The current schema uses `payment_date DATE` as the date column in `FeePayment`. The ERD labels this column `payment_paid`. Align the column name with the ERD so all queries remain consistent.

### Database Migration
```sql
ALTER TABLE FeePayment
    CHANGE COLUMN payment_date payment_paid DATE NOT NULL;
```

### PHP Changes

Search the entire codebase for every reference to `payment_date` in fee-related files and rename to `payment_paid`:

- **`fees/pay.php`** — form field name + INSERT query
- **`fees/index.php`** — SELECT query + table column header
- **`fees/view.php`** — display logic

Example fix in `fees/pay.php`:
```php
// Before
$stmt = $db->prepare("INSERT INTO FeePayment (fee_id, amount_paid, payment_date) VALUES (?,?,?)");
$stmt->execute([$fee_id, $amount_paid, $payment_date]);

// After
$stmt = $db->prepare("INSERT INTO FeePayment (fee_id, amount_paid, payment_paid) VALUES (?,?,?)");
$stmt->execute([$fee_id, $amount_paid, $payment_paid]);
```

---

## Fix 6 — Marks Entry Must Filter Students by Section (Not Class)

### Problem
The ERD data flow for Marks goes: Exam → SubjectExam → Marks ← Student ← Section. The `marks/enter.php` currently loads students by `class_id`. Now that `Student.section_id` exists (Fix 1), marks entry should filter students by section to match the subject's assignment scope.

### PHP Changes

**`marks/enter.php`**
- Add a Section dropdown after the Exam and Subject dropdowns.
- Load students filtered by `section_id`:
```sql
SELECT s.student_id, u.name
FROM Student s
JOIN User u ON u.user_id = s.user_id
WHERE s.section_id = ?
ORDER BY u.name ASC
```
- The selected `section_id` should match the `ClassAssignment.section_id` for the chosen subject/teacher, which can be pre-filtered if the logged-in user is a Teacher.

---

## Fix 7 — Verify All Cross-Module Queries After Schema Changes

After applying Fixes 1–6, do a full search of the codebase for the following patterns and verify each query is still valid:

| Search term | Why |
|---|---|
| `ClassAssignment` | Must no longer reference `ca.class_id` |
| `AttendanceSession` | Must no longer reference `exam_name`; must include `teacher_id` |
| `Timetable` | Must use `timetable_id` as PK, not `assignment_id` |
| `Student` | Must include `section_id` in all INSERTs and UPDATEs |
| `payment_date` | Must be renamed to `payment_paid` everywhere |
| `?ajax=students` | AJAX endpoint must now query via `Student.section_id` |

---

## Summary of All Database Changes

| # | Table | Change |
|---|---|---|
| 1 | `Student` | Add `section_id INT NULL FK → Section` |
| 2 | `AttendanceSession` | Drop `exam_name`; add `teacher_id INT NOT NULL FK → Teacher` |
| 3 | `Timetable` | Drop and recreate with own auto-increment `timetable_id` PK |
| 4 | `ClassAssignment` | Drop `class_id` column and its FK |
| 5 | `FeePayment` | Rename `payment_date` → `payment_paid` |

Run all migrations in the order listed above (1 → 5) to avoid FK dependency conflicts.

---

## Notes for the Agent

- Apply database migrations before touching any PHP file.
- After Fix 3 (Timetable drop/recreate), the `timetable/manage.php` delete handler must change its WHERE clause from `WHERE assignment_id = ?` to `WHERE timetable_id = ?`.
- The `dashboard/index.php` attendance stat for Teachers (e.g., "Today's Attendance Sessions") should now query `AttendanceSession WHERE teacher_id = ?` using the resolved `teacher_id` from session — update this query accordingly.
- No new modules need to be created. All fixes are confined to existing files and the existing schema.
- After all fixes, re-run a login test for each role (Admin, Teacher, Student, Parent) to confirm no broken queries surface.
