<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$pageTitle = 'Class Assignments';
$db = getDB();
$rows = $db->query("SELECT ca.*,u.name AS teacher_name,sub.subject_name,c.class_name,s.section_name FROM ClassAssignment ca JOIN Teacher t ON t.teacher_id=ca.teacher_id JOIN User u ON u.user_id=t.user_id JOIN Subject sub ON sub.subject_id=ca.subject_id JOIN Section s ON s.section_id=ca.section_id JOIN Class c ON c.class_id=s.class_id ORDER BY ca.assignment_id DESC")->fetchAll();
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Class Assignments</h1>
  <a href="/dbProject/class_assignments/create.php" class="btn-primary"><i data-lucide="plus"></i> Add Assignment</a>
</div>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><th>#</th><th>Teacher</th><th>Subject</th><th>Class</th><th>Section</th><th>Actions</th></tr></thead>
  <tbody>
  <?php if(empty($rows)): ?>
    <tr><td colspan="6"><div class="empty-state">No assignments found.</div></td></tr>
  <?php else: foreach($rows as $r): ?>
    <tr>
      <td><?= $r['assignment_id'] ?></td>
      <td><?= htmlspecialchars($r['teacher_name']) ?></td>
      <td><?= htmlspecialchars($r['subject_name']) ?></td>
      <td><?= htmlspecialchars($r['class_name']) ?></td>
      <td><?= htmlspecialchars($r['section_name']) ?></td>
      <td>
        <a href="/dbProject/class_assignments/edit.php?id=<?= $r['assignment_id'] ?>" class="btn-ghost btn-sm">Edit</a>
        <form method="POST" action="/dbProject/class_assignments/delete.php" style="display:inline" onsubmit="confirmDelete(this);return false">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="id" value="<?= $r['assignment_id'] ?>">
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
