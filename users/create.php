<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id  = (int)($_POST['role_id'] ?? 0);
    if (!$name) $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if (!$role_id) $errors[] = 'Role is required.';
    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO User (name,email,password,role_id) VALUES (?,?,?,?)");
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT), $role_id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'User created successfully.'];
        header('Location: /dbProject/users/index.php'); exit;
    }
}
$roles = $db->query("SELECT * FROM Role ORDER BY role_name")->fetchAll();
$pageTitle = 'Add User';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Add User</h1></div>
<div class="form-card">
<?php foreach($errors as $e): ?><div class="flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="form-group"><label>Name</label><input name="name" value="<?= htmlspecialchars($_POST['name']??'') ?>" required></div>
  <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($_POST['email']??'') ?>" required></div>
  <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
  <div class="form-group"><label>Role</label>
    <select name="role_id" required>
      <option value="">Select role</option>
      <?php foreach($roles as $r): ?>
        <option value="<?= $r['role_id'] ?>" <?= (($_POST['role_id']??'')==$r['role_id'])?'selected':'' ?>><?= htmlspecialchars($r['role_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn-primary">Create User</button>
    <a href="/dbProject/users/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<?php require_once '../includes/footer.php'; ?>
