<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin','Student','Parent');
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$report = $db->prepare("SELECT r.*,u.name AS student_name,e.exam_name,e.date AS exam_date,c.class_name FROM Report r JOIN Student s ON s.student_id=r.student_id JOIN User u ON u.user_id=s.user_id JOIN Exam e ON e.exam_id=r.exam_id JOIN Class c ON c.class_id=s.class_id WHERE r.report_id=?");
$report->execute([$id]); $report = $report->fetch();
if (!$report) { header('Location: /dbProject/reports/index.php'); exit; }
$marks = $db->prepare("SELECT sub.subject_name,m.obtained_marks,se.max_marks FROM Marks m JOIN SubjectExam se ON se.subject_exam_id=m.subject_exam_id JOIN Subject sub ON sub.subject_id=se.subject_id WHERE m.student_id=? AND se.exam_id=? ORDER BY sub.subject_name");
$marks->execute([$report['student_id'],$report['exam_id']]); $marks = $marks->fetchAll();
$pageTitle = 'Report Card';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>
<div class="page-header">
  <h1>Report Card</h1>
  <div style="display:flex;gap:10px">
    <a href="/dbProject/reports/index.php" class="btn-ghost">← Back</a>
    <button onclick="window.print()" class="btn-primary"><i data-lucide="printer"></i> Print</button>
  </div>
</div>
<div class="card" style="max-width:700px">
  <div style="text-align:center;margin-bottom:24px;border-bottom:1px solid var(--border);padding-bottom:16px">
    <h2 style="font-size:20px;margin-bottom:4px">School Management System</h2>
    <p style="color:var(--text-muted);font-size:13px">Report Card</p>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;font-size:13px">
    <div><strong>Student:</strong> <?= htmlspecialchars($report['student_name']) ?></div>
    <div><strong>Class:</strong> <?= htmlspecialchars($report['class_name']) ?></div>
    <div><strong>Exam:</strong> <?= htmlspecialchars($report['exam_name']) ?></div>
    <div><strong>Date:</strong> <?= $report['exam_date'] ?></div>
  </div>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Subject</th><th>Obtained</th><th>Max</th><th>%</th></tr></thead>
    <tbody>
    <?php foreach($marks as $m): ?>
      <tr>
        <td><?= htmlspecialchars($m['subject_name']) ?></td>
        <td><?= $m['obtained_marks'] ?></td>
        <td><?= $m['max_marks'] ?></td>
        <td><?= $m['max_marks'] > 0 ? round($m['obtained_marks']/$m['max_marks']*100) : 0 ?>%</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
    <div style="font-size:15px"><strong>Total:</strong> <?= $report['total_marks'] ?></div>
    <div><span class="badge <?= $report['grade']==='A'?'badge-success':($report['grade']==='F'?'badge-danger':'badge-warning') ?>" style="font-size:18px;padding:6px 16px"><?= $report['grade'] ?></span></div>
  </div>
  <?php if($report['remarks']): ?>
  <div style="margin-top:14px;font-size:13px;color:var(--text-muted)"><strong>Remarks:</strong> <?= htmlspecialchars($report['remarks']) ?></div>
  <?php endif; ?>
</div>
<?php require_once '../includes/footer.php'; ?>
