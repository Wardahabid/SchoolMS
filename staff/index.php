<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$pageTitle = 'Staff';
$db = getDB();
$page = max(1,(int)($_GET['page'] ?? 1));
$limit = 20; $offset = ($page-1)*$limit;
$total = $db->query("SELECT COUNT(*) FROM Staff")->fetchColumn();
$pages = ceil($total/$limit);
$rows = $db->prepare("SELECT s.*,u.name,u.email FROM Staff s JOIN User u ON u.user_id=s.user_id ORDER BY s.staff_id DESC LIMIT $limit OFFSET $offset");
$rows->execute(); $rows = $rows->fetchAll();
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Staff</h1>
  <a href="/dbProject/staff/create.php" class="btn-primary"><i data-lucide="plus"></i> Add Staff</a>
</div>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Position</th><th>Actions</th></tr></thead>
  <tbody>
  <?php if(empty($rows)): ?>
    <tr><td colspan="5"><div class="empty-state">No staff found.</div></td></tr>
  <?php else: foreach($rows as $r): ?>
    <tr>
      <td><?= $r['staff_id'] ?></td>
      <td><?= htmlspecialchars($r['name']) ?></td>
      <td><?= htmlspecialchars($r['email']) ?></td>
      <td><?= htmlspecialchars($r['position']) ?></td>
      <td>
        <a href="/dbProject/staff/edit.php?id=<?= $r['staff_id'] ?>" class="btn-ghost btn-sm">Edit</a>
        <form method="POST" action="/dbProject/staff/delete.php" style="display:inline" onsubmit="confirmDelete(this);return false">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="id" value="<?= $r['staff_id'] ?>">
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
