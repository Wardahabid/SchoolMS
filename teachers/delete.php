<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $db = getDB();
        $row = $db->prepare("SELECT user_id FROM Teacher WHERE teacher_id=?");
        $row->execute([$id]); $t = $row->fetch();
        $db->prepare("DELETE FROM Teacher WHERE teacher_id=?")->execute([$id]);
        if ($t) $db->prepare("DELETE FROM User WHERE user_id=?")->execute([$t['user_id']]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Teacher deleted.'];
    }
}
header('Location: /dbProject/teachers/index.php'); exit;
