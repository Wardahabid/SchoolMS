<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();

// AJAX: sections by class
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
    $name           = trim($_POST['name'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $password       = $_POST['password'] ?? '';
    $class_id       = (int)($_POST['class_id'] ?? 0);
    $admission_date = $_POST['admission_date'] ?? '';
    $parent_id      = ($_POST['parent_id'] ?? '') !== '' ? (int)$_POST['parent_id'] : null;

    if (!$name) $errors[] = 'Name required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (strlen($password) < 6) $errors[] = 'Password min 6 chars.';
    if (!$class_id) $errors[] = 'Class required.';
    if (!$admission_date) $errors[] = 'Admission date required.';

    $section_id = (int)($_POST['section_id'] ?? 0);
    if (!$section_id) $errors[] = 'Section required.';

    if (empty($errors)) {
        $roleId = $db->query("SELECT role_id FROM Role WHERE role_name='Student'")->fetchColumn();
        $db->prepare("INSERT INTO User (name,email,password,role_id) VALUES (?,?,?,?)")->execute([$name,$email,password_hash($password,PASSWORD_BCRYPT),$roleId]);
        $userId = $db->lastInsertId();
        $db->prepare("INSERT INTO Student (user_id,class_id,section_id,parent_id,admission_date) VALUES (?,?,?,?,?)")->execute([$userId,$class_id,$section_id,$parent_id,$admission_date]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Student created.'];
        header('Location: /dbProject/students/index.php'); exit;
    }
}
$classes = $db->query("SELECT * FROM Class ORDER BY class_name")->fetchAll();
$parents = $db->query("SELECT p.parent_id,u.name FROM Parent p JOIN User u ON u.user_id=p.user_id ORDER BY u.name")->fetchAll();
$pageTitle = 'Add Student';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Add Student</h1></div>
<div class="form-card">
<?php foreach($errors as $e): ?><div class="flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <h3 style="margin-bottom:16px;font-size:15px">Account Info</h3>
  <div class="form-row">
    <div class="form-group"><label>Full Name</label><input name="name" value="<?= htmlspecialchars($_POST['name']??'') ?>" required></div>
    <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($_POST['email']??'') ?>" required></div>
  </div>
  <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
  <h3 style="margin:20px 0 16px;font-size:15px">Student Info</h3>
  <div class="form-row">
    <div class="form-group"><label>Class</label>
      <select name="class_id" id="class_id" required>
        <option value="">Select class</option>
        <?php foreach($classes as $c): ?>
          <option value="<?= $c['class_id'] ?>" <?= (($_POST['class_id']??'')==$c['class_id'])?'selected':'' ?>><?= htmlspecialchars($c['class_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>Admission Date</label><input type="date" name="admission_date" value="<?= htmlspecialchars($_POST['admission_date']??'') ?>" required></div>
  </div>
  <div class="form-group"><label>Section</label>
    <select name="section_id" id="section_id" required>
      <option value="">Select section</option>
    </select>
  </div>
  <div class="form-group"><label>Parent (optional)</label>
    <select name="parent_id">
      <option value="">None</option>
      <?php foreach($parents as $p): ?>
        <option value="<?= $p['parent_id'] ?>" <?= (($_POST['parent_id']??'')==$p['parent_id'])?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn-primary">Create Student</button>
    <a href="/dbProject/students/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<script>
document.getElementById('class_id').addEventListener('change', async function() {
    const sel = document.getElementById('section_id');
    sel.innerHTML = '<option value="">Select section</option>';
    if (!this.value) return;
    const data = await fetchJSON('/dbProject/students/create.php?ajax=sections&class_id=' + this.value);
    data.forEach(s => sel.innerHTML += `<option value="${s.section_id}">${s.section_name}</option>`);
});
</script>
<?php require_once '../includes/footer.php'; ?>
