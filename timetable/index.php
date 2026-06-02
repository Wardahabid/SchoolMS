<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin','Teacher','Student','Parent');
$pageTitle = 'Timetable';
$db = getDB();

$rows = [];

if (hasRole('Admin')) {
    // Admin: filter by any section
    $sections = $db->query("SELECT s.*,c.class_name FROM Section s JOIN Class c ON c.class_id=s.class_id ORDER BY c.class_name,s.section_name")->fetchAll();
    $sectionFilter = (int)($_GET['section_id'] ?? 0);
    if ($sectionFilter) {
        $stmt = $db->prepare("SELECT tt.time_slot,sub.subject_name,u.name AS teacher_name,c.class_name,s.section_name FROM Timetable tt JOIN ClassAssignment ca ON ca.assignment_id=tt.assignment_id JOIN Teacher t ON t.teacher_id=tt.teacher_id JOIN User u ON u.user_id=t.user_id JOIN Subject sub ON sub.subject_id=ca.subject_id JOIN Section s ON s.section_id=tt.section_id JOIN Class c ON c.class_id=s.class_id WHERE tt.section_id=? ORDER BY tt.time_slot");
        $stmt->execute([$sectionFilter]);
        $rows = $stmt->fetchAll();
    }

} elseif (hasRole('Student')) {
    // Student: show their own section's timetable
    $stmt = $db->prepare("SELECT st.section_id FROM Student st WHERE st.user_id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $sectionId = $stmt->fetchColumn();
    if ($sectionId) {
        $stmt = $db->prepare("SELECT tt.time_slot,sub.subject_name,u.name AS teacher_name,c.class_name,s.section_name FROM Timetable tt JOIN ClassAssignment ca ON ca.assignment_id=tt.assignment_id JOIN Teacher t ON t.teacher_id=tt.teacher_id JOIN User u ON u.user_id=t.user_id JOIN Subject sub ON sub.subject_id=ca.subject_id JOIN Section s ON s.section_id=tt.section_id JOIN Class c ON c.class_id=s.class_id WHERE tt.section_id=? ORDER BY tt.time_slot");
        $stmt->execute([$sectionId]);
        $rows = $stmt->fetchAll();
    }

} elseif (hasRole('Teacher')) {
    // Teacher: show all their assigned sections' timetable
    $stmt = $db->prepare("SELECT tt.time_slot,sub.subject_name,u.name AS teacher_name,c.class_name,s.section_name FROM Timetable tt JOIN ClassAssignment ca ON ca.assignment_id=tt.assignment_id JOIN Teacher t ON t.teacher_id=tt.teacher_id JOIN User u ON u.user_id=t.user_id JOIN Subject sub ON sub.subject_id=ca.subject_id JOIN Section s ON s.section_id=tt.section_id JOIN Class c ON c.class_id=s.class_id WHERE t.user_id=? ORDER BY s.section_name,tt.time_slot");
    $stmt->execute([$_SESSION['user_id']]);
    $rows = $stmt->fetchAll();

} elseif (hasRole('Parent')) {
    // Parent: pick a child and show their timetable
    $stmt = $db->prepare("SELECT s.student_id,u.name FROM Student s JOIN Parent p ON p.parent_id=s.parent_id JOIN User u ON u.user_id=s.user_id WHERE p.user_id=? ORDER BY u.name");
    $stmt->execute([$_SESSION['user_id']]);
    $children = $stmt->fetchAll();
    $selectedChild = (int)($_GET['student_id'] ?? ($children[0]['student_id'] ?? 0));
    $valid = array_column($children,'student_id');
    if (!in_array($selectedChild,$valid)) $selectedChild = $children[0]['student_id'] ?? 0;
    if ($selectedChild) {
        $secStmt = $db->prepare("SELECT section_id FROM Student WHERE student_id=?");
        $secStmt->execute([$selectedChild]);
        $sectionId = $secStmt->fetchColumn();
        if ($sectionId) {
            $stmt = $db->prepare("SELECT tt.time_slot,sub.subject_name,u.name AS teacher_name,c.class_name,s.section_name FROM Timetable tt JOIN ClassAssignment ca ON ca.assignment_id=tt.assignment_id JOIN Teacher t ON t.teacher_id=tt.teacher_id JOIN User u ON u.user_id=t.user_id JOIN Subject sub ON sub.subject_id=ca.subject_id JOIN Section s ON s.section_id=tt.section_id JOIN Class c ON c.class_id=s.class_id WHERE tt.section_id=? ORDER BY tt.time_slot");
            $stmt->execute([$sectionId]);
            $rows = $stmt->fetchAll();
        }
    }
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Timetable</h1>
  <?php if(hasRole('Admin')): ?><a href="/dbProject/timetable/manage.php" class="btn-primary"><i data-lucide="plus"></i> Manage Slots</a><?php endif; ?>
</div>

<?php if(hasRole('Admin')): ?>
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
<?php endif; ?>

<?php if(hasRole('Parent') && count($children) > 1): ?>
<div class="card" style="margin-bottom:16px">
  <form method="GET" style="display:flex;gap:12px;align-items:center">
    <select name="student_id" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px">
      <?php foreach($children as $ch): ?>
        <option value="<?= $ch['student_id'] ?>" <?= $selectedChild==$ch['student_id']?'selected':'' ?>><?= htmlspecialchars($ch['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-ghost">Switch Child</button>
  </form>
</div>
<?php endif; ?>

<?php if(!hasRole('Admin') || (hasRole('Admin') && !empty($rows))): ?>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><th>Time Slot</th><th>Subject</th><th>Teacher</th><th>Class</th><th>Section</th></tr></thead>
  <tbody>
  <?php if(empty($rows)): ?>
    <tr><td colspan="5"><div class="empty-state">No timetable entries found.</div></td></tr>
  <?php else: foreach($rows as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['time_slot']) ?></td>
      <td><?= htmlspecialchars($r['subject_name']) ?></td>
      <td><?= htmlspecialchars($r['teacher_name']) ?></td>
      <td><?= htmlspecialchars($r['class_name']) ?></td>
      <td><?= htmlspecialchars($r['section_name']) ?></td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
</div>
</div>
<?php endif; ?>
<?php require_once '../includes/footer.php'; ?>
