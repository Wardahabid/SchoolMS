<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$user = $db->prepare("SELECT * FROM User WHERE user_id=?");
$user->execute([$id]);
$user = $user->fetch();
if (!$user) { header('Location: /dbProject/users/index.php'); exit; }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $role_id = (int)($_POST['role_id'] ?? 0);
    if (!$name) $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (empty($errors)) {
        if (!empty($_POST['password'])) {
            $db->prepare("UPDATE User SET name=?,email=?,password=?,role_id=? WHERE user_id=?")->execute([$name,$email,password_hash($_POST['password'],PASSWORD_BCRYPT),$role_id,$id]);
        } else {
            $db->prepare("UPDATE User SET name=?,email=?,role_id=? WHERE user_id=?")->execute([$name,$email,$role_id,$id]);
        }
        $_SESSION['flash'] = ['type'=>'success','msg'=>'User updated.'];
        header('Location: /dbProject/users/index.php'); exit;
    }
}
$roles = $db->query("SELECT * FROM Role ORDER BY role_name")->fetchAll();
$pageTitle = 'Edit User';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Edit User</h1></div>
<div class="form-card">
<?php foreach($errors as $e): ?><div class="flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="form-group"><label>Name</label><input name="name" value="<?= htmlspecialchars($_POST['name']??$user['name']) ?>" required></div>
  <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($_POST['email']??$user['email']) ?>" required></div>
  <div class="form-group"><label>New Password <span class="hint">(leave blank to keep current)</span></label><input type="password" name="password"></div>
  <div class="form-group"><label>Role</label>
    <select name="role_id" required>
      <?php foreach($roles as $r): ?>
        <option value="<?= $r['role_id'] ?>" <?= (($_POST['role_id']??$user['role_id'])==$r['role_id'])?'selected':'' ?>><?= htmlspecialchars($r['role_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn-primary">Save Changes</button>
    <a href="/dbProject/users/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<?php require_once '../includes/footer.php'; ?>
