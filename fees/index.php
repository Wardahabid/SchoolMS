<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$pageTitle = 'Fees';
$db = getDB();
$page = max(1,(int)($_GET['page'] ?? 1));
$limit = 20; $offset = ($page-1)*$limit;
$total = $db->query("SELECT COUNT(*) FROM Fee")->fetchColumn();
$pages = ceil($total/$limit);
$rows = $db->prepare("SELECT f.*,u.name AS student_name,COALESCE(SUM(fp.amount_paid),0) AS paid FROM Fee f JOIN Student s ON s.student_id=f.student_id JOIN User u ON u.user_id=s.user_id LEFT JOIN FeePayment fp ON fp.fee_id=f.fee_id GROUP BY f.fee_id ORDER BY f.due_date DESC LIMIT $limit OFFSET $offset");
$rows->execute(); $rows = $rows->fetchAll();
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Fees</h1>
  <a href="/dbProject/fees/create.php" class="btn-primary"><i data-lucide="plus"></i> Assign Fee</a>
</div>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><th>#</th><th>Student</th><th>Amount</th><th>Due Date</th><th>Paid</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php if(empty($rows)): ?>
    <tr><td colspan="8"><div class="empty-state">No fee records found.</div></td></tr>
  <?php else: foreach($rows as $r):
    $balance = $r['amount'] - $r['paid'];
  ?>
    <tr>
      <td><?= $r['fee_id'] ?></td>
      <td><?= htmlspecialchars($r['student_name']) ?></td>
      <td><?= number_format($r['amount'],2) ?></td>
      <td><?= $r['due_date'] ?></td>
      <td><?= number_format($r['paid'],2) ?></td>
      <td><?= number_format($balance,2) ?></td>
      <td><span class="badge <?= $balance <= 0 ? 'badge-success' : ($r['due_date'] < date('Y-m-d') ? 'badge-danger' : 'badge-warning') ?>"><?= $balance <= 0 ? 'Paid' : ($r['due_date'] < date('Y-m-d') ? 'Overdue' : 'Pending') ?></span></td>
      <td>
        <a href="/dbProject/fees/view.php?id=<?= $r['fee_id'] ?>" class="btn-ghost btn-sm">View</a>
        <a href="/dbProject/fees/pay.php?fee_id=<?= $r['fee_id'] ?>" class="btn-primary btn-sm">Pay</a>
      </td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
</div>
<?php if($pages>1): ?>
<div class="pagination">
  <?php for($i=1;$i<=$pages;$i++): ?>
    <?php if($i==$page): ?><span class="current"><?= $i ?></span>
    <?php else: ?><a href="?page=<?= $i ?>"><?= $i ?></a><?php endif; ?>
  <?php endfor; ?>
</div>
<?php endif; ?>
</div>
<?php require_once '../includes/footer.php'; ?>
