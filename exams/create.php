<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = trim($_POST['exam_name'] ?? '');
    $date = $_POST['date'] ?? '';
    if (!$name) $errors[] = 'Exam name required.';
    if (!$date) $errors[] = 'Date required.';
    if (empty($errors)) {
        $db->prepare("INSERT INTO Exam (exam_name,date) VALUES (?,?)")->execute([$name,$date]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Exam created.'];
        header('Location: /dbProject/exams/index.php'); exit;
    }
}
$pageTitle = 'Add Exam';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Add Exam</h1></div>
<div class="form-card">
<?php foreach($errors as $e): ?><div class="flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="form-row">
    <div class="form-group"><label>Exam Name</label><input name="exam_name" value="<?= htmlspecialchars($_POST['exam_name']??'') ?>" required></div>
    <div class="form-group"><label>Date</label><input type="date" name="date" value="<?= htmlspecialchars($_POST['date']??'') ?>" required></div>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn-primary">Create Exam</button>
    <a href="/dbProject/exams/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<?php require_once '../includes/footer.php'; ?>
