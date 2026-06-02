<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /dbProject/dashboard/index.php');
} else {
    header('Location: /dbProject/auth/login.php');
}
exit;
