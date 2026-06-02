<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin','Teacher');
$pageTitle = 'Attendance Sessions';
$db = getDB();
$page = max(1,(int)($_GET['page'] ?? 1));
$limit = 20; $offset = ($page-1)*$limit;
$total = $db->query("SELECT COUNT(*) FROM AttendanceSession")->fetchColumn();
$pages = ceil($total/$limit);
$rows = $db->prepare("SELECT ats.*,s.section_name,c.class_name,u.name AS teacher_name FROM AttendanceSession ats JOIN Section s ON s.section_id=ats.section_id JOIN Class c ON c.class_id=s.class_id LEFT JOIN Teacher t ON t.teacher_id=ats.teacher_id LEFT JOIN User u ON u.user_id=t.user_id ORDER BY ats.date DESC LIMIT $limit OFFSET $offset");
$rows->execute(); $rows = $rows->fetchAll();
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Attendance</h1>
  <a href="/dbProject/attendance/take.php" class="btn-primary"><i data-lucide="plus"></i> Take Attendance</a>
</div>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><th>#</th><th>Date</th><th>Class</th><th>Section</th><th>Teacher</th><th>Actions</th></tr></thead>
  <tbody>
  <?php if(empty($rows)): ?>
    <tr><td colspan="6"><div class="empty-state">No sessions found.</div></td></tr>
  <?php else: foreach($rows as $r): ?>
    <tr>
      <td><?= $r['session_id'] ?></td>
      <td><?= $r['date'] ?></td>
      <td><?= htmlspecialchars($r['class_name']) ?></td>
      <td><?= htmlspecialchars($r['section_name']) ?></td>
      <td><?= htmlspecialchars($r['teacher_name'] ?? '—') ?></td>
      <td><a href="/dbProject/attendance/view.php?id=<?= $r['session_id'] ?>" class="btn-ghost btn-sm">View</a>
          <a href="/dbProject/attendance/report.php" class="btn-ghost btn-sm">Report</a></td>
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
