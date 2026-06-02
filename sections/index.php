<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$pageTitle = 'Sections';
$db = getDB();
$rows = $db->query("SELECT s.*,c.class_name FROM Section s JOIN Class c ON c.class_id=s.class_id ORDER BY c.class_name,s.section_name")->fetchAll();
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Sections</h1>
  <a href="/dbProject/sections/create.php" class="btn-primary"><i data-lucide="plus"></i> Add Section</a>
</div>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><th>#</th><th>Section Name</th><th>Class</th><th>Actions</th></tr></thead>
  <tbody>
  <?php if(empty($rows)): ?>
    <tr><td colspan="4"><div class="empty-state">No sections found.</div></td></tr>
  <?php else: foreach($rows as $r): ?>
    <tr>
      <td><?= $r['section_id'] ?></td>
      <td><?= htmlspecialchars($r['section_name']) ?></td>
      <td><?= htmlspecialchars($r['class_name']) ?></td>
      <td>
        <a href="/dbProject/sections/edit.php?id=<?= $r['section_id'] ?>" class="btn-ghost btn-sm">Edit</a>
        <form method="POST" action="/dbProject/sections/delete.php" style="display:inline" onsubmit="confirmDelete(this);return false">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="id" value="<?= $r['section_id'] ?>">
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
