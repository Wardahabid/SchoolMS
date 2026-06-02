<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = trim($_POST['class_name'] ?? '');
    if (!$name) $errors[] = 'Class name required.';
    if (empty($errors)) {
        $db->prepare("INSERT INTO Class (class_name) VALUES (?)")->execute([$name]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Class created.'];
        header('Location: /dbProject/classes/index.php'); exit;
    }
}
$pageTitle = 'Add Class';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Add Class</h1></div>
<div class="form-card">
<?php foreach($errors as $e): ?><div class="flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="form-group"><label>Class Name</label><input name="class_name" value="<?= htmlspecialchars($_POST['class_name']??'') ?>" required></div>
  <div class="form-actions">
    <button type="submit" class="btn-primary">Create Class</button>
    <a href="/dbProject/classes/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<?php require_once '../includes/footer.php'; ?>
