<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT t.*,u.name,u.email FROM Teacher t JOIN User u ON u.user_id=t.user_id WHERE t.teacher_id=?");
$stmt->execute([$id]); $row = $stmt->fetch();
if (!$row) { header('Location: /dbProject/teachers/index.php'); exit; }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $spec = trim($_POST['specialization'] ?? '');
    if (!$name) $errors[] = 'Name required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (empty($errors)) {
        $db->prepare("UPDATE User SET name=?,email=? WHERE user_id=?")->execute([$name,$email,$row['user_id']]);
        if (!empty($_POST['password'])) $db->prepare("UPDATE User SET password=? WHERE user_id=?")->execute([password_hash($_POST['password'],PASSWORD_BCRYPT),$row['user_id']]);
        $db->prepare("UPDATE Teacher SET specialization=? WHERE teacher_id=?")->execute([$spec,$id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Teacher updated.'];
        header('Location: /dbProject/teachers/index.php'); exit;
    }
}
$pageTitle = 'Edit Teacher';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Edit Teacher</h1></div>
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
    <div class="form-group"><label>Specialization</label><input name="specialization" value="<?= htmlspecialchars($_POST['specialization']??$row['specialization']) ?>" required></div>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn-primary">Save Changes</button>
    <a href="/dbProject/teachers/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<?php require_once '../includes/footer.php'; ?>
