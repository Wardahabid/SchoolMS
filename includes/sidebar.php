<?php
$currentPath = $_SERVER['REQUEST_URI'];
function navLink(string $href, string $icon, string $label, string $current): string {
    $active = str_starts_with($current, $href) ? ' active' : '';
    return "<a href=\"{$href}\" class=\"{$active}\"><i data-lucide=\"{$icon}\"></i>{$label}</a>";
}
?>
<nav class="sidebar" id="sidebar">
  <div class="sidebar-logo">School<span>MS</span></div>
  <div class="sidebar-nav">
    <?= navLink('/dbProject/dashboard/index.php','layout-dashboard','Dashboard',$currentPath) ?>

    <div class="nav-section">People</div>
    <?php if(hasRole('Admin','Teacher')): ?>
    <?= navLink('/dbProject/students/index.php','graduation-cap','Students',$currentPath) ?>
    <?php endif; ?>
    <?php if(hasRole('Admin')): ?>
    <?= navLink('/dbProject/teachers/index.php','user-check','Teachers',$currentPath) ?>
    <?= navLink('/dbProject/parents/index.php','users','Parents',$currentPath) ?>
    <?= navLink('/dbProject/staff/index.php','briefcase','Staff',$currentPath) ?>
    <?php endif; ?>

    <div class="nav-section">Academics</div>
    <?php if(hasRole('Admin')): ?>
    <?= navLink('/dbProject/classes/index.php','layers','Classes',$currentPath) ?>
    <?= navLink('/dbProject/sections/index.php','list','Sections',$currentPath) ?>
    <?= navLink('/dbProject/subjects/index.php','book-open','Subjects',$currentPath) ?>
    <?= navLink('/dbProject/class_assignments/index.php','link','Class Assignments',$currentPath) ?>
    <?php endif; ?>
    <?php if(hasRole('Admin','Teacher','Student')): ?>
    <?= navLink('/dbProject/timetable/index.php','calendar','Timetable',$currentPath) ?>
    <?php endif; ?>

    <div class="nav-section">Assessment</div>
    <?php if(hasRole('Admin','Teacher')): ?>
    <?= navLink('/dbProject/attendance/index.php','clipboard-check','Attendance',$currentPath) ?>
    <?php endif; ?>
    <?php if(hasRole('Admin')): ?>
    <?= navLink('/dbProject/exams/index.php','file-text','Exams',$currentPath) ?>
    <?php endif; ?>
    <?php if(hasRole('Admin','Teacher','Student')): ?>
    <?= navLink('/dbProject/marks/index.php','bar-chart-2','Marks',$currentPath) ?>
    <?php endif; ?>
    <?php if(hasRole('Admin','Student','Parent')): ?>
    <?= navLink('/dbProject/reports/index.php','award','Reports',$currentPath) ?>
    <?php endif; ?>

    <?php if(hasRole('Admin')): ?>
    <div class="nav-section">Finance</div>
    <?= navLink('/dbProject/fees/index.php','credit-card','Fees',$currentPath) ?>

    <div class="nav-section">Settings</div>
    <?= navLink('/dbProject/users/index.php','settings','Users',$currentPath) ?>
    <?php endif; ?>
  </div>
</nav>
