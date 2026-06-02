<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id) { getDB()->prepare("DELETE FROM ClassAssignment WHERE assignment_id=?")->execute([$id]); $_SESSION['flash'] = ['type'=>'success','msg'=>'Assignment deleted.']; }
}
header('Location: /dbProject/class_assignments/index.php'); exit;
