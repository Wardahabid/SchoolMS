<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();

if (isset($_GET['ajax']) && $_GET['ajax'] === 'sections') {
    $stmt = $db->prepare("SELECT section_id,section_name FROM Section WHERE class_id=?");
    $stmt->execute([(int)$_GET['class_id']]);
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll());
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $subject_id = (int)($_POST['subject_id'] ?? 0);
    $section_id = (int)($_POST['section_id'] ?? 0);
    if (!$teacher_id || !$subject_id || !$section_id) $errors[] = 'All fields required.';
    if (empty($errors)) {
        $db->prepare("INSERT INTO ClassAssignment (teacher_id,subject_id,section_id) VALUES (?,?,?)")->execute([$teacher_id,$subject_id,$section_id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Assignment created.'];
        header('Location: /dbProject/class_assignments/index.php'); exit;
    }
}
$teachers = $db->query("SELECT t.teacher_id,u.name FROM Teacher t JOIN User u ON u.user_id=t.user_id ORDER BY u.name")->fetchAll();
$subjects = $db->query("SELECT * FROM Subject ORDER BY subject_name")->fetchAll();
$sections = $db->query("SELECT s.*,c.class_name FROM Section s JOIN Class c ON c.class_id=s.class_id ORDER BY c.class_name,s.section_name")->fetchAll();
$pageTitle = 'Add Assignment';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Add Class Assignment</h1></div>
<div class="form-card">
<?php foreach($errors as $e): ?><div class="flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="form-group"><label>Teacher</label>
    <select name="teacher_id" required>
      <option value="">Select teacher</option>
      <?php foreach($teachers as $t): ?>
        <option value="<?= $t['teacher_id'] ?>" <?= (($_POST['teacher_id']??'')==$t['teacher_id'])?'selected':'' ?>><?= htmlspecialchars($t['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group"><label>Subject</label>
    <select name="subject_id" required>
      <option value="">Select subject</option>
      <?php foreach($subjects as $s): ?>
        <option value="<?= $s['subject_id'] ?>" <?= (($_POST['subject_id']??'')==$s['subject_id'])?'selected':'' ?>><?= htmlspecialchars($s['subject_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group"><label>Section</label>
    <select name="section_id" required>
      <option value="">Select section</option>
      <?php foreach($sections as $s): ?>
        <option value="<?= $s['section_id'] ?>" <?= (($_POST['section_id']??'')==$s['section_id'])?'selected':'' ?>><?= htmlspecialchars($s['class_name'].' — '.$s['section_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn-primary">Create Assignment</button>
    <a href="/dbProject/class_assignments/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<?php require_once '../includes/footer.php'; ?>
