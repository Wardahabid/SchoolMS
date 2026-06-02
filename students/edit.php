<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT s.*,u.name,u.email FROM Student s JOIN User u ON u.user_id=s.user_id WHERE s.student_id=?");
$stmt->execute([$id]);
$student = $stmt->fetch();
if (!$student) { header('Location: /dbProject/students/index.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $class_id = (int)($_POST['class_id'] ?? 0);
    $admission_date = $_POST['admission_date'] ?? '';
    $parent_id = ($_POST['parent_id'] ?? '') !== '' ? (int)$_POST['parent_id'] : null;
    if (!$name) $errors[] = 'Name required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (empty($errors)) {
        $db->prepare("UPDATE User SET name=?,email=? WHERE user_id=?")->execute([$name,$email,$student['user_id']]);
        if (!empty($_POST['password'])) {
            $db->prepare("UPDATE User SET password=? WHERE user_id=?")->execute([password_hash($_POST['password'],PASSWORD_BCRYPT),$student['user_id']]);
        }
    $section_id = (int)($_POST['section_id'] ?? 0);
        $db->prepare("UPDATE Student SET class_id=?,section_id=?,parent_id=?,admission_date=? WHERE student_id=?")->execute([$class_id,$section_id,$parent_id,$admission_date,$id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Student updated.'];
        header('Location: /dbProject/students/index.php'); exit;
    }
}
$classes = $db->query("SELECT * FROM Class ORDER BY class_name")->fetchAll();
$parents = $db->query("SELECT p.parent_id,u.name FROM Parent p JOIN User u ON u.user_id=p.user_id ORDER BY u.name")->fetchAll();
$pageTitle = 'Edit Student';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Edit Student</h1></div>
<div class="form-card">
<?php foreach($errors as $e): ?><div class="flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="form-row">
    <div class="form-group"><label>Full Name</label><input name="name" value="<?= htmlspecialchars($_POST['name']??$student['name']) ?>" required></div>
    <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($_POST['email']??$student['email']) ?>" required></div>
  </div>
  <div class="form-group"><label>New Password <span class="hint">(leave blank to keep)</span></label><input type="password" name="password"></div>
  <div class="form-row">
    <div class="form-group"><label>Class</label>
      <select name="class_id" id="class_id" required>
        <?php foreach($classes as $c): ?>
          <option value="<?= $c['class_id'] ?>" <?= (($_POST['class_id']??$student['class_id'])==$c['class_id'])?'selected':'' ?>><?= htmlspecialchars($c['class_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>Admission Date</label><input type="date" name="admission_date" value="<?= htmlspecialchars($_POST['admission_date']??$student['admission_date']) ?>" required></div>
  </div>
  <div class="form-group"><label>Section</label>
    <select name="section_id" id="section_id" required>
      <option value="">Select section</option>
    </select>
  </div>
  <div class="form-group"><label>Parent</label>
    <select name="parent_id">
      <option value="">None</option>
      <?php foreach($parents as $p): ?>
        <option value="<?= $p['parent_id'] ?>" <?= (($_POST['parent_id']??$student['parent_id'])==$p['parent_id'])?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn-primary">Save Changes</button>
    <a href="/dbProject/students/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<script>
(async function(){
    const classId = <?= (int)$student['class_id'] ?>;
    const sectionId = <?= (int)($student['section_id'] ?? 0) ?>;
    if (!classId) return;
    const sel = document.getElementById('section_id');
    const data = await fetchJSON('/dbProject/students/edit.php?ajax=sections&class_id=' + classId);
    data.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.section_id;
        opt.text = s.section_name;
        if (s.section_id == sectionId) opt.selected = true;
        sel.appendChild(opt);
    });
})();
document.getElementById('class_id').addEventListener('change', async function() {
    const sel = document.getElementById('section_id');
    sel.innerHTML = '<option value="">Select section</option>';
    if (!this.value) return;
    const data = await fetchJSON('/dbProject/students/edit.php?ajax=sections&class_id=' + this.value);
    data.forEach(s => sel.innerHTML += `<option value="${s.section_id}">${s.section_name}</option>`);
});
</script>
<?php require_once '../includes/footer.php'; ?>
