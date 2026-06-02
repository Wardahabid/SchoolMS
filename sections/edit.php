<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM Section WHERE section_id=?");
$stmt->execute([$id]); $row = $stmt->fetch();
if (!$row) { header('Location: /dbProject/sections/index.php'); exit; }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name     = trim($_POST['section_name'] ?? '');
    $class_id = (int)($_POST['class_id'] ?? 0);
    if (!$name) $errors[] = 'Section name required.';
    if (empty($errors)) {
        $db->prepare("UPDATE Section SET class_id=?,section_name=? WHERE section_id=?")->execute([$class_id,$name,$id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Section updated.'];
        header('Location: /dbProject/sections/index.php'); exit;
    }
}
$classes = $db->query("SELECT * FROM Class ORDER BY class_name")->fetchAll();
$pageTitle = 'Edit Section';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Edit Section</h1></div>
<div class="form-card">
<?php foreach($errors as $e): ?><div class="flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="form-group"><label>Class</label>
    <select name="class_id" required>
      <?php foreach($classes as $c): ?>
        <option value="<?= $c['class_id'] ?>" <?= (($_POST['class_id']??$row['class_id'])==$c['class_id'])?'selected':'' ?>><?= htmlspecialchars($c['class_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group"><label>Section Name</label><input name="section_name" value="<?= htmlspecialchars($_POST['section_name']??$row['section_name']) ?>" required></div>
  <div class="form-actions">
    <button type="submit" class="btn-primary">Save Changes</button>
    <a href="/dbProject/sections/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<?php require_once '../includes/footer.php'; ?>
