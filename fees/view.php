<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$fee = $db->prepare("SELECT f.*,u.name AS student_name FROM Fee f JOIN Student s ON s.student_id=f.student_id JOIN User u ON u.user_id=s.user_id WHERE f.fee_id=?");
$fee->execute([$id]); $fee = $fee->fetch();
if (!$fee) { header('Location: /dbProject/fees/index.php'); exit; }
$payments = $db->prepare("SELECT * FROM FeePayment WHERE fee_id=? ORDER BY payment_paid DESC");
$payments->execute([$id]); $payments = $payments->fetchAll();
$totalPaid = array_sum(array_column($payments,'amount_paid'));
$pageTitle = 'Fee Details';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Fee Details</h1>
  <div style="display:flex;gap:10px">
    <a href="/dbProject/fees/pay.php?fee_id=<?= $id ?>" class="btn-primary">Record Payment</a>
    <a href="/dbProject/fees/index.php" class="btn-ghost">← Back</a>
  </div>
</div>
<div class="card" style="margin-bottom:20px;font-size:13px">
  <p style="margin-bottom:8px"><strong>Student:</strong> <?= htmlspecialchars($fee['student_name']) ?></p>
  <p style="margin-bottom:8px"><strong>Total Amount:</strong> <?= number_format($fee['amount'],2) ?></p>
  <p style="margin-bottom:8px"><strong>Due Date:</strong> <?= $fee['due_date'] ?></p>
  <p style="margin-bottom:8px"><strong>Total Paid:</strong> <?= number_format($totalPaid,2) ?></p>
  <p><strong>Balance:</strong> <?= number_format($fee['amount']-$totalPaid,2) ?></p>
</div>
<div class="card">
  <h3 style="margin-bottom:14px;font-size:15px">Payment History</h3>
  <?php if(empty($payments)): ?><p style="color:var(--text-muted);font-size:13px">No payments yet.</p>
  <?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>#</th><th>Amount Paid</th><th>Date</th></tr></thead>
    <tbody>
    <?php foreach($payments as $p): ?>
      <tr><td><?= $p['payment_id'] ?></td><td><?= number_format($p['amount_paid'],2) ?></td><td><?= $p['payment_paid'] ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
<?php require_once '../includes/footer.php'; ?>
