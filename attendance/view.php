<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin','Teacher');
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$session = $db->prepare("SELECT ats.*,s.section_name,c.class_name,u.name AS teacher_name FROM AttendanceSession ats JOIN Section s ON s.section_id=ats.section_id JOIN Class c ON c.class_id=s.class_id LEFT JOIN Teacher t ON t.teacher_id=ats.teacher_id LEFT JOIN User u ON u.user_id=t.user_id WHERE ats.session_id=?");
$session->execute([$id]); $session = $session->fetch();
if (!$session) { header('Location: /dbProject/attendance/index.php'); exit; }
$records = $db->prepare("SELECT ar.status,u.name FROM AttendanceRecord ar JOIN Student st ON st.student_id=ar.student_id JOIN User u ON u.user_id=st.user_id WHERE ar.session_id=? ORDER BY u.name");
$records->execute([$id]); $records = $records->fetchAll();
$pageTitle = 'Attendance Session';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Attendance Session</h1>
  <a href="/dbProject/attendance/index.php" class="btn-ghost">← Back</a>
</div>
<div class="card" style="margin-bottom:16px;font-size:13px">
  <strong>Date:</strong> <?= $session['date'] ?> &nbsp;|&nbsp;
  <strong>Class:</strong> <?= htmlspecialchars($session['class_name']) ?> &nbsp;|&nbsp;
  <strong>Section:</strong> <?= htmlspecialchars($session['section_name']) ?> &nbsp;|&nbsp;
  <strong>Teacher:</strong> <?= htmlspecialchars($session['teacher_name'] ?? '—') ?>
</div>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><th>Student</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach($records as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['name']) ?></td>
      <td><span class="badge <?= $r['status']==='Present'?'badge-success':($r['status']==='Late'?'badge-warning':'badge-danger') ?>"><?= $r['status'] ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
