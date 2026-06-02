<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT t.*,u.name,u.email FROM Teacher t JOIN User u ON u.user_id=t.user_id WHERE t.teacher_id=?");
$stmt->execute([$id]); $row = $stmt->fetch();
if (!$row) { header('Location: /dbProject/teachers/index.php'); exit; }
$assignments = $db->prepare("SELECT ca.*,c.class_name,s.section_name,sub.subject_name FROM ClassAssignment ca JOIN Class c ON c.class_id=ca.class_id JOIN Section s ON s.section_id=ca.section_id JOIN Subject sub ON sub.subject_id=ca.subject_id WHERE ca.teacher_id=?");
$assignments->execute([$id]); $assignments = $assignments->fetchAll();
$pageTitle = 'Teacher Profile';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1><?= htmlspecialchars($row['name']) ?></h1><a href="/dbProject/teachers/index.php" class="btn-ghost">← Back</a></div>
<div class="card" style="margin-bottom:20px">
  <p style="font-size:13px;margin-bottom:8px"><strong>Email:</strong> <?= htmlspecialchars($row['email']) ?></p>
  <p style="font-size:13px"><strong>Specialization:</strong> <?= htmlspecialchars($row['specialization']) ?></p>
</div>
<div class="card">
  <h3 style="margin-bottom:14px;font-size:15px">Class Assignments</h3>
  <?php if(empty($assignments)): ?><p style="color:var(--text-muted);font-size:13px">No assignments.</p>
  <?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Class</th><th>Section</th><th>Subject</th></tr></thead>
    <tbody>
    <?php foreach($assignments as $a): ?>
      <tr><td><?= htmlspecialchars($a['class_name']) ?></td><td><?= htmlspecialchars($a['section_name']) ?></td><td><?= htmlspecialchars($a['subject_name']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
<?php require_once '../includes/footer.php'; ?>
