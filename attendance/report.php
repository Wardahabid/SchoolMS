<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin','Teacher');
$pageTitle = 'Attendance Report';
$db = getDB();
$studentId = (int)($_GET['student_id'] ?? 0);
$students  = $db->query("SELECT s.student_id,u.name FROM Student s JOIN User u ON u.user_id=s.user_id ORDER BY u.name")->fetchAll();
$summary   = [];
if ($studentId) {
    $stmt = $db->prepare("SELECT DATE_FORMAT(ass.date,'%Y-%m') AS month, SUM(ar.status='Present') AS present, SUM(ar.status='Absent') AS absent, SUM(ar.status='Late') AS late, COUNT(*) AS total FROM AttendanceRecord ar JOIN AttendanceSession ass ON ass.session_id=ar.session_id WHERE ar.student_id=? GROUP BY month ORDER BY month DESC");
    $stmt->execute([$studentId]);
    $summary = $stmt->fetchAll();
}
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Attendance Report</h1></div>
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
<?php if($studentId && !empty($summary)): ?>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><th>Month</th><th>Present</th><th>Absent</th><th>Late</th><th>Total</th><th>%</th></tr></thead>
  <tbody>
  <?php foreach($summary as $row): ?>
    <tr>
      <td><?= $row['month'] ?></td>
      <td><?= $row['present'] ?></td>
      <td><?= $row['absent'] ?></td>
      <td><?= $row['late'] ?></td>
      <td><?= $row['total'] ?></td>
      <td><?= $row['total'] > 0 ? round($row['present']/$row['total']*100) : 0 ?>%</td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>
<?php elseif($studentId): ?>
<div class="card"><div class="empty-state">No attendance records found.</div></div>
<?php endif; ?>
<?php require_once '../includes/footer.php'; ?>
