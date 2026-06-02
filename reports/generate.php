<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $student_id = (int)($_POST['student_id'] ?? 0);
    $exam_id    = (int)($_POST['exam_id'] ?? 0);
    $remarks    = trim($_POST['remarks'] ?? '');
    if (!$student_id || !$exam_id) $errors[] = 'Student and exam required.';
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT SUM(m.obtained_marks) AS total FROM Marks m JOIN SubjectExam se ON se.subject_exam_id=m.subject_exam_id WHERE m.student_id=? AND se.exam_id=?");
        $stmt->execute([$student_id,$exam_id]);
        $total = (float)($stmt->fetchColumn() ?? 0);
        $maxStmt = $db->prepare("SELECT SUM(se.max_marks) AS max_total FROM SubjectExam se WHERE se.exam_id=?");
        $maxStmt->execute([$exam_id]);
        $maxTotal = (float)($maxStmt->fetchColumn() ?? 0);
        $pct = $maxTotal > 0 ? ($total / $maxTotal * 100) : 0;
        $grade = $pct >= 90 ? 'A' : ($pct >= 75 ? 'B' : ($pct >= 60 ? 'C' : ($pct >= 45 ? 'D' : 'F')));
        $db->prepare("INSERT INTO Report (student_id,exam_id,total_marks,grade,remarks) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE total_marks=VALUES(total_marks),grade=VALUES(grade),remarks=VALUES(remarks)")->execute([$student_id,$exam_id,$total,$grade,$remarks]);
        $reportId = $db->lastInsertId() ?: $db->query("SELECT report_id FROM Report WHERE student_id=$student_id AND exam_id=$exam_id")->fetchColumn();
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Report generated.'];
        header("Location: /reports/view.php?id=$reportId"); exit;
    }
}

$students = $db->query("SELECT s.student_id,u.name FROM Student s JOIN User u ON u.user_id=s.user_id ORDER BY u.name")->fetchAll();
$exams    = $db->query("SELECT * FROM Exam ORDER BY date DESC")->fetchAll();
$pageTitle = 'Generate Report';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Generate Report</h1></div>
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
  <div class="form-group"><label>Exam</label>
    <select name="exam_id" required>
      <option value="">Select exam</option>
      <?php foreach($exams as $e): ?>
        <option value="<?= $e['exam_id'] ?>" <?= (($_POST['exam_id']??'')==$e['exam_id'])?'selected':'' ?>><?= htmlspecialchars($e['exam_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group"><label>Remarks (optional)</label><textarea name="remarks" rows="3"><?= htmlspecialchars($_POST['remarks']??'') ?></textarea></div>
  <div class="form-actions">
    <button type="submit" class="btn-primary">Generate</button>
    <a href="/dbProject/reports/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<?php require_once '../includes/footer.php'; ?>
