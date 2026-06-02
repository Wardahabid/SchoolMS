<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin','Teacher','Student','Parent');
$pageTitle = 'Attendance Report';
$db = getDB();

// Determine student_id based on role
if (hasRole('Student')) {
    $stmt = $db->prepare("SELECT student_id FROM Student WHERE user_id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $studentId = (int)$stmt->fetchColumn();
} elseif (hasRole('Parent')) {
    // Parent may have multiple children — pick selected or first
    $stmt = $db->prepare("SELECT s.student_id,u.name FROM Student s JOIN Parent p ON p.parent_id=s.parent_id JOIN User u ON u.user_id=s.user_id WHERE p.user_id=? ORDER BY u.name");
    $stmt->execute([$_SESSION['user_id']]);
    $children = $stmt->fetchAll();
    $studentId = (int)($_GET['student_id'] ?? ($children[0]['student_id'] ?? 0));
    // Verify selected child belongs to this parent
    $valid = array_column($children, 'student_id');
    if (!in_array($studentId, $valid)) $studentId = $children[0]['student_id'] ?? 0;
} else {
    $studentId = (int)($_GET['student_id'] ?? 0);
    $students = $db->query("SELECT s.student_id,u.name FROM Student s JOIN User u ON u.user_id=s.user_id ORDER BY u.name")->fetchAll();
}

// Fetch student name for heading
$studentName = '';
if ($studentId) {
    $s = $db->prepare("SELECT u.name FROM Student s JOIN User u ON u.user_id=s.user_id WHERE s.student_id=?");
    $s->execute([$studentId]);
    $studentName = $s->fetchColumn();
}

// Monthly summary
$summary = [];
if ($studentId) {
    $stmt = $db->prepare("SELECT DATE_FORMAT(ass.date,'%Y-%m') AS month, SUM(ar.status='Present') AS present, SUM(ar.status='Absent') AS absent, SUM(ar.status='Late') AS late, COUNT(*) AS total FROM AttendanceRecord ar JOIN AttendanceSession ass ON ass.session_id=ar.session_id WHERE ar.student_id=? GROUP BY month ORDER BY month DESC");
    $stmt->execute([$studentId]);
    $summary = $stmt->fetchAll();
}

// Full records
$records = [];
if ($studentId) {
    $stmt = $db->prepare("SELECT ass.date, sec.section_name, c.class_name, u.name AS teacher_name, ar.status FROM AttendanceRecord ar JOIN AttendanceSession ass ON ass.session_id=ar.session_id JOIN Section sec ON sec.section_id=ass.section_id JOIN Class c ON c.class_id=sec.class_id LEFT JOIN Teacher t ON t.teacher_id=ass.teacher_id LEFT JOIN User u ON u.user_id=t.user_id WHERE ar.student_id=? ORDER BY ass.date DESC");
    $stmt->execute([$studentId]);
    $records = $stmt->fetchAll();
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Attendance Report<?= $studentName ? ' — '.htmlspecialchars($studentName) : '' ?></h1></div>

<?php if(hasRole('Admin','Teacher')): ?>
<div class="card" style="margin-bottom:16px">
  <form method="GET" style="display:flex;gap:12px;align-items:center">
    <select name="student_id" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px">
      <option value="">Select student</option>
      <?php foreach($students as $s): ?>
        <option value="<?= $s['student_id'] ?>" <?= $studentId==$s['student_id']?'selected':'' ?>><?= htmlspecialchars($s['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-ghost">View</button>
  </form>
</div>
<?php endif; ?>

<?php if(hasRole('Parent') && count($children) > 1): ?>
<div class="card" style="margin-bottom:16px">
  <form method="GET" style="display:flex;gap:12px;align-items:center">
    <select name="student_id" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px">
      <?php foreach($children as $ch): ?>
        <option value="<?= $ch['student_id'] ?>" <?= $studentId==$ch['student_id']?'selected':'' ?>><?= htmlspecialchars($ch['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-ghost">View</button>
  </form>
</div>
<?php endif; ?>

<?php if($studentId && !empty($summary)): ?>
<div class="card" style="margin-bottom:20px">
  <h3 style="margin-bottom:14px;font-size:15px">Monthly Summary</h3>
  <div class="table-wrap"><table>
    <thead><tr><th>Month</th><th>Present</th><th>Absent</th><th>Late</th><th>Total</th><th>%</th></tr></thead>
    <tbody>
    <?php foreach($summary as $row): ?>
      <tr>
        <td><?= $row['month'] ?></td>
        <td><span class="badge badge-success"><?= $row['present'] ?></span></td>
        <td><span class="badge badge-danger"><?= $row['absent'] ?></span></td>
        <td><span class="badge badge-warning"><?= $row['late'] ?></span></td>
        <td><?= $row['total'] ?></td>
        <td><?= $row['total'] > 0 ? round($row['present']/$row['total']*100) : 0 ?>%</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<div class="card">
  <h3 style="margin-bottom:14px;font-size:15px">All Records</h3>
  <div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>Class</th><th>Section</th><th>Teacher</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach($records as $r): ?>
      <tr>
        <td><?= $r['date'] ?></td>
        <td><?= htmlspecialchars($r['class_name']) ?></td>
        <td><?= htmlspecialchars($r['section_name']) ?></td>
        <td><?= htmlspecialchars($r['teacher_name'] ?? '—') ?></td>
        <td><span class="badge <?= $r['status']==='Present'?'badge-success':($r['status']==='Late'?'badge-warning':'badge-danger') ?>"><?= $r['status'] ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php elseif($studentId): ?>
<div class="card"><div class="empty-state">No attendance records found.</div></div>
<?php endif; ?>
<?php require_once '../includes/footer.php'; ?>
