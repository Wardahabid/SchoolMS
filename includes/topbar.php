<?php
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<div class="topbar">
  <div style="display:flex;align-items:center;gap:14px">
    <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
    <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? '') ?></span>
  </div>
  <div class="topbar-user">
    <span><?= htmlspecialchars($_SESSION['name'] ?? '') ?></span>
    <span class="badge badge-info"><?= htmlspecialchars($_SESSION['role_name'] ?? '') ?></span>
    <a href="/dbProject/auth/logout.php">Logout</a>
  </div>
</div>
<div class="main-content">
<?php if ($flash): ?>
  <div class="flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>
