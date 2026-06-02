<?php
require_once '../auth/middleware.php';
require_once '../config/db.php';
requireRole('Admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id && $id !== (int)$_SESSION['user_id']) {
        $db = getDB();
        $db->prepare("DELETE FROM User WHERE user_id=?")->execute([$id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'User deleted.'];
    }
}
header('Location: /dbProject/users/index.php'); exit;
