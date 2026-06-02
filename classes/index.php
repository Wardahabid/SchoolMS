<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$pageTitle = 'Classes';
$db = getDB();
$rows = $db->query("SELECT c.*,(SELECT COUNT(*) FROM Section s WHERE s.class_id=c.class_id) AS section_count FROM Class c ORDER BY c.class_name")->fetchAll();
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Classes</h1>
  <a href="/dbProject/classes/create.php" class="btn-primary"><i data-lucide="plus"></i> Add Class</a>
</div>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><th>#</th><th>Class Name</th><th>Sections</th><th>Actions</th></tr></thead>
  <tbody>
  <?php if(empty($rows)): ?>
    <tr><td colspan="4"><div class="empty-state">No classes found.</div></td></tr>
  <?php else: foreach($rows as $r): ?>
    <tr>
      <td><?= $r['class_id'] ?></td>
      <td><?= htmlspecialchars($r['class_name']) ?></td>
      <td><?= $r['section_count'] ?></td>
      <td>
        <a href="/dbProject/classes/edit.php?id=<?= $r['class_id'] ?>" class="btn-ghost btn-sm">Edit</a>
        <form method="POST" action="/dbProject/classes/delete.php" style="display:inline" onsubmit="confirmDelete(this);return false">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="id" value="<?= $r['class_id'] ?>">
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
