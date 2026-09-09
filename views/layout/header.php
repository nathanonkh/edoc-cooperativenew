<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <meta name="csrf-token" content="<?php echo Session::getCsrf(); ?>">
  <title><?php echo isset($pageTitle) ? e($pageTitle) . ' — ' : ''; ?>ระบบนำส่งเอกสาร สตท.5</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/app-theme.css">

  <script src="<?php echo APP_URL; ?>/app.js"></script>
</head>
<body>
<?php
global $db;
$unreadCount   = Notification::countUnread($db, $_SESSION['user_id']);
$recentNotifs  = Notification::getRecent($db, $_SESSION['user_id'], 10);
$currentPage   = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$currentAction = isset($_GET['action']) ? $_GET['action'] : 'index';
$userRoles     = isset($_SESSION['roles']) ? $_SESSION['roles'] : array($_SESSION['role']);

function navActive($page, $current) { return $page === $current; }
?>

<div id="app-container">
  <div class="overlay" id="overlay" onclick="toggleAppSidebar(false)"></div>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <i class="fas fa-file-alt" style="font-size:20px;color:#a5d6a7;"></i>
      <div>
        <h3>ระบบนำส่งเอกสารอิเล็กทรอนิกส์<br>สำนักงานตรวจบัญชีสหกรณ์ที่ 5</h3>
      </div>
    </div>
    <nav>
      <ul>
        <li class="menu-item">
          <a class="menu-link" href="?page=dashboard" style="<?php echo navActive('dashboard', $currentPage) ? 'background:#2a5531;' : ''; ?>">
            <span><i class="fas fa-house" style="width:18px;"></i> หน้าหลัก</span>
          </a>
        </li>

        <li class="menu-item">
          <div class="menu-link" onclick="toggleSubMenu(this)">
            <span><i class="fas fa-folder-open" style="width:18px;"></i> เอกสาร</span>
            <i class="fas fa-chevron-<?php echo in_array($currentPage, array('documents','reports')) ? 'up' : 'down'; ?>"></i>
          </div>
          <ul class="submenu <?php echo in_array($currentPage, array('documents','reports')) ? 'open' : ''; ?>">
            <?php if (Auth::hasAnyRole(array('submitter','admin'))): ?>
            <li class="submenu-item"><a href="?page=documents&action=create" class="<?php echo ($currentPage==='documents' && $currentAction==='create') ? 'active' : ''; ?>"><i class="fas fa-plus"></i> นำส่งเอกสาร</a></li>
            <?php endif; ?>
            <li class="submenu-item"><a href="?page=documents" class="<?php echo ($currentPage==='documents' && $currentAction!=='create') ? 'active' : ''; ?>"><i class="fas fa-list"></i> รายการเอกสาร</a></li>
            <li class="submenu-item"><a href="?page=reports" class="<?php echo $currentPage==='reports' ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i> รายงานเอกสาร</a></li>
          </ul>
        </li>

        <li class="menu-item">
          <div class="menu-link" onclick="toggleSubMenu(this)">
            <span><i class="fas fa-triangle-exclamation" style="width:18px;"></i> แจ้งปัญหา</span>
            <i class="fas fa-chevron-<?php echo in_array($currentPage, array('issues','issue_reports')) ? 'up' : 'down'; ?>"></i>
          </div>
          <ul class="submenu <?php echo in_array($currentPage, array('issues','issue_reports')) ? 'open' : ''; ?>">
            <li class="submenu-item"><a href="?page=issues&action=create" class="<?php echo ($currentPage==='issues' && $currentAction==='create') ? 'active' : ''; ?>"><i class="fas fa-plus"></i> แจ้งปัญหาใหม่</a></li>
            <li class="submenu-item"><a href="?page=issues" class="<?php echo ($currentPage==='issues' && $currentAction!=='create') ? 'active' : ''; ?>"><i class="fas fa-list"></i> รายการแจ้งปัญหา</a></li>
            <li class="submenu-item"><a href="?page=issue_reports" class="<?php echo $currentPage==='issue_reports' ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i> รายงานแจ้งปัญหา</a></li>
          </ul>
        </li>

        <li class="menu-item">
          <div class="menu-link" onclick="toggleSubMenu(this)">
            <span><i class="fas fa-concierge-bell" style="width:18px;"></i> บริการภายในสำนักงาน</span>
            <i class="fas fa-chevron-<?php echo $currentPage==='internal_services' ? 'up' : 'down'; ?>"></i>
          </div>
          <ul class="submenu <?php echo $currentPage==='internal_services' ? 'open' : ''; ?>">
            <li class="submenu-item"><a href="?page=internal_services&action=create" class="<?php echo ($currentPage==='internal_services' && $currentAction==='create') ? 'active' : ''; ?>"><i class="fas fa-plus"></i> ยื่นคำขอใหม่</a></li>
            <li class="submenu-item"><a href="?page=internal_services" class="<?php echo ($currentPage==='internal_services' && $currentAction!=='create') ? 'active' : ''; ?>"><i class="fas fa-list"></i> รายการคำขอ</a></li>
          </ul>
        </li>

        <?php if (Auth::hasRole('admin')): ?>
        <li class="menu-item">
          <div class="menu-link" onclick="toggleSubMenu(this)">
            <span><i class="fas fa-gear" style="width:18px;"></i> จัดการระบบ</span>
            <i class="fas fa-chevron-<?php echo in_array($currentPage, array('users','cooperatives','announcements')) ? 'up' : 'down'; ?>"></i>
          </div>
          <ul class="submenu <?php echo in_array($currentPage, array('users','cooperatives','announcements')) ? 'open' : ''; ?>">
            <li class="submenu-item"><a href="?page=users" class="<?php echo $currentPage==='users' ? 'active' : ''; ?>"><i class="fas fa-users"></i> จัดการสมาชิก</a></li>
            <li class="submenu-item"><a href="?page=cooperatives" class="<?php echo $currentPage==='cooperatives' ? 'active' : ''; ?>"><i class="fas fa-building"></i> จัดการสหกรณ์</a></li>
            <li class="submenu-item"><a href="?page=announcements" class="<?php echo $currentPage==='announcements' ? 'active' : ''; ?>"><i class="fas fa-bullhorn"></i> จัดการหน้าหลัก</a></li>
          </ul>
        </li>
        <?php endif; ?>

        <li class="menu-item">
          <a class="menu-link" href="?page=notifications" style="<?php echo navActive('notifications', $currentPage) ? 'background:#2a5531;' : ''; ?>">
            <span><i class="fas fa-bell" style="width:18px;"></i> การแจ้งเตือน</span>
            <?php if ($unreadCount > 0): ?><span class="badge-pill badge-red"><?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?></span><?php endif; ?>
          </a>
        </li>
      </ul>
    </nav>
    <div class="sidebar-footer">eDMS v1.0 &middot; สตท.5</div>
  </aside>

  <main class="main-content">
    <header class="top-header">
      <div class="top-left">
        <i class="fas fa-bars hamburger" onclick="toggleAppSidebar()"></i>
        <div class="header-logo-circle"><i class="fas fa-file-alt"></i></div>
        <div class="org-info">
          <h4><?php echo e($_SESSION['office_name']); ?></h4>
          <span>ระบบนำส่งเอกสารอิเล็กทรอนิกส์</span>
        </div>
      </div>
      <div class="top-right">
        <div style="position:relative;">
          <span class="icon-btn" data-dropdown-toggle="notifDropdown">
            <i class="fas fa-bell"></i>
            <?php if ($unreadCount > 0): ?>
            <span class="notif-badge notif-badge-js"><?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?></span>
            <?php endif; ?>
          </span>
          <div id="notifDropdown" data-dropdown class="dropdown-panel hidden" style="width:320px;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid #eee;background:#f7f9f7;">
              <strong style="font-size:13px;">การแจ้งเตือน</strong>
              <a href="?page=notifications&action=mark_all_read" style="font-size:12px;color:#0277bd;">อ่านทั้งหมด</a>
            </div>
            <div class="notif-list">
              <?php if (empty($recentNotifs)): ?>
              <div style="padding:24px;text-align:center;color:#aaa;font-size:13px;"><i class="fas fa-bell-slash" style="font-size:22px;display:block;margin-bottom:6px;"></i>ไม่มีการแจ้งเตือน</div>
              <?php else: foreach ($recentNotifs as $notif): ?>
              <a href="?page=notifications&action=read&id=<?php echo $notif['id']; ?>&target_id=<?php echo $notif['target_id']; ?>&target_type=<?php echo $notif['target_type']; ?>"
                 class="<?php echo !$notif['is_read'] ? 'unread' : ''; ?>">
                <span class="notif-title"><?php echo e($notif['title']); ?></span>
                <span class="notif-msg"><?php echo e($notif['message']); ?></span>
                <span class="notif-time"><?php echo timeAgo($notif['created_at']); ?></span>
              </a>
              <?php endforeach; endif; ?>
            </div>
            <div style="text-align:center;padding:8px;border-top:1px solid #eee;">
              <a href="?page=notifications" style="font-size:12px;color:#0277bd;">ดูทั้งหมด</a>
            </div>
          </div>
        </div>

        <div style="position:relative;">
          <div class="user-wrapper" data-dropdown-toggle="userDropdown">
            <i class="fas fa-circle-user" style="font-size:26px;color:#555;"></i>
            <div class="user-text">
              <div class="name"><?php echo e(trim($_SESSION['prefix'].' '.$_SESSION['firstname'].' '.$_SESSION['lastname'])); ?></div>
              <div class="role"><?php echo e(getRoleLabel($_SESSION['role'])); ?></div>
            </div>
            <i class="fas fa-chevron-down" style="font-size:11px;"></i>
          </div>
          <div id="userDropdown" data-dropdown class="dropdown-panel hidden" style="left:auto;right:0;">
            <a href="?page=users&action=profile"><i class="fas fa-user" style="width:16px;"></i> โปรไฟล์ของฉัน</a>
            <hr>
            <a href="?action=logout" style="color:#c62828;"><i class="fas fa-right-from-bracket" style="width:16px;"></i> ออกจากระบบ</a>
          </div>
        </div>
      </div>
    </header>

    <div class="content-area">
