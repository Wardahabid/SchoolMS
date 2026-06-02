<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = trim($_POST['subject_name'] ?? '');
    if (!$name) $errors[] = 'Subject name required.';
    if (empty($errors)) {
        $db->prepare("INSERT INTO Subject (subject_name) VALUES (?)")->execute([$name]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Subject created.'];
        header('Location: /dbProject/subjects/index.php'); exit;
    }
}
$pageTitle = 'Add Subject';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Add Subject</h1></div>
<div class="form-card">
<?php foreach($errors as $e): ?><div class="flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="form-group"><label>Subject Name</label><input name="subject_name" value="<?= htmlspecialchars($_POST['subject_name']??'') ?>" required></div>
  <div class="form-actions">
    <button type="submit" class="btn-primary">Create Subject</button>
    <a href="/dbProject/subjects/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<?php require_once '../includes/footer.php'; ?>
