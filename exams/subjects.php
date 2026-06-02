<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
$db = getDB();
$examId = (int)($_GET['exam_id'] ?? 0);
$exam = $db->prepare("SELECT * FROM Exam WHERE exam_id=?");
$exam->execute([$examId]); $exam = $exam->fetch();
if (!$exam) { header('Location: /dbProject/exams/index.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (isset($_POST['add'])) {
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $max_marks  = (float)($_POST['max_marks'] ?? 0);
        if (!$subject_id || $max_marks <= 0) $errors[] = 'Subject and max marks required.';
        if (empty($errors)) {
            $db->prepare("INSERT INTO SubjectExam (exam_id,subject_id,max_marks) VALUES (?,?,?)")->execute([$examId,$subject_id,$max_marks]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Subject added.'];
            header("Location: /exams/subjects.php?exam_id=$examId"); exit;
        }
    } elseif (isset($_POST['remove'])) {
        $seId = (int)($_POST['subject_exam_id'] ?? 0);
        if ($seId) $db->prepare("DELETE FROM SubjectExam WHERE subject_exam_id=?")->execute([$seId]);
        header("Location: /exams/subjects.php?exam_id=$examId"); exit;
    }
}

$subjectExams = $db->prepare("SELECT se.*,sub.subject_name FROM SubjectExam se JOIN Subject sub ON sub.subject_id=se.subject_id WHERE se.exam_id=?");
$subjectExams->execute([$examId]); $subjectExams = $subjectExams->fetchAll();
$subjects = $db->query("SELECT * FROM Subject ORDER BY subject_name")->fetchAll();
$pageTitle = 'Exam Subjects';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Subjects for: <?= htmlspecialchars($exam['exam_name']) ?></h1>
  <a href="/dbProject/exams/index.php" class="btn-ghost">← Back</a>
</div>
<?php foreach($errors as $e): ?><div class="flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<div class="card" style="margin-bottom:20px">
  <h3 style="margin-bottom:14px;font-size:15px">Add Subject</h3>
  <form method="POST" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <div class="form-group" style="margin:0">
      <label>Subject</label>
      <select name="subject_id" required style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px">
        <option value="">Select</option>
        <?php foreach($subjects as $s): ?><option value="<?= $s['subject_id'] ?>"><?= htmlspecialchars($s['subject_name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="margin:0">
      <label>Max Marks</label>
      <input type="number" name="max_marks" min="1" step="0.01" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;width:120px" required>
    </div>
    <button type="submit" name="add" class="btn-primary">Add</button>
  </form>
</div>
<div class="card">
<div class="table-wrap">
<table>
  <thead><tr><th>Subject</th><th>Max Marks</th><th>Actions</th></tr></thead>
  <tbody>
  <?php if(empty($subjectExams)): ?>
    <tr><td colspan="3"><div class="empty-state">No subjects added yet.</div></td></tr>
  <?php else: foreach($subjectExams as $se): ?>
    <tr>
      <td><?= htmlspecialchars($se['subject_name']) ?></td>
      <td><?= $se['max_marks'] ?></td>
      <td>
        <form method="POST" style="display:inline" onsubmit="confirmDelete(this);return false">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="subject_exam_id" value="<?= $se['subject_exam_id'] ?>">
          <button type="submit" name="remove" class="btn-danger btn-sm">Remove</button>
        </form>
      </td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
