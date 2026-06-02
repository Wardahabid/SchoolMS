<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT s.*,u.name,u.email FROM Staff s JOIN User u ON u.user_id=s.user_id WHERE s.staff_id=?");
$stmt->execute([$id]); $row = $stmt->fetch();
if (!$row) { header('Location: /dbProject/staff/index.php'); exit; }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pos = trim($_POST['position'] ?? '');
    if (!$name) $errors[] = 'Name required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (empty($errors)) {
        $db->prepare("UPDATE User SET name=?,email=? WHERE user_id=?")->execute([$name,$email,$row['user_id']]);
        if (!empty($_POST['password'])) $db->prepare("UPDATE User SET password=? WHERE user_id=?")->execute([password_hash($_POST['password'],PASSWORD_BCRYPT),$row['user_id']]);
        $db->prepare("UPDATE Staff SET position=? WHERE staff_id=?")->execute([$pos,$id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Staff updated.'];
        header('Location: /dbProject/staff/index.php'); exit;
    }
}
$pageTitle = 'Edit Staff';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Edit Staff</h1></div>
<div class="form-card">
<?php foreach($errors as $e): ?><div class="flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="form-row">
    <div class="form-group"><label>Full Name</label><input name="name" value="<?= htmlspecialchars($_POST['name']??$row['name']) ?>" required></div>
    <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($_POST['email']??$row['email']) ?>" required></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>New Password <span class="hint">(leave blank to keep)</span></label><input type="password" name="password"></div>
    <div class="form-group"><label>Position</label><input name="position" value="<?= htmlspecialchars($_POST['position']??$row['position']) ?>" required></div>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn-primary">Save Changes</button>
    <a href="/dbProject/staff/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<?php require_once '../includes/footer.php'; ?>
