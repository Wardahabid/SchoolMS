<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$pageTitle = 'Subjects';
$db = getDB();
$rows = $db->query("SELECT * FROM Subject ORDER BY subject_name")->fetchAll();
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Subjects</h1>
  <a href="/dbProject/subjects/create.php" class="btn-primary"><i data-lucide="plus"></i> Add Subject</a>
</div>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><th>#</th><th>Subject Name</th><th>Actions</th></tr></thead>
  <tbody>
  <?php if(empty($rows)): ?>
    <tr><td colspan="3"><div class="empty-state">No subjects found.</div></td></tr>
  <?php else: foreach($rows as $r): ?>
    <tr>
      <td><?= $r['subject_id'] ?></td>
      <td><?= htmlspecialchars($r['subject_name']) ?></td>
      <td>
        <a href="/dbProject/subjects/edit.php?id=<?= $r['subject_id'] ?>" class="btn-ghost btn-sm">Edit</a>
        <form method="POST" action="/dbProject/subjects/delete.php" style="display:inline" onsubmit="confirmDelete(this);return false">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="id" value="<?= $r['subject_id'] ?>">
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
