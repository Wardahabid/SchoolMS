<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin','Teacher');
$db = getDB();

// AJAX: load students by section
if (isset($_GET['ajax']) && $_GET['ajax'] === 'students') {
    $stmt = $db->prepare("SELECT s.student_id,u.name FROM Student s JOIN User u ON u.user_id=s.user_id WHERE s.section_id=? ORDER BY u.name");
    $stmt->execute([(int)$_GET['section_id']]);
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll());
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $section_id  = (int)($_POST['section_id'] ?? 0);
    $date        = $_POST['date'] ?? '';
    $statuses    = $_POST['status'] ?? [];
    if (!$section_id || !$date) $errors[] = 'Section and date required.';
    if (empty($errors)) {
        $teacherStmt = $db->prepare("SELECT teacher_id FROM Teacher WHERE user_id=?");
        $teacherStmt->execute([$_SESSION['user_id']]);
        $teacher = $teacherStmt->fetch();
        $teacher_id = $teacher['teacher_id'] ?? null;
        $db->prepare("INSERT INTO AttendanceSession (section_id,teacher_id,date) VALUES (?,?,?)")->execute([$section_id,$teacher_id,$date]);
        $session_id = $db->lastInsertId();
        foreach ($statuses as $student_id => $status) {
            $db->prepare("INSERT INTO AttendanceRecord (session_id,student_id,status) VALUES (?,?,?)")->execute([$session_id,(int)$student_id,$status]);
        }
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Attendance saved.'];
        header('Location: /dbProject/attendance/index.php'); exit;
    }
}

$sections = $db->query("SELECT s.*,c.class_name FROM Section s JOIN Class c ON c.class_id=s.class_id ORDER BY c.class_name,s.section_name")->fetchAll();
$pageTitle = 'Take Attendance';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header"><h1>Take Attendance</h1></div>
<div class="form-card" style="max-width:800px">
<?php foreach($errors as $e): ?><div class="flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<form method="POST" id="att-form">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="form-row">
    <div class="form-group"><label>Section</label>
      <select name="section_id" id="section_id" required>
        <option value="">Select section</option>
        <?php foreach($sections as $s): ?>
          <option value="<?= $s['section_id'] ?>"><?= htmlspecialchars($s['class_name'].' — '.$s['section_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>Date</label><input type="date" name="date" value="<?= date('Y-m-d') ?>" required></div>
  </div>
  <div id="student-list" style="margin-top:16px"></div>
  <div class="form-actions" id="submit-row" style="display:none">
    <button type="submit" class="btn-primary">Save Attendance</button>
    <a href="/dbProject/attendance/index.php" class="btn-ghost">Cancel</a>
  </div>
</form>
</div>
<script>
document.getElementById('section_id').addEventListener('change', async function() {
    const list = document.getElementById('student-list');
    const row  = document.getElementById('submit-row');
    list.innerHTML = '';
    row.style.display = 'none';
    if (!this.value) return;
    const students = await fetchJSON('/dbProject/attendance/take.php?ajax=students&section_id=' + this.value);
    if (!students.length) { list.innerHTML = '<p style="color:var(--text-muted)">No students in this section.</p>'; return; }
    let html = '<div class="table-wrap"><table><thead><tr><th>Student</th><th>Present</th><th>Absent</th><th>Late</th></tr></thead><tbody>';
    students.forEach(s => {
        html += `<tr><td>${s.name}</td>
        <td><input type="radio" name="status[${s.student_id}]" value="Present" checked></td>
        <td><input type="radio" name="status[${s.student_id}]" value="Absent"></td>
        <td><input type="radio" name="status[${s.student_id}]" value="Late"></td></tr>`;
    });
    html += '</tbody></table></div>';
    list.innerHTML = html;
    row.style.display = 'flex';
});
</script>
<?php require_once '../includes/footer.php'; ?>
