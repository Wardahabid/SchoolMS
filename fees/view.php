<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin','Student','Parent');
$db = getDB();

if (hasRole('Student')) {
    // Show list of own fees
    $stmt = $db->prepare("SELECT f.fee_id,f.amount,f.due_date,COALESCE(SUM(fp.amount_paid),0) AS paid FROM Fee f LEFT JOIN FeePayment fp ON fp.fee_id=f.fee_id JOIN Student s ON s.student_id=f.student_id WHERE s.user_id=? GROUP BY f.fee_id ORDER BY f.due_date DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $fees = $stmt->fetchAll();
    $pageTitle = 'My Fees';
    require_once '../includes/header.php';
    require_once '../includes/sidebar.php';
    require_once '../includes/topbar.php';
    ?>
    <div class="page-header"><h1>My Fees</h1></div>
    <div class="card"><div class="table-wrap"><table>
      <thead><tr><th>Amount</th><th>Due Date</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
      <tbody>
      <?php if(empty($fees)): ?><tr><td colspan="5"><div class="empty-state">No fee records.</div></td></tr>
      <?php else: foreach($fees as $f): $bal=$f['amount']-$f['paid']; ?>
        <tr>
          <td><?= number_format($f['amount'],2) ?></td>
          <td><?= $f['due_date'] ?></td>
          <td><?= number_format($f['paid'],2) ?></td>
          <td><?= number_format($bal,2) ?></td>
          <td><span class="badge <?= $bal<=0?'badge-success':($f['due_date']<date('Y-m-d')?'badge-danger':'badge-warning') ?>"><?= $bal<=0?'Paid':($f['due_date']<date('Y-m-d')?'Overdue':'Pending') ?></span></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table></div></div>
    <?php require_once '../includes/footer.php'; exit;
}

if (hasRole('Parent')) {
    $stmt = $db->prepare("SELECT s.student_id,u.name FROM Student s JOIN Parent p ON p.parent_id=s.parent_id JOIN User u ON u.user_id=s.user_id WHERE p.user_id=? ORDER BY u.name");
    $stmt->execute([$_SESSION['user_id']]);
    $children = $stmt->fetchAll();
    $selectedChild = (int)($_GET['student_id'] ?? ($children[0]['student_id'] ?? 0));
    $valid = array_column($children,'student_id');
    if (!in_array($selectedChild,$valid)) $selectedChild = $children[0]['student_id'] ?? 0;
    $stmt = $db->prepare("SELECT f.fee_id,f.amount,f.due_date,COALESCE(SUM(fp.amount_paid),0) AS paid FROM Fee f LEFT JOIN FeePayment fp ON fp.fee_id=f.fee_id WHERE f.student_id=? GROUP BY f.fee_id ORDER BY f.due_date DESC");
    $stmt->execute([$selectedChild]);
    $fees = $stmt->fetchAll();
    $childName = $children[array_search($selectedChild,array_column($children,'student_id'))]['name'] ?? '';
    $pageTitle = 'Fees';
    require_once '../includes/header.php';
    require_once '../includes/sidebar.php';
    require_once '../includes/topbar.php';
    ?>
    <div class="page-header"><h1>Fees — <?= htmlspecialchars($childName) ?></h1></div>
    <?php if(count($children)>1): ?>
    <div class="card" style="margin-bottom:16px">
      <form method="GET" style="display:flex;gap:12px;align-items:center">
        <select name="student_id" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px">
          <?php foreach($children as $ch): ?>
            <option value="<?= $ch['student_id'] ?>" <?= $selectedChild==$ch['student_id']?'selected':'' ?>><?= htmlspecialchars($ch['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-ghost">Switch Child</button>
      </form>
    </div>
    <?php endif; ?>
    <div class="card"><div class="table-wrap"><table>
      <thead><tr><th>Amount</th><th>Due Date</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
      <tbody>
      <?php if(empty($fees)): ?><tr><td colspan="5"><div class="empty-state">No fee records.</div></td></tr>
      <?php else: foreach($fees as $f): $bal=$f['amount']-$f['paid']; ?>
        <tr>
          <td><?= number_format($f['amount'],2) ?></td>
          <td><?= $f['due_date'] ?></td>
          <td><?= number_format($f['paid'],2) ?></td>
          <td><?= number_format($bal,2) ?></td>
          <td><span class="badge <?= $bal<=0?'badge-success':($f['due_date']<date('Y-m-d')?'badge-danger':'badge-warning') ?>"><?= $bal<=0?'Paid':($f['due_date']<date('Y-m-d')?'Overdue':'Pending') ?></span></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table></div></div>
    <?php require_once '../includes/footer.php'; exit;
}

// Admin
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
