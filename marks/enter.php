<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin','Teacher');
$db = getDB();

// AJAX: load students by subject exam (class via SubjectExam→Exam→ClassAssignment)
if (isset($_GET['ajax']) && $_GET['ajax'] === 'students') {
    $seId = (int)$_GET['subject_exam_id'];
    $stmt = $db->prepare("SELECT s.student_id,u.name,COALESCE(m.obtained_marks,'') AS obtained_marks FROM SubjectExam se JOIN ClassAssignment ca ON ca.subject_id=se.subject_id JOIN Student s ON s.section_id=ca.section_id JOIN User u ON u.user_id=s.user_id LEFT JOIN Marks m ON m.student_id=s.student_id AND m.subject_exam_id=se.subject_exam_id WHERE se.subject_exam_id=? GROUP BY s.student_id ORDER BY u.name");
    $stmt->execute([$seId]);
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll());
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $seId   = (int)($_POST['subject_exam_id'] ?? 0);
    $marks  = $_POST['marks'] ?? [];
    if (!$seId) $errors[] = 'Subject exam required.';
    if (empty($errors)) {
        foreach ($marks as $student_id => $obtained) {
            $obtained = (float)$obtained;
            $student_id = (int)$student_id;
            $exists = $db->prepare("SELECT marks_id FROM Marks WHERE student_id=? AND subject_exam_id=?");
            $exists->execute([$student_id,$seId]);
            if ($exists->fetch()) {
                $db->prepare("UPDATE Marks SET obtained_marks=? WHERE student_id=? AND subject_exam_id=?")->execute([$obtained,$student_id,$seId]);
            } else {
                $db->prepare("INSERT INTO Marks (student_id,subject_exam_id,obtained_marks) VALUES (?,?,?)")->execute([$student_id,$seId,$obtained]);
            }
        }
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Marks saved.'];
        header('Location: /dbProject/marks/index.php'); exit;
    }
}

$exams = $db->query("SELECT * FROM Exam ORDER BY date DESC")->fetchAll();
$selectedExam = (int)($_GET['exam_id'] ?? 0);
$subjectExams = [];
if ($selectedExam) {
    $stmt = $db->prepare("SELECT se.*,sub.subject_name FROM SubjectExam se JOIN Subject sub ON sub.subject_id=se.subject_id WHERE se.exam_id=?");
    $stmt->execute([$selectedExam]);
    $subjectExams = $stmt->fetchAll();
}
$pageTitle = 'Enter Marks';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Enter Marks</h1></div>
<div class="card" style="margin-bottom:16px">
  <form method="GET" style="display:flex;gap:12px;align-items:center">
    <select name="exam_id" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px">
      <option value="">Select Exam</option>
      <?php foreach($exams as $e): ?>
        <option value="<?= $e['exam_id'] ?>" <?= $selectedExam==$e['exam_id']?'selected':'' ?>><?= htmlspecialchars($e['exam_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-ghost">Load</button>
  </form>
</div>
<?php if($selectedExam): ?>
<div class="form-card" style="max-width:800px">
<?php foreach($errors as $e): ?><div class="flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="form-group"><label>Subject</label>
    <select name="subject_exam_id" id="se_sel" required style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;width:100%">
      <option value="">Select subject</option>
      <?php foreach($subjectExams as $se): ?>
        <option value="<?= $se['subject_exam_id'] ?>" data-max="<?= $se['max_marks'] ?>"><?= htmlspecialchars($se['subject_name']) ?> (Max: <?= $se['max_marks'] ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div id="marks-table"></div>
  <div class="form-actions" id="submit-row" style="display:none">
    <button type="submit" class="btn-primary">Save Marks</button>
    <a href="/dbProject/marks/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<script>
document.getElementById('se_sel').addEventListener('change', async function() {
    const tbl = document.getElementById('marks-table');
    const row = document.getElementById('submit-row');
    tbl.innerHTML = ''; row.style.display = 'none';
    if (!this.value) return;
    const max = this.options[this.selectedIndex].dataset.max;
    const students = await fetchJSON('/dbProject/marks/enter.php?ajax=students&subject_exam_id=' + this.value);
    if (!students.length) { tbl.innerHTML = '<p style="color:var(--text-muted)">No students found.</p>'; return; }
    let html = '<div class="table-wrap"><table><thead><tr><th>Student</th><th>Max Marks</th><th>Obtained Marks</th></tr></thead><tbody>';
    students.forEach(s => {
        html += `<tr><td>${s.name}</td><td>${max}</td><td><input type="number" name="marks[${s.student_id}]" value="${s.obtained_marks}" min="0" max="${max}" step="0.01" style="width:100px;padding:6px 8px;border:1px solid var(--border);border-radius:var(--radius)"></td></tr>`;
    });
    html += '</tbody></table></div>';
    tbl.innerHTML = html;
    row.style.display = 'flex';
});
</script>
<?php endif; ?>
<?php require_once '../includes/footer.php'; ?>
