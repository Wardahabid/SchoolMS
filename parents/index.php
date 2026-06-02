<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$pageTitle = 'Parents';
$db = getDB();
$page = max(1,(int)($_GET['page'] ?? 1));
$limit = 20; $offset = ($page-1)*$limit;
$total = $db->query("SELECT COUNT(*) FROM Parent")->fetchColumn();
$pages = ceil($total/$limit);
$rows = $db->prepare("SELECT p.*,u.name,u.email FROM Parent p JOIN User u ON u.user_id=p.user_id ORDER BY p.parent_id DESC LIMIT $limit OFFSET $offset");
$rows->execute(); $rows = $rows->fetchAll();
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Parents</h1>
  <a href="/dbProject/parents/create.php" class="btn-primary"><i data-lucide="plus"></i> Add Parent</a>
</div>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Actions</th></tr></thead>
  <tbody>
  <?php if(empty($rows)): ?>
    <tr><td colspan="5"><div class="empty-state">No parents found.</div></td></tr>
  <?php else: foreach($rows as $r): ?>
    <tr>
      <td><?= $r['parent_id'] ?></td>
      <td><?= htmlspecialchars($r['name']) ?></td>
      <td><?= htmlspecialchars($r['email']) ?></td>
      <td><?= htmlspecialchars($r['phone']) ?></td>
      <td>
        <a href="/dbProject/parents/edit.php?id=<?= $r['parent_id'] ?>" class="btn-ghost btn-sm">Edit</a>
        <form method="POST" action="/dbProject/parents/delete.php" style="display:inline" onsubmit="confirmDelete(this);return false">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="id" value="<?= $r['parent_id'] ?>">
          <button class="btn-danger btn-sm">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
</div>
<?php if($pages>1): ?>
<div class="pagination">
  <?php for($i=1;$i<=$pages;$i++): ?>
    <?php if($i==$page): ?><span class="current"><?= $i ?></span>
    <?php else: ?><a href="?page=<?= $i ?>"><?= $i ?></a><?php endif; ?>
  <?php endfor; ?>
</div>
<?php endif; ?>
</div>
<?php require_once '../includes/footer.php'; ?>
