<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();
$feeId = (int)($_GET['fee_id'] ?? 0);
$fee = $db->prepare("SELECT f.*,u.name AS student_name FROM Fee f JOIN Student s ON s.student_id=f.student_id JOIN User u ON u.user_id=s.user_id WHERE f.fee_id=?");
$fee->execute([$feeId]); $fee = $fee->fetch();
if (!$fee) { header('Location: /dbProject/fees/index.php'); exit; }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $amount_paid  = (float)($_POST['amount_paid'] ?? 0);
    $payment_paid = $_POST['payment_paid'] ?? '';
    if ($amount_paid <= 0 || !$payment_paid) $errors[] = 'Amount and date required.';
    if (empty($errors)) {
        $db->prepare("INSERT INTO FeePayment (fee_id,amount_paid,payment_paid) VALUES (?,?,?)")->execute([$feeId,$amount_paid,$payment_paid]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Payment recorded.'];
        header('Location: /dbProject/fees/index.php'); exit;
    }
}
$pageTitle = 'Record Payment';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Record Payment</h1></div>
<div class="form-card">
  <div style="margin-bottom:20px;font-size:13px">
    <p><strong>Student:</strong> <?= htmlspecialchars($fee['student_name']) ?></p>
    <p><strong>Total Amount:</strong> <?= number_format($fee['amount'],2) ?></p>
    <p><strong>Due Date:</strong> <?= $fee['due_date'] ?></p>
  </div>
<?php foreach($errors as $e): ?><div class="flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="form-row">
    <div class="form-group"><label>Amount Paid</label><input type="number" name="amount_paid" min="0.01" step="0.01" value="<?= htmlspecialchars($_POST['amount_paid']??'') ?>" required></div>
    <div class="form-group"><label>Payment Date</label><input type="date" name="payment_paid" value="<?= date('Y-m-d') ?>" required></div>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn-primary">Record Payment</button>
    <a href="/dbProject/fees/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<?php require_once '../includes/footer.php'; ?>
