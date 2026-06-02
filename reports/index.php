<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin','Student','Parent');
$pageTitle = 'Reports';
$db = getDB();
$rows = $db->query("SELECT r.*,u.name AS student_name,e.exam_name FROM Report r JOIN Student s ON s.student_id=r.student_id JOIN User u ON u.user_id=s.user_id JOIN Exam e ON e.exam_id=r.exam_id ORDER BY r.report_id DESC")->fetchAll();
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Reports</h1>
  <?php if(hasRole('Admin')): ?><a href="/dbProject/reports/generate.php" class="btn-primary"><i data-lucide="plus"></i> Generate Report</a><?php endif; ?>
</div>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><th>#</th><th>Student</th><th>Exam</th><th>Total</th><th>Grade</th><th>Actions</th></tr></thead>
  <tbody>
  <?php if(empty($rows)): ?>
    <tr><td colspan="6"><div class="empty-state">No reports found.</div></td></tr>
  <?php else: foreach($rows as $r): ?>
    <tr>
      <td><?= $r['report_id'] ?></td>
      <td><?= htmlspecialchars($r['student_name']) ?></td>
      <td><?= htmlspecialchars($r['exam_name']) ?></td>
      <td><?= $r['total_marks'] ?></td>
      <td><span class="badge <?= $r['grade']==='A'?'badge-success':($r['grade']==='F'?'badge-danger':'badge-warning') ?>"><?= $r['grade'] ?></span></td>
      <td><a href="/dbProject/reports/view.php?id=<?= $r['report_id'] ?>" class="btn-ghost btn-sm">View</a></td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
