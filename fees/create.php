<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $student_id = (int)($_POST['student_id'] ?? 0);
    $amount     = (float)($_POST['amount'] ?? 0);
    $due_date   = $_POST['due_date'] ?? '';
    if (!$student_id || $amount <= 0 || !$due_date) $errors[] = 'All fields required.';
    if (empty($errors)) {
        $db->prepare("INSERT INTO Fee (student_id,amount,due_date) VALUES (?,?,?)")->execute([$student_id,$amount,$due_date]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Fee assigned.'];
        header('Location: /dbProject/fees/index.php'); exit;
    }
}
$students = $db->query("SELECT s.student_id,u.name FROM Student s JOIN User u ON u.user_id=s.user_id ORDER BY u.name")->fetchAll();
$pageTitle = 'Assign Fee';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Assign Fee</h1></div>
<div class="form-card">
<?php foreach($errors as $e): ?><div class="flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="form-group"><label>Student</label>
    <select name="student_id" required>
      <option value="">Select student</option>
      <?php foreach($students as $s): ?>
        <option value="<?= $s['student_id'] ?>" <?= (($_POST['student_id']??'')==$s['student_id'])?'selected':'' ?>><?= htmlspecialchars($s['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Amount</label><input type="number" name="amount" min="0.01" step="0.01" value="<?= htmlspecialchars($_POST['amount']??'') ?>" required></div>
    <div class="form-group"><label>Due Date</label><input type="date" name="due_date" value="<?= htmlspecialchars($_POST['due_date']??'') ?>" required></div>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn-primary">Assign Fee</button>
    <a href="/dbProject/fees/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<?php require_once '../includes/footer.php'; ?>
