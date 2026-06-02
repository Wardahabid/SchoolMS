<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$pageTitle = 'Exams';
$db = getDB();
$rows = $db->query("SELECT e.*,(SELECT COUNT(*) FROM SubjectExam se WHERE se.exam_id=e.exam_id) AS subject_count FROM Exam e ORDER BY e.date DESC")->fetchAll();
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Exams</h1>
  <a href="/dbProject/exams/create.php" class="btn-primary"><i data-lucide="plus"></i> Add Exam</a>
</div>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><th>#</th><th>Exam Name</th><th>Date</th><th>Subjects</th><th>Actions</th></tr></thead>
  <tbody>
  <?php if(empty($rows)): ?>
    <tr><td colspan="5"><div class="empty-state">No exams found.</div></td></tr>
  <?php else: foreach($rows as $r): ?>
    <tr>
      <td><?= $r['exam_id'] ?></td>
      <td><?= htmlspecialchars($r['exam_name']) ?></td>
      <td><?= $r['date'] ?></td>
      <td><?= $r['subject_count'] ?></td>
      <td>
        <a href="/dbProject/exams/subjects.php?exam_id=<?= $r['exam_id'] ?>" class="btn-ghost btn-sm">Subjects</a>
        <a href="/dbProject/exams/edit.php?id=<?= $r['exam_id'] ?>" class="btn-ghost btn-sm">Edit</a>
        <form method="POST" action="/dbProject/exams/delete.php" style="display:inline" onsubmit="confirmDelete(this);return false">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="id" value="<?= $r['exam_id'] ?>">
          <button class="btn-danger btn-sm">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
