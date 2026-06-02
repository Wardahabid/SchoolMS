<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $db = getDB();
        $row = $db->prepare("SELECT user_id FROM Staff WHERE staff_id=?");
        $row->execute([$id]); $s = $row->fetch();
        $db->prepare("DELETE FROM Staff WHERE staff_id=?")->execute([$id]);
        if ($s) $db->prepare("DELETE FROM User WHERE user_id=?")->execute([$s['user_id']]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Staff deleted.'];
    }
}
header('Location: /dbProject/staff/index.php'); exit;
