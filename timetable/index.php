<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin','Teacher','Student');
$pageTitle = 'Timetable';
$db = getDB();
$sectionFilter = (int)($_GET['section_id'] ?? 0);
$sections = $db->query("SELECT s.*,c.class_name FROM Section s JOIN Class c ON c.class_id=s.class_id ORDER BY c.class_name,s.section_name")->fetchAll();

$rows = [];
if ($sectionFilter) {
    $stmt = $db->prepare("SELECT tt.*,u.name AS teacher_name,sub.subject_name,c.class_name,s.section_name FROM Timetable tt JOIN ClassAssignment ca ON ca.assignment_id=tt.assignment_id JOIN Teacher t ON t.teacher_id=tt.teacher_id JOIN User u ON u.user_id=t.user_id JOIN Subject sub ON sub.subject_id=ca.subject_id JOIN Section s ON s.section_id=tt.section_id JOIN Class c ON c.class_id=s.class_id WHERE tt.section_id=? ORDER BY tt.time_slot");
    $stmt->execute([$sectionFilter]);
    $rows = $stmt->fetchAll();
}
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Timetable</h1>
  <?php if(hasRole('Admin')): ?><a href="/dbProject/timetable/manage.php" class="btn-primary"><i data-lucide="plus"></i> Manage Slots</a><?php endif; ?>
</div>
<div class="card" style="margin-bottom:16px">
  <form method="GET" style="display:flex;gap:12px;align-items:center">
    <select name="section_id" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px">
      <option value="">Select Section</option>
      <?php foreach($sections as $s): ?>
        <option value="<?= $s['section_id'] ?>" <?= $sectionFilter==$s['section_id']?'selected':'' ?>><?= htmlspecialchars($s['class_name'].' — '.$s['section_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-ghost">View</button>
  </form>
</div>
<?php if($sectionFilter): ?>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><th>Time Slot</th><th>Subject</th><th>Teacher</th><th>Class</th></tr></thead>
  <tbody>
  <?php if(empty($rows)): ?>
    <tr><td colspan="4"><div class="empty-state">No timetable entries for this section.</div></td></tr>
  <?php else: foreach($rows as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['time_slot']) ?></td>
      <td><?= htmlspecialchars($r['subject_name']) ?></td>
      <td><?= htmlspecialchars($r['teacher_name']) ?></td>
      <td><?= htmlspecialchars($r['class_name']) ?></td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
</div>
</div>
<?php endif; ?>
<?php require_once '../includes/footer.php'; ?>
