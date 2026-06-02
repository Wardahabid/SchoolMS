<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name     = trim($_POST['section_name'] ?? '');
    $class_id = (int)($_POST['class_id'] ?? 0);
    if (!$name) $errors[] = 'Section name required.';
    if (!$class_id) $errors[] = 'Class required.';
    if (empty($errors)) {
        $db->prepare("INSERT INTO Section (class_id,section_name) VALUES (?,?)")->execute([$class_id,$name]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Section created.'];
        header('Location: /dbProject/sections/index.php'); exit;
    }
}
$classes = $db->query("SELECT * FROM Class ORDER BY class_name")->fetchAll();
$pageTitle = 'Add Section';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Add Section</h1></div>
<div class="form-card">
<?php foreach($errors as $e): ?><div class="flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="form-group"><label>Class</label>
    <select name="class_id" required>
      <option value="">Select class</option>
      <?php foreach($classes as $c): ?>
        <option value="<?= $c['class_id'] ?>" <?= (($_POST['class_id']??'')==$c['class_id'])?'selected':'' ?>><?= htmlspecialchars($c['class_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group"><label>Section Name</label><input name="section_name" value="<?= htmlspecialchars($_POST['section_name']??'') ?>" required></div>
  <div class="form-actions">
    <button type="submit" class="btn-primary">Create Section</button>
    <a href="/dbProject/sections/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<?php require_once '../includes/footer.php'; ?>
