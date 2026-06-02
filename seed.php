<?php
require_once 'config/db.php';
$db = getDB();
$success = [];
$errors = [];

function run($db, $sql, $params = []) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $db->lastInsertId();
}

try {
    // ── Classes ──────────────────────────────────────────────
    $c1 = run($db, "INSERT INTO Class (class_name) VALUES (?)", ['Grade 1']);
    $c2 = run($db, "INSERT INTO Class (class_name) VALUES (?)", ['Grade 2']);
    $c3 = run($db, "INSERT INTO Class (class_name) VALUES (?)", ['Grade 3']);
    $success[] = "Classes created";

    // ── Sections ─────────────────────────────────────────────
    $s1a = run($db, "INSERT INTO Section (class_id,section_name) VALUES (?,?)", [$c1, 'A']);
    $s1b = run($db, "INSERT INTO Section (class_id,section_name) VALUES (?,?)", [$c1, 'B']);
    $s2a = run($db, "INSERT INTO Section (class_id,section_name) VALUES (?,?)", [$c2, 'A']);
    $s2b = run($db, "INSERT INTO Section (class_id,section_name) VALUES (?,?)", [$c2, 'B']);
    $s3a = run($db, "INSERT INTO Section (class_id,section_name) VALUES (?,?)", [$c3, 'A']);
    $success[] = "Sections created";

    // ── Subjects ─────────────────────────────────────────────
    $sub1 = run($db, "INSERT INTO Subject (subject_name) VALUES (?)", ['Mathematics']);
    $sub2 = run($db, "INSERT INTO Subject (subject_name) VALUES (?)", ['English']);
    $sub3 = run($db, "INSERT INTO Subject (subject_name) VALUES (?)", ['Science']);
    $sub4 = run($db, "INSERT INTO Subject (subject_name) VALUES (?)", ['History']);
    $sub5 = run($db, "INSERT INTO Subject (subject_name) VALUES (?)", ['Computer Science']);
    $success[] = "Subjects created";

    // ── Roles (get existing) ──────────────────────────────────
    $roles = $db->query("SELECT role_id,role_name FROM Role")->fetchAll(PDO::FETCH_KEY_PAIR);
    $roleTeacher = array_search('Teacher', $roles);
    $roleStudent = array_search('Student', $roles);
    $roleParent  = array_search('Parent',  $roles);
    $roleStaff   = array_search('Staff',   $roles);

    // ── Teachers ─────────────────────────────────────────────
    $teachers = [
        ['Alice Johnson', 'alice@school.com',  'Math & Science'],
        ['Bob Smith',     'bob@school.com',    'English Literature'],
        ['Carol White',   'carol@school.com',  'History & Geography'],
        ['David Brown',   'david@school.com',  'Computer Science'],
    ];
    $teacherIds = [];
    foreach ($teachers as [$name, $email, $spec]) {
        $uid = run($db, "INSERT INTO User (name,email,password,role_id) VALUES (?,?,?,?)",
            [$name, $email, password_hash('password', PASSWORD_BCRYPT), $roleTeacher]);
        $teacherIds[] = run($db, "INSERT INTO Teacher (user_id,specialization) VALUES (?,?)", [$uid, $spec]);
    }
    [$t1,$t2,$t3,$t4] = $teacherIds;
    $success[] = "Teachers created (password: password)";

    // ── Class Assignments ─────────────────────────────────────
    $ca1 = run($db, "INSERT INTO ClassAssignment (teacher_id,subject_id,section_id) VALUES (?,?,?)", [$t1,$sub1,$s1a]);
    $ca2 = run($db, "INSERT INTO ClassAssignment (teacher_id,subject_id,section_id) VALUES (?,?,?)", [$t2,$sub2,$s1a]);
    $ca3 = run($db, "INSERT INTO ClassAssignment (teacher_id,subject_id,section_id) VALUES (?,?,?)", [$t1,$sub3,$s1b]);
    $ca4 = run($db, "INSERT INTO ClassAssignment (teacher_id,subject_id,section_id) VALUES (?,?,?)", [$t3,$sub4,$s2a]);
    $ca5 = run($db, "INSERT INTO ClassAssignment (teacher_id,subject_id,section_id) VALUES (?,?,?)", [$t4,$sub5,$s2a]);
    $ca6 = run($db, "INSERT INTO ClassAssignment (teacher_id,subject_id,section_id) VALUES (?,?,?)", [$t1,$sub1,$s3a]);
    $success[] = "Class assignments created";

    // ── Timetable ─────────────────────────────────────────────
    $slots = [
        [$ca1,$s1a,$t1,'Monday 08:00-09:00'],
        [$ca2,$s1a,$t2,'Monday 09:00-10:00'],
        [$ca3,$s1b,$t1,'Tuesday 08:00-09:00'],
        [$ca4,$s2a,$t3,'Wednesday 08:00-09:00'],
        [$ca5,$s2a,$t4,'Wednesday 09:00-10:00'],
        [$ca6,$s3a,$t1,'Thursday 08:00-09:00'],
    ];
    foreach ($slots as [$ca,$sec,$t,$slot]) {
        run($db, "INSERT INTO Timetable (assignment_id,section_id,teacher_id,time_slot) VALUES (?,?,?,?)", [$ca,$sec,$t,$slot]);
    }
    $success[] = "Timetable slots created";

    // ── Parents ───────────────────────────────────────────────
    $parents = [
        ['Parent One',   'parent1@school.com', '03001111111'],
        ['Parent Two',   'parent2@school.com', '03002222222'],
        ['Parent Three', 'parent3@school.com', '03003333333'],
    ];
    $parentIds = [];
    foreach ($parents as [$name,$email,$phone]) {
        $uid = run($db, "INSERT INTO User (name,email,password,role_id) VALUES (?,?,?,?)",
            [$name, $email, password_hash('password', PASSWORD_BCRYPT), $roleParent]);
        $parentIds[] = run($db, "INSERT INTO Parent (user_id,phone) VALUES (?,?)", [$uid, $phone]);
    }
    [$p1,$p2,$p3] = $parentIds;
    $success[] = "Parents created (password: password)";

    // ── Students ──────────────────────────────────────────────
    $students = [
        ['Emma Wilson',    'emma@school.com',    $c1,$s1a,$p1,'2024-01-10'],
        ['Liam Davis',     'liam@school.com',    $c1,$s1a,$p1,'2024-01-10'],
        ['Olivia Moore',   'olivia@school.com',  $c1,$s1b,$p2,'2024-01-15'],
        ['Noah Taylor',    'noah@school.com',    $c1,$s1b,null,'2024-01-15'],
        ['Ava Anderson',   'ava@school.com',     $c2,$s2a,$p2,'2023-01-12'],
        ['James Thomas',   'james@school.com',   $c2,$s2a,$p3,'2023-01-12'],
        ['Sophia Jackson', 'sophia@school.com',  $c2,$s2b,null,'2023-02-01'],
        ['Lucas White',    'lucas@school.com',   $c3,$s3a,$p3,'2022-01-20'],
    ];
    $studentIds = [];
    foreach ($students as [$name,$email,$cid,$sid,$par,$date]) {
        $uid = run($db, "INSERT INTO User (name,email,password,role_id) VALUES (?,?,?,?)",
            [$name, $email, password_hash('password', PASSWORD_BCRYPT), $roleStudent]);
        $studentIds[] = run($db, "INSERT INTO Student (user_id,class_id,section_id,parent_id,admission_date) VALUES (?,?,?,?,?)",
            [$uid,$cid,$sid,$par,$date]);
    }
    [$st1,$st2,$st3,$st4,$st5,$st6,$st7,$st8] = $studentIds;
    $success[] = "Students created (password: password)";

    // ── Staff ─────────────────────────────────────────────────
    $staffList = [
        ['Sara Ahmed', 'sara@school.com',  'Librarian'],
        ['Usman Khan', 'usman@school.com', 'Security Guard'],
    ];
    foreach ($staffList as [$name,$email,$pos]) {
        $uid = run($db, "INSERT INTO User (name,email,password,role_id) VALUES (?,?,?,?)",
            [$name, $email, password_hash('password', PASSWORD_BCRYPT), $roleStaff]);
        run($db, "INSERT INTO Staff (user_id,position) VALUES (?,?)", [$uid,$pos]);
    }
    $success[] = "Staff created (password: password)";

    // ── Exams ─────────────────────────────────────────────────
    $ex1 = run($db, "INSERT INTO Exam (exam_name,date) VALUES (?,?)", ['Mid Term 2024','2024-03-15']);
    $ex2 = run($db, "INSERT INTO Exam (exam_name,date) VALUES (?,?)", ['Final Term 2024','2024-06-20']);
    $success[] = "Exams created";

    // ── Subject Exams ─────────────────────────────────────────
    $se1 = run($db, "INSERT INTO SubjectExam (exam_id,subject_id,max_marks) VALUES (?,?,?)", [$ex1,$sub1,100]);
    $se2 = run($db, "INSERT INTO SubjectExam (exam_id,subject_id,max_marks) VALUES (?,?,?)", [$ex1,$sub2,100]);
    $se3 = run($db, "INSERT INTO SubjectExam (exam_id,subject_id,max_marks) VALUES (?,?,?)", [$ex1,$sub3,100]);
    $se4 = run($db, "INSERT INTO SubjectExam (exam_id,subject_id,max_marks) VALUES (?,?,?)", [$ex2,$sub1,100]);
    $se5 = run($db, "INSERT INTO SubjectExam (exam_id,subject_id,max_marks) VALUES (?,?,?)", [$ex2,$sub2,100]);
    $success[] = "Subject exams created";

    // ── Marks ─────────────────────────────────────────────────
    $marksData = [
        [$st1,$se1,85],[$st1,$se2,78],[$st1,$se3,90],
        [$st2,$se1,72],[$st2,$se2,88],[$st2,$se3,65],
        [$st3,$se1,91],[$st3,$se2,84],[$st3,$se3,77],
        [$st4,$se1,60],[$st4,$se2,55],[$st4,$se3,70],
        [$st5,$se4,88],[$st5,$se5,92],
        [$st6,$se4,74],[$st6,$se5,68],
    ];
    foreach ($marksData as [$stid,$seid,$marks]) {
        run($db, "INSERT INTO Marks (student_id,subject_exam_id,obtained_marks) VALUES (?,?,?)", [$stid,$seid,$marks]);
    }
    $success[] = "Marks created";

    // ── Attendance Sessions ───────────────────────────────────
    $as1 = run($db, "INSERT INTO AttendanceSession (section_id,teacher_id,date) VALUES (?,?,?)", [$s1a,$t1,'2024-03-10']);
    $as2 = run($db, "INSERT INTO AttendanceSession (section_id,teacher_id,date) VALUES (?,?,?)", [$s1a,$t2,'2024-03-11']);
    $as3 = run($db, "INSERT INTO AttendanceSession (section_id,teacher_id,date) VALUES (?,?,?)", [$s2a,$t3,'2024-03-10']);
    $success[] = "Attendance sessions created";

    // ── Attendance Records ────────────────────────────────────
    $attData = [
        [$as1,$st1,'Present'],[$as1,$st2,'Present'],[$as1,$st3,'Absent'],
        [$as2,$st1,'Present'],[$as2,$st2,'Late'],   [$as2,$st3,'Present'],
        [$as3,$st5,'Present'],[$as3,$st6,'Absent'],
    ];
    foreach ($attData as [$asid,$stid,$status]) {
        run($db, "INSERT INTO AttendanceRecord (session_id,student_id,status) VALUES (?,?,?)", [$asid,$stid,$status]);
    }
    $success[] = "Attendance records created";

    // ── Fees ──────────────────────────────────────────────────
    $f1 = run($db, "INSERT INTO Fee (student_id,amount,due_date) VALUES (?,?,?)", [$st1,5000,'2024-04-01']);
    $f2 = run($db, "INSERT INTO Fee (student_id,amount,due_date) VALUES (?,?,?)", [$st2,5000,'2024-04-01']);
    $f3 = run($db, "INSERT INTO Fee (student_id,amount,due_date) VALUES (?,?,?)", [$st3,5000,'2024-04-01']);
    $f4 = run($db, "INSERT INTO Fee (student_id,amount,due_date) VALUES (?,?,?)", [$st5,6000,'2024-04-01']);
    $f5 = run($db, "INSERT INTO Fee (student_id,amount,due_date) VALUES (?,?,?)", [$st6,6000,'2024-04-01']);
    $success[] = "Fees created";

    // ── Fee Payments ──────────────────────────────────────────
    run($db, "INSERT INTO FeePayment (fee_id,amount_paid,payment_paid) VALUES (?,?,?)", [$f1,5000,'2024-03-25']);
    run($db, "INSERT INTO FeePayment (fee_id,amount_paid,payment_paid) VALUES (?,?,?)", [$f2,2500,'2024-03-25']);
    run($db, "INSERT INTO FeePayment (fee_id,amount_paid,payment_paid) VALUES (?,?,?)", [$f4,6000,'2024-03-20']);
    $success[] = "Fee payments created";

    // ── Reports ───────────────────────────────────────────────
    run($db, "INSERT INTO Report (student_id,exam_id,total_marks,grade,remarks) VALUES (?,?,?,?,?)",
        [$st1,$ex1,253,'A','Excellent performance']);
    run($db, "INSERT INTO Report (student_id,exam_id,total_marks,grade,remarks) VALUES (?,?,?,?,?)",
        [$st2,$ex1,225,'B','Good effort']);
    run($db, "INSERT INTO Report (student_id,exam_id,total_marks,grade,remarks) VALUES (?,?,?,?,?)",
        [$st3,$ex1,252,'A','Outstanding']);
    $success[] = "Reports created";

} catch (PDOException $e) {
    $errors[] = htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head><style>
body{font-family:monospace;padding:20px;background:#f8f9fb}
h2{font-family:sans-serif}
.ok{color:green;margin:4px 0}
.err{color:red;margin:4px 0}
.box{background:#fff;border:1px solid #e4e7ec;border-radius:8px;padding:20px;max-width:700px}
.creds{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:16px;margin-top:20px;font-family:sans-serif;font-size:13px}
.creds table{width:100%;border-collapse:collapse}
.creds td{padding:4px 8px}
</style></head>
<body>
<div class="box">
<h2>Seed Results</h2>
<?php foreach($success as $s): ?><p class="ok">✓ <?= $s ?></p><?php endforeach; ?>
<?php foreach($errors as $e): ?><p class="err">✗ <?= $e ?></p><?php endforeach; ?>
<?php if(empty($errors)): ?>
<div class="creds">
<strong>Test Login Credentials</strong> (all passwords: <code>password</code>)
<table>
<tr><td><strong>Role</strong></td><td><strong>Email</strong></td></tr>
<tr><td>Admin</td><td>admin@school.com</td></tr>
<tr><td>Teacher</td><td>alice@school.com</td></tr>
<tr><td>Teacher</td><td>bob@school.com</td></tr>
<tr><td>Student</td><td>emma@school.com</td></tr>
<tr><td>Student</td><td>ava@school.com</td></tr>
<tr><td>Parent</td><td>parent1@school.com</td></tr>
<tr><td>Staff</td><td>sara@school.com</td></tr>
</table>
</div>
<p style="margin-top:16px;font-family:sans-serif"><strong>✓ Done! <a href="/dbProject/auth/login.php">Delete this file then go to login</a></strong></p>
<?php endif; ?>
</div>
</body>
</html>
