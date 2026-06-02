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
    $stmt = $db->prepare("SELECT student_id FROM Student WHERE user_id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $sid = (int)$stmt->fetchColumn();
    $total = $db->prepare("SELECT COUNT(*) FROM AttendanceRecord WHERE student_id=?");
    $total->execute([$sid]); $total = $total->fetchColumn();
    $present = $db->prepare("SELECT COUNT(*) FROM AttendanceRecord WHERE student_id=? AND status='Present'");
    $present->execute([$sid]); $present = $present->fetchColumn();
    $attPct = $total > 0 ? round($present/$total*100) : 0;
    $feeCount = $db->prepare("SELECT COUNT(*) FROM Fee WHERE student_id=?");
    $feeCount->execute([$sid]); $feeCount = $feeCount->fetchColumn();
    $feeBalance = $db->prepare("SELECT COALESCE(SUM(f.amount),0)-COALESCE(SUM(fp.amount_paid),0) FROM Fee f LEFT JOIN FeePayment fp ON fp.fee_id=f.fee_id WHERE f.student_id=?");
    $feeBalance->execute([$sid]); $feeBalance = $feeBalance->fetchColumn();

} elseif (hasRole('Parent')) {
    $stmt = $db->prepare("SELECT s.student_id,u.name FROM Student s JOIN Parent p ON p.parent_id=s.parent_id JOIN User u ON u.user_id=s.user_id WHERE p.user_id=? ORDER BY u.name");
    $stmt->execute([$_SESSION['user_id']]);
    $children = $stmt->fetchAll();
    $childStats = [];
    foreach ($children as $child) {
        $csid = $child['student_id'];
        $t = $db->prepare("SELECT COUNT(*) FROM AttendanceRecord WHERE student_id=?");
        $t->execute([$csid]); $ctotal = $t->fetchColumn();
        $p = $db->prepare("SELECT COUNT(*) FROM AttendanceRecord WHERE student_id=? AND status='Present'");
        $p->execute([$csid]); $cpresent = $p->fetchColumn();
        $b = $db->prepare("SELECT COALESCE(SUM(f.amount),0)-COALESCE(SUM(fp.amount_paid),0) AS balance FROM Fee f LEFT JOIN FeePayment fp ON fp.fee_id=f.fee_id WHERE f.student_id=?");
        $b->execute([$csid]); $balance = $b->fetchColumn();
        $childStats[] = [
            'name'   => $child['name'],
            'sid'    => $csid,
            'attPct' => $ctotal > 0 ? round($cpresent/$ctotal*100) : 0,
            'balance'=> $balance,
        ];
    }

} elseif (hasRole('Staff')) {
    $stmt = $db->prepare("SELECT s.position FROM Staff s WHERE s.user_id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $staffRow = $stmt->fetch();
    $totalStudents = $db->query("SELECT COUNT(*) FROM Student")->fetchColumn();
    $totalTeachers = $db->query("SELECT COUNT(*) FROM Teacher")->fetchColumn();
    $totalClasses  = $db->query("SELECT COUNT(*) FROM Class")->fetchColumn();
    $upcomingExams = $db->query("SELECT COUNT(*) FROM Exam WHERE date>=CURDATE()")->fetchColumn();
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Dashboard</h1></div>

<?php if(hasRole('Staff')): ?>
<div class="card" style="margin-bottom:20px;font-size:13px">
  <strong>Position:</strong> <?= htmlspecialchars($staffRow['position'] ?? '—') ?> &nbsp;|&nbsp;
  <strong>Welcome,</strong> <?= htmlspecialchars($_SESSION['name']) ?>
</div>
<?php endif; ?>

<div class="stat-grid">
<?php if(hasRole('Admin')): ?>
  <div class="stat-card"><div class="stat-label">Total Students</div><div class="stat-value"><?= $students ?></div></div>
  <div class="stat-card"><div class="stat-label">Total Teachers</div><div class="stat-value"><?= $teachers ?></div></div>
  <div class="stat-card"><div class="stat-label">Total Classes</div><div class="stat-value"><?= $classes ?></div></div>
  <div class="stat-card"><div class="stat-label">Fees Due</div><div class="stat-value"><?= $feesDue ?></div></div>

<?php elseif(hasRole('Teacher')): ?>
  <div class="stat-card"><div class="stat-label">My Sections</div><div class="stat-value"><?= $myClasses ?></div></div>
  <div class="stat-card"><div class="stat-label">Today's Sessions</div><div class="stat-value"><?= $todaySessions ?></div></div>
  <div class="stat-card"><div class="stat-label">Upcoming Exams</div><div class="stat-value"><?= $upcomingExams ?></div></div>

<?php elseif(hasRole('Student')): ?>
  <div class="stat-card"><div class="stat-label">Attendance %</div><div class="stat-value"><?= $attPct ?>%</div></div>
  <div class="stat-card"><div class="stat-label">Fee Records</div><div class="stat-value"><?= $feeCount ?></div></div>
  <div class="stat-card">
    <div class="stat-label">Fee Balance</div>
    <div class="stat-value" style="color:<?= $feeBalance>0?'var(--danger)':'var(--success)' ?>"><?= number_format($feeBalance,2) ?></div>
  </div>

<?php elseif(hasRole('Parent')): ?>
  <?php foreach($childStats as $ch): ?>
  <div class="stat-card">
    <div class="stat-label"><?= htmlspecialchars($ch['name']) ?> — Attendance</div>
    <div class="stat-value"><?= $ch['attPct'] ?>%</div>
    <div style="margin-top:8px"><a href="/dbProject/students/view.php?id=<?= $ch['sid'] ?>" class="btn-ghost btn-sm">View Profile</a></div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><?= htmlspecialchars($ch['name']) ?> — Fee Balance</div>
    <div class="stat-value" style="color:<?= $ch['balance']>0?'var(--danger)':'var(--success)' ?>"><?= number_format($ch['balance'],2) ?></div>
  </div>
  <?php endforeach; ?>

<?php elseif(hasRole('Staff')): ?>
  <div class="stat-card"><div class="stat-label">Total Students</div><div class="stat-value"><?= $totalStudents ?></div></div>
  <div class="stat-card"><div class="stat-label">Total Teachers</div><div class="stat-value"><?= $totalTeachers ?></div></div>
  <div class="stat-card"><div class="stat-label">Total Classes</div><div class="stat-value"><?= $totalClasses ?></div></div>
  <div class="stat-card"><div class="stat-label">Upcoming Exams</div><div class="stat-value"><?= $upcomingExams ?></div></div>
<?php endif; ?>
</div>
<?php require_once '../includes/footer.php'; ?>
