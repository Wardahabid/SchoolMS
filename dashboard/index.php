<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
$pageTitle = 'Dashboard';
$db = getDB();

if (hasRole('Admin')) {
    $students = $db->query("SELECT COUNT(*) FROM Student")->fetchColumn();
    $teachers = $db->query("SELECT COUNT(*) FROM Teacher")->fetchColumn();
    $classes  = $db->query("SELECT COUNT(*) FROM Class")->fetchColumn();
    $feesDue  = $db->query("SELECT COUNT(*) FROM Fee f WHERE f.amount > COALESCE((SELECT SUM(fp.amount_paid) FROM FeePayment fp WHERE fp.fee_id=f.fee_id),0)")->fetchColumn();
} elseif (hasRole('Teacher')) {
    $stmt = $db->prepare("SELECT COUNT(DISTINCT ca.section_id) FROM ClassAssignment ca JOIN Teacher t ON t.teacher_id=ca.teacher_id WHERE t.user_id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $myClasses = $stmt->fetchColumn();
    $teacherRow = $db->prepare("SELECT teacher_id FROM Teacher WHERE user_id=?");
    $teacherRow->execute([$_SESSION['user_id']]);
    $tid = $teacherRow->fetchColumn();
    $todaySessions = $db->prepare("SELECT COUNT(*) FROM AttendanceSession WHERE teacher_id=? AND date=CURDATE()");
    $todaySessions->execute([$tid]);
    $todaySessions = $todaySessions->fetchColumn();
    $upcomingExams = $db->query("SELECT COUNT(*) FROM Exam WHERE date>=CURDATE()")->fetchColumn();
} elseif (hasRole('Student')) {
    $stmt = $db->prepare("SELECT s.student_id FROM Student s WHERE s.user_id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();
    $sid = $student['student_id'] ?? 0;
    $totalAtt = $db->prepare("SELECT COUNT(*) FROM AttendanceRecord WHERE student_id=?");
    $totalAtt->execute([$sid]);
    $total = $totalAtt->fetchColumn();
    $presentAtt = $db->prepare("SELECT COUNT(*) FROM AttendanceRecord WHERE student_id=? AND status='Present'");
    $presentAtt->execute([$sid]);
    $present = $presentAtt->fetchColumn();
    $attPct = $total > 0 ? round($present/$total*100) : 0;
    $feeStmt = $db->prepare("SELECT COUNT(*) FROM Fee WHERE student_id=?");
    $feeStmt->execute([$sid]);
    $feeCount = $feeStmt->fetchColumn();
} elseif (hasRole('Parent')) {
    $stmt = $db->prepare("SELECT s.student_id FROM Student s JOIN Parent p ON p.parent_id=s.parent_id WHERE p.user_id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $child = $stmt->fetch();
    $csid = $child['student_id'] ?? 0;
    $totalAtt = $db->prepare("SELECT COUNT(*) FROM AttendanceRecord WHERE student_id=?");
    $totalAtt->execute([$csid]);
    $ctotal = $totalAtt->fetchColumn();
    $presentAtt = $db->prepare("SELECT COUNT(*) FROM AttendanceRecord WHERE student_id=? AND status='Present'");
    $presentAtt->execute([$csid]);
    $cpresent = $presentAtt->fetchColumn();
    $cattPct = $ctotal > 0 ? round($cpresent/$ctotal*100) : 0;
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Dashboard</h1></div>
<div class="stat-grid">
<?php if(hasRole('Admin')): ?>
  <div class="stat-card"><div class="stat-label">Total Students</div><div class="stat-value"><?= $students ?></div></div>
  <div class="stat-card"><div class="stat-label">Total Teachers</div><div class="stat-value"><?= $teachers ?></div></div>
  <div class="stat-card"><div class="stat-label">Total Classes</div><div class="stat-value"><?= $classes ?></div></div>
  <div class="stat-card"><div class="stat-label">Fees Due</div><div class="stat-value"><?= $feesDue ?></div></div>
<?php elseif(hasRole('Teacher')): ?>
  <div class="stat-card"><div class="stat-label">My Classes</div><div class="stat-value"><?= $myClasses ?></div></div>
  <div class="stat-card"><div class="stat-label">Today's Sessions</div><div class="stat-value"><?= $todaySessions ?></div></div>
  <div class="stat-card"><div class="stat-label">Upcoming Exams</div><div class="stat-value"><?= $upcomingExams ?></div></div>
<?php elseif(hasRole('Student')): ?>
  <div class="stat-card"><div class="stat-label">Attendance %</div><div class="stat-value"><?= $attPct ?>%</div></div>
  <div class="stat-card"><div class="stat-label">Fee Records</div><div class="stat-value"><?= $feeCount ?></div></div>
<?php elseif(hasRole('Parent')): ?>
  <div class="stat-card"><div class="stat-label">Child Attendance %</div><div class="stat-value"><?= $cattPct ?>%</div></div>
<?php endif; ?>
</div>
<?php require_once '../includes/footer.php'; ?>
