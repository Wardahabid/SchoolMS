<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$pageTitle = 'Manage Timetable';
$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $assignment_id = (int)($_POST['assignment_id'] ?? 0);
    $teacher_id    = (int)($_POST['teacher_id'] ?? 0);
    $section_id    = (int)($_POST['section_id'] ?? 0);
    $time_slot     = trim($_POST['time_slot'] ?? '');
    if (!$assignment_id || !$teacher_id || !$section_id || !$time_slot) {
        $errors[] = 'All fields required.';
    } else {
        $db->prepare("INSERT INTO Timetable (assignment_id,teacher_id,section_id,time_slot) VALUES (?,?,?,?)")->execute([$assignment_id,$teacher_id,$section_id,$time_slot]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Timetable slot saved.'];
        header('Location: /dbProject/timetable/index.php'); exit;
    }
}

$assignments = $db->query("SELECT ca.*,u.name AS teacher_name,sub.subject_name,c.class_name,s.section_name FROM ClassAssignment ca JOIN Teacher t ON t.teacher_id=ca.teacher_id JOIN User u ON u.user_id=t.user_id JOIN Subject sub ON sub.subject_id=ca.subject_id JOIN Section s ON s.section_id=ca.section_id JOIN Class c ON c.class_id=s.class_id ORDER BY c.class_name")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Manage Timetable Slot</h1></div>
<div class="form-card">
<?php foreach($errors as $e): ?><div class="flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="form-group"><label>Class Assignment</label>
    <select name="assignment_id" id="ca_sel" required>
      <option value="">Select assignment</option>
      <?php foreach($assignments as $a): ?>
        <option value="<?= $a['assignment_id'] ?>" data-teacher="<?= $a['teacher_id'] ?>" data-section="<?= $a['section_id'] ?>">
          <?= htmlspecialchars($a['class_name'].' — '.$a['section_name'].' — '.$a['subject_name'].' ('.$a['teacher_name'].')') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <input type="hidden" name="teacher_id" id="teacher_id">
  <input type="hidden" name="section_id" id="section_id">
  <div class="form-group"><label>Time Slot</label><input name="time_slot" placeholder="e.g. Monday 08:00–09:00" value="<?= htmlspecialchars($_POST['time_slot']??'') ?>" required></div>
  <div class="form-actions">
    <button type="submit" class="btn-primary">Save Slot</button>
    <a href="/dbProject/timetable/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<script>
document.getElementById('ca_sel').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    document.getElementById('teacher_id').value = opt.dataset.teacher || '';
    document.getElementById('section_id').value  = opt.dataset.section || '';
});
</script>
<?php require_once '../includes/footer.php'; ?>
