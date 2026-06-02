<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    if (!$name) $errors[] = 'Name required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (strlen($pass) < 6) $errors[] = 'Password min 6 chars.';
    if (!$phone) $errors[] = 'Phone required.';
    if (empty($errors)) {
        $roleId = $db->query("SELECT role_id FROM Role WHERE role_name='Parent'")->fetchColumn();
        $db->prepare("INSERT INTO User (name,email,password,role_id) VALUES (?,?,?,?)")->execute([$name,$email,password_hash($pass,PASSWORD_BCRYPT),$roleId]);
        $uid = $db->lastInsertId();
        $db->prepare("INSERT INTO Parent (user_id,phone) VALUES (?,?)")->execute([$uid,$phone]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Parent created.'];
        header('Location: /dbProject/parents/index.php'); exit;
    }
}
$pageTitle = 'Add Parent';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Add Parent</h1></div>
<div class="form-card">
<?php foreach($errors as $e): ?><div class="flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="form-row">
    <div class="form-group"><label>Full Name</label><input name="name" value="<?= htmlspecialchars($_POST['name']??'') ?>" required></div>
    <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($_POST['email']??'') ?>" required></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
    <div class="form-group"><label>Phone</label><input name="phone" value="<?= htmlspecialchars($_POST['phone']??'') ?>" required></div>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn-primary">Create Parent</button>
    <a href="/dbProject/parents/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<?php require_once '../includes/footer.php'; ?>
