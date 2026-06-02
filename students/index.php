<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin','Teacher');
$pageTitle = 'Students';
$db = getDB();
$search   = trim($_GET['search'] ?? '');
$classFilter = (int)($_GET['class_id'] ?? 0);
$page  = max(1,(int)($_GET['page'] ?? 1));
$limit = 20; $offset = ($page-1)*$limit;

$where = "WHERE 1=1";
$params = [];
if ($search) { $where .= " AND u.name LIKE ?"; $params[] = "%$search%"; }
if ($classFilter) { $where .= " AND s.class_id=?"; $params[] = $classFilter; }

$countStmt = $db->prepare("SELECT COUNT(*) FROM Student s JOIN User u ON u.user_id=s.user_id $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pages = ceil($total/$limit);

$stmt = $db->prepare("SELECT s.*,u.name,u.email,c.class_name,sec.section_name FROM Student s JOIN User u ON u.user_id=s.user_id JOIN Class c ON c.class_id=s.class_id LEFT JOIN Section sec ON sec.section_id=s.section_id $where ORDER BY s.student_id DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$students = $stmt->fetchAll();
$classes = $db->query("SELECT * FROM Class ORDER BY class_name")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Students</h1>
  <?php if(hasRole('Admin')): ?><a href="/dbProject/students/create.php" class="btn-primary"><i data-lucide="plus"></i> Add Student</a><?php endif; ?>
</div>
<div class="card" style="margin-bottom:16px">
  <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap">
    <input name="search" placeholder="Search by name…" value="<?= htmlspecialchars($search) ?>" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;width:220px">
    <select name="class_id" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px">
      <option value="">All Classes</option>
      <?php foreach($classes as $c): ?>
        <option value="<?= $c['class_id'] ?>" <?= $classFilter==$c['class_id']?'selected':'' ?>><?= htmlspecialchars($c['class_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-ghost">Filter</button>
    <?php if($search||$classFilter): ?><a href="/dbProject/students/index.php" class="btn-ghost">Clear</a><?php endif; ?>
  </form>
</div>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Class</th><th>Admission Date</th><th>Actions</th></tr></thead>
  <tbody>
  <?php if(empty($students)): ?>
    <tr><td colspan="6"><div class="empty-state">No students found.</div></td></tr>
  <?php else: foreach($students as $s): ?>
    <tr>
      <td><?= $s['student_id'] ?></td>
      <td><?= htmlspecialchars($s['name']) ?></td>
      <td><?= htmlspecialchars($s['email']) ?></td>
      <td><?= htmlspecialchars($s['class_name']) ?></td>
      <td><?= $s['admission_date'] ?></td>
      <td>
        <a href="/dbProject/students/view.php?id=<?= $s['student_id'] ?>" class="btn-ghost btn-sm">View</a>
        <?php if(hasRole('Admin')): ?>
        <a href="/dbProject/students/edit.php?id=<?= $s['student_id'] ?>" class="btn-ghost btn-sm">Edit</a>
        <form method="POST" action="/dbProject/students/delete.php" style="display:inline" onsubmit="confirmDelete(this);return false">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="id" value="<?= $s['student_id'] ?>">
          <button class="btn-danger btn-sm">Delete</button>
        </form>
        <?php endif; ?>
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
    <?php else: ?><a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&class_id=<?= $classFilter ?>"><?= $i ?></a><?php endif; ?>
  <?php endfor; ?>
</div>
<?php endif; ?>
</div>
<?php require_once '../includes/footer.php'; ?>
