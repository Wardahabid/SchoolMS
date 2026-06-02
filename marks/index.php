<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin','Teacher','Student','Parent');
$pageTitle = 'Marks';
$db = getDB();
$exams = $db->query("SELECT * FROM Exam ORDER BY date DESC")->fetchAll();
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Marks</h1>
  <?php if(hasRole('Admin','Teacher')): ?>
  <a href="/dbProject/marks/enter.php" class="btn-primary"><i data-lucide="plus"></i> Enter Marks</a>
  <?php endif; ?>
</div>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><th>Exam</th><th>Date</th><th>Actions</th></tr></thead>
  <tbody>
  <?php if(empty($exams)): ?>
    <tr><td colspan="3"><div class="empty-state">No exams found.</div></td></tr>
  <?php else: foreach($exams as $e): ?>
    <tr>
      <td><?= htmlspecialchars($e['exam_name']) ?></td>
      <td><?= $e['date'] ?></td>
      <td><a href="/dbProject/marks/view.php?exam_id=<?= $e['exam_id'] ?>" class="btn-ghost btn-sm">View Marks</a></td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
