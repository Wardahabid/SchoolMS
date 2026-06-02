<?php
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: /dbProject/dashboard/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $db = getDB();
    $stmt = $db->prepare("SELECT u.*, r.role_name FROM User u JOIN Role r ON u.role_id = r.role_id WHERE u.email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['name']      = $user['name'];
        $_SESSION['role_name'] = $user['role_name'];
        header('Location: /dbProject/dashboard/index.php');
        exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — School Management</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/global.css">
<style>
body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--bg)}
.login-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:40px;width:100%;max-width:400px;box-shadow:var(--shadow)}
.login-card h1{font-family:'DM Sans',sans-serif;font-size:22px;margin-bottom:6px}
.login-card p{color:var(--text-muted);margin-bottom:28px;font-size:13px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;font-weight:500;margin-bottom:6px}
.form-group input{width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:14px;font-family:inherit;box-sizing:border-box;outline:none}
.form-group input:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-lt)}
.btn-primary{width:100%;padding:10px;background:var(--accent);color:#fff;border:none;border-radius:var(--radius);font-size:14px;font-weight:600;cursor:pointer;font-family:inherit}
.btn-primary:hover{opacity:.9}
.alert-danger{background:#FEE2E2;color:var(--danger);padding:10px 14px;border-radius:var(--radius);font-size:13px;margin-bottom:16px}
</style>
</head>
<body>
<div class="login-card">
  <h1>School Management</h1>
  <p>Sign in to your account</p>
  <?php if ($error): ?><div class="alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST">
    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" required>
    </div>
    <button type="submit" class="btn-primary">Sign In</button>
  </form>
</div>
</body>
</html>
