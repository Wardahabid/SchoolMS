<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin','Teacher','Student','Parent');
$pageTitle = 'View Marks';
$db = getDB();
$examId = (int)($_GET['exam_id'] ?? 0);
$exams  = $db->query("SELECT * FROM Exam ORDER BY date DESC")->fetchAll();
$rows   = [];

// Parent: pick child
$children = [];
$selectedChild = 0;
if (hasRole('Parent')) {
    $stmt = $db->prepare("SELECT s.student_id,u.name FROM Student s JOIN Parent p ON p.parent_id=s.parent_id JOIN User u ON u.user_id=s.user_id WHERE p.user_id=? ORDER BY u.name");
    $stmt->execute([$_SESSION['user_id']]);
    $children = $stmt->fetchAll();
    $selectedChild = (int)($_GET['student_id'] ?? ($children[0]['student_id'] ?? 0));
    $valid = array_column($children,'student_id');
    if (!in_array($selectedChild,$valid)) $selectedChild = $children[0]['student_id'] ?? 0;
}

if ($examId) {
    if (hasRole('Student')) {
        $stmt = $db->prepare("SELECT u.name,sub.subject_name,m.obtained_marks,se.max_marks FROM Marks m JOIN SubjectExam se ON se.subject_exam_id=m.subject_exam_id JOIN Subject sub ON sub.subject_id=se.subject_id JOIN Student s ON s.student_id=m.student_id JOIN User u ON u.user_id=s.user_id WHERE se.exam_id=? AND s.user_id=? ORDER BY sub.subject_name");
        $stmt->execute([$examId,$_SESSION['user_id']]);
    } elseif (hasRole('Parent') && $selectedChild) {
        $stmt = $db->prepare("SELECT u.name,sub.subject_name,m.obtained_marks,se.max_marks FROM Marks m JOIN SubjectExam se ON se.subject_exam_id=m.subject_exam_id JOIN Subject sub ON sub.subject_id=se.subject_id JOIN Student s ON s.student_id=m.student_id JOIN User u ON u.user_id=s.user_id WHERE se.exam_id=? AND s.student_id=? ORDER BY sub.subject_name");
        $stmt->execute([$examId,$selectedChild]);
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
<?php if(hasRole('Parent') && count($children) > 1): ?>
<div class="card" style="margin-bottom:16px">
  <form method="GET" style="display:flex;gap:12px;align-items:center">
    <select name="student_id" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px">
      <?php foreach($children as $ch): ?>
        <option value="<?= $ch['student_id'] ?>" <?= $selectedChild==$ch['student_id']?'selected':'' ?>><?= htmlspecialchars($ch['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if($examId): ?><input type="hidden" name="exam_id" value="<?= $examId ?>"><?php endif; ?>
    <button type="submit" class="btn-ghost">Switch Child</button>
  </form>
</div>
<?php endif; ?>
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
