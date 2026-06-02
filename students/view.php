<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin','Teacher','Student','Parent');
$db = getDB();
$id = (int)($_GET['id'] ?? 0);

// Enforce access control
if (hasRole('Student')) {
    $stmt = $db->prepare("SELECT student_id FROM Student WHERE user_id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $id = (int)$stmt->fetchColumn();
} elseif (hasRole('Parent')) {
    // Verify this student belongs to this parent
    $stmt = $db->prepare("SELECT s.student_id FROM Student s JOIN Parent p ON p.parent_id=s.parent_id WHERE p.user_id=? AND s.student_id=?");
    $stmt->execute([$_SESSION['user_id'], $id]);
    if (!$stmt->fetch()) { header('Location: /dbProject/dashboard/index.php'); exit; }
}
$stmt = $db->prepare("SELECT s.*,u.name,u.email,c.class_name FROM Student s JOIN User u ON u.user_id=s.user_id JOIN Class c ON c.class_id=s.class_id WHERE s.student_id=?");
$stmt->execute([$id]);
$student = $stmt->fetch();
if (!$student) { header('Location: /dbProject/students/index.php'); exit; }

// Recent attendance
$attStmt = $db->prepare("SELECT ar.status,ass.date FROM AttendanceRecord ar JOIN AttendanceSession ass ON ass.session_id=ar.session_id WHERE ar.student_id=? ORDER BY ass.date DESC LIMIT 5");
$attStmt->execute([$id]);
$recentAtt = $attStmt->fetchAll();

// Recent marks
$marksStmt = $db->prepare("SELECT m.obtained_marks,se.max_marks,sub.subject_name,e.exam_name FROM Marks m JOIN SubjectExam se ON se.subject_exam_id=m.subject_exam_id JOIN Subject sub ON sub.subject_id=se.subject_id JOIN Exam e ON e.exam_id=se.exam_id WHERE m.student_id=? ORDER BY m.marks_id DESC LIMIT 5");
$marksStmt->execute([$id]);
$recentMarks = $marksStmt->fetchAll();

// Fee status
$feeStmt = $db->prepare("SELECT f.fee_id,f.amount,f.due_date,COALESCE(SUM(fp.amount_paid),0) AS paid FROM Fee f LEFT JOIN FeePayment fp ON fp.fee_id=f.fee_id WHERE f.student_id=? GROUP BY f.fee_id ORDER BY f.due_date DESC LIMIT 5");
$feeStmt->execute([$id]);
$fees = $feeStmt->fetchAll();

$pageTitle = 'Student Profile';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1><?= htmlspecialchars($student['name']) ?></h1>
  <a href="/dbProject/students/index.php" class="btn-ghost">← Back</a>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
  <div class="card">
    <h3 style="margin-bottom:14px;font-size:15px">Personal Info</h3>
    <p style="margin-bottom:8px;font-size:13px"><strong>Email:</strong> <?= htmlspecialchars($student['email']) ?></p>
    <p style="margin-bottom:8px;font-size:13px"><strong>Class:</strong> <?= htmlspecialchars($student['class_name']) ?></p>
    <p style="font-size:13px"><strong>Admission Date:</strong> <?= $student['admission_date'] ?></p>
  </div>
  <div class="card">
    <h3 style="margin-bottom:14px;font-size:15px">Fee Status</h3>
    <?php if(empty($fees)): ?><p style="color:var(--text-muted);font-size:13px">No fee records.</p>
    <?php else: foreach($fees as $f):
      $balance = $f['amount'] - $f['paid'];
    ?>
      <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px">
        <span>Due: <?= $f['due_date'] ?></span>
        <span><?= $balance <= 0 ? '<span class="badge badge-success">Paid</span>' : '<span class="badge badge-danger">Due: '.number_format($balance,2).'</span>' ?></span>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
  <div class="card">
    <h3 style="margin-bottom:14px;font-size:15px">Recent Attendance</h3>
    <?php if(empty($recentAtt)): ?><p style="color:var(--text-muted);font-size:13px">No records.</p>
    <?php else: foreach($recentAtt as $a): ?>
      <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px">
        <span><?= $a['date'] ?></span>
        <span class="badge <?= $a['status']==='Present'?'badge-success':($a['status']==='Late'?'badge-warning':'badge-danger') ?>"><?= $a['status'] ?></span>
      </div>
    <?php endforeach; endif; ?>
    <a href="/dbProject/attendance/report.php?student_id=<?= $id ?>" class="btn-ghost btn-sm" style="margin-top:10px;display:inline-block">View Full Attendance</a>
  </div>
  <div class="card">
    <h3 style="margin-bottom:14px;font-size:15px">Recent Marks</h3>
    <?php if(empty($recentMarks)): ?><p style="color:var(--text-muted);font-size:13px">No records.</p>
    <?php else: foreach($recentMarks as $m): ?>
      <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px">
        <span><?= htmlspecialchars($m['subject_name']) ?> (<?= htmlspecialchars($m['exam_name']) ?>)</span>
        <span><?= $m['obtained_marks'] ?>/<?= $m['max_marks'] ?></span>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
