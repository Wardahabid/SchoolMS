<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin','Teacher','Student');
$pageTitle = 'View Marks';
$db = getDB();
$examId = (int)($_GET['exam_id'] ?? 0);
$exams  = $db->query("SELECT * FROM Exam ORDER BY date DESC")->fetchAll();
$rows   = [];
if ($examId) {
    // Students see only their own marks
    if (hasRole('Student')) {
        $stmt = $db->prepare("SELECT u.name,sub.subject_name,m.obtained_marks,se.max_marks FROM Marks m JOIN SubjectExam se ON se.subject_exam_id=m.subject_exam_id JOIN Subject sub ON sub.subject_id=se.subject_id JOIN Student s ON s.student_id=m.student_id JOIN User u ON u.user_id=s.user_id WHERE se.exam_id=? AND s.user_id=? ORDER BY sub.subject_name");
        $stmt->execute([$examId,$_SESSION['user_id']]);
    } else {
        $stmt = $db->prepare("SELECT u.name,sub.subject_name,m.obtained_marks,se.max_marks FROM Marks m JOIN SubjectExam se ON se.subject_exam_id=m.subject_exam_id JOIN Subject sub ON sub.subject_id=se.subject_id JOIN Student s ON s.student_id=m.student_id JOIN User u ON u.user_id=s.user_id WHERE se.exam_id=? ORDER BY u.name,sub.subject_name");
        $stmt->execute([$examId]);
    }
    $rows = $stmt->fetchAll();
}
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>View Marks</h1></div>
<div class="card" style="margin-bottom:16px">
  <form method="GET" style="display:flex;gap:12px;align-items:center">
    <select name="exam_id" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px">
      <option value="">Select Exam</option>
      <?php foreach($exams as $e): ?>
        <option value="<?= $e['exam_id'] ?>" <?= $examId==$e['exam_id']?'selected':'' ?>><?= htmlspecialchars($e['exam_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-ghost">View</button>
  </form>
</div>
<?php if($examId): ?>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><?php if(!hasRole('Student')): ?><th>Student</th><?php endif; ?><th>Subject</th><th>Obtained</th><th>Max</th><th>%</th></tr></thead>
  <tbody>
  <?php if(empty($rows)): ?>
    <tr><td colspan="5"><div class="empty-state">No marks found.</div></td></tr>
  <?php else: foreach($rows as $r): ?>
    <tr>
      <?php if(!hasRole('Student')): ?><td><?= htmlspecialchars($r['name']) ?></td><?php endif; ?>
      <td><?= htmlspecialchars($r['subject_name']) ?></td>
      <td><?= $r['obtained_marks'] ?></td>
      <td><?= $r['max_marks'] ?></td>
      <td><?= $r['max_marks'] > 0 ? round($r['obtained_marks']/$r['max_marks']*100) : 0 ?>%</td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
</div>
</div>
<?php endif; ?>
<?php require_once '../includes/footer.php'; ?>
