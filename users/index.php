<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$pageTitle = 'Users';
$db = getDB();
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20; $offset = ($page-1)*$limit;
$total = $db->query("SELECT COUNT(*) FROM User")->fetchColumn();
$pages = ceil($total/$limit);
$users = $db->prepare("SELECT u.*,r.role_name FROM User u JOIN Role r ON r.role_id=u.role_id ORDER BY u.user_id DESC LIMIT $limit OFFSET $offset");
$users->execute();
$users = $users->fetchAll();
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Users</h1>
  <a href="/dbProject/users/create.php" class="btn-primary"><i data-lucide="plus"></i> Add User</a>
</div>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
  <tbody>
  <?php if(empty($users)): ?>
    <tr><td colspan="5"><div class="empty-state">No users found.</div></td></tr>
  <?php else: foreach($users as $u): ?>
    <tr>
      <td><?= $u['user_id'] ?></td>
      <td><?= htmlspecialchars($u['name']) ?></td>
      <td><?= htmlspecialchars($u['email']) ?></td>
      <td><span class="badge badge-info"><?= htmlspecialchars($u['role_name']) ?></span></td>
      <td>
        <a href="/dbProject/users/edit.php?id=<?= $u['user_id'] ?>" class="btn-ghost btn-sm">Edit</a>
        <form method="POST" action="/dbProject/users/delete.php" style="display:inline" onsubmit="confirmDelete(this);return false">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="id" value="<?= $u['user_id'] ?>">
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
