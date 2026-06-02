<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id) { getDB()->prepare("DELETE FROM Exam WHERE exam_id=?")->execute([$id]); $_SESSION['flash'] = ['type'=>'success','msg'=>'Exam deleted.']; }
}
header('Location: /dbProject/exams/index.php'); exit;
