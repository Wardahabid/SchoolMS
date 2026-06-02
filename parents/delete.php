<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $db = getDB();
        $row = $db->prepare("SELECT user_id FROM Parent WHERE parent_id=?");
        $row->execute([$id]); $p = $row->fetch();
        $db->prepare("DELETE FROM Parent WHERE parent_id=?")->execute([$id]);
        if ($p) $db->prepare("DELETE FROM User WHERE user_id=?")->execute([$p['user_id']]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Parent deleted.'];
    }
}
header('Location: /dbProject/parents/index.php'); exit;
